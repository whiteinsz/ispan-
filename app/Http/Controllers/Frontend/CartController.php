<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function add(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return redirect()->back()->with('error', '未找到產品!');
        }

        $quantity = $request->input('quantity', 1); // 預設為 1，允許使用者選擇數量

        if (Auth::check()) {
            // **會員模式：儲存到資料庫**
            $userId = Auth::id();
            $cartItem = DB::table('shopping_carts')
                ->where('user_id', $userId)
                ->where('product_id', $id)
                ->first();

            if ($cartItem) {
                DB::table('shopping_carts')
                    ->where('user_id', $userId)
                    ->where('product_id', $id)
                    ->increment('quantity', $quantity);
            } else {
                DB::table('shopping_carts')->insert([
                    'user_id' => $userId,
                    'product_id' => $id,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            // **訪客模式：使用 Session**
            $cartItem = Cart::get($id);
            if ($cartItem) {
                Cart::update($id, ['quantity' => $quantity]);
            } else {
                Cart::add([
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'attributes' => ['image' => $product->thumb_image]
                ]);
                $this->updateCartOrder($id);
            }
        }

        return redirect()->back()->with('success', '成功加入購物車');
    }


    // ✅ **移除購物車商品**
    public function remove($id)
    {
        if (Auth::check()) {
            $deleted = DB::table('shopping_carts')
                ->where('user_id', Auth::id())
                ->where('product_id', $id)
                ->delete();

            if (!$deleted) {
                return redirect()->back()->with('error', '移除失敗，請稍後再試。');
            }
        } else {
            Cart::remove($id);
            $this->removeFromCartOrder($id);
        }

        return redirect()->back()->with('success', '產品已從購物車中移除！');
    }


    // ✅ **更新購物車數量**
    public function update(Request $request, $id)
    {

        $validated = $request->validate([
            'quantity' => 'required' // 限制數量在 1 到 3 之間
        ]);
        

        if (Auth::check()) {
            // **會員模式**
            DB::table('shopping_carts')
                ->where('user_id', Auth::id())
                ->where('product_id', $id)
                ->update(['quantity' => $validated['quantity'], 'updated_at' => now()]);
        } else {
            // **訪客模式**
            $cartItem = Cart::get($id);
            if (!$cartItem) {
                return redirect()->back()->with('error', '購物車中未找到商品。');
            }

            Cart::update($id, ['quantity' => ['relative' => false, 'value' => $validated['quantity']]]); 
        }

        return redirect()->back()->with('success', '產品數量已更新。');
    }


    // ✅ **減少購物車數量**
    public function decrease($id)
    {
        if (Auth::check()) {
            // **會員模式**
            $cartItem = DB::table('shopping_carts')
                ->where('user_id', Auth::id())
                ->where('product_id', $id)
                ->first();

            if (!$cartItem) {
                return redirect()->back()->with('error', '購物車中未找到商品。');
            }

            if ($cartItem->quantity > 1) {
                DB::table('shopping_carts')
                    ->where('user_id', Auth::id())
                    ->where('product_id', $id)
                    ->decrement('quantity', 1);
            } else {
                DB::table('shopping_carts')
                    ->where('user_id', Auth::id())
                    ->where('product_id', $id)
                    ->delete();
            }
        } else {
            // **訪客模式**
            $cartItem = Cart::get($id);
            if (!$cartItem) {
                return redirect()->back()->with('error', '購物車中未找到商品。');
            }

            if ($cartItem->quantity > 1) {
                Cart::update($id, ['quantity' => -1]);
            } else {
                Cart::remove($id);
                $this->removeFromCartOrder($id);
            }
        }

        return redirect()->back()->with('success', '產品數量已更新。');
    }

    // ✅ **顯示購物車**
    public function index()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $userInfo = DB::table('users')
            ->where('id', $userId)
            ->select('email','name','phone')
            ->get();
            $cartItems = DB::table('shopping_carts')
                ->where('shopping_carts.user_id', $userId)
                ->join('products', 'shopping_carts.product_id', '=', 'products.id')
                ->select('shopping_carts.id', 'shopping_carts.product_id', 'shopping_carts.quantity', 'products.name', 'products.thumb_image as image', 'products.price',)
                ->get();
            $cartTotal = $cartItems->sum(fn ($item) => $item->price * $item->quantity);
        } else {
            $userInfo=[];
            $cartItems = collect();
            $order = session('cart_order', []);

            foreach ($order as $id) {
                $item = Cart::get($id);
                if ($item) {
                    $cartItems->push($item);
                }
            }

            $cartTotal = Cart::isEmpty() ? 0 : Cart::getTotal();
        }
        return view('frontend.cart.cart', compact('cartItems', 'cartTotal','userInfo'));
    }

    // ✅ **更新訪客購物車順序**
    private function updateCartOrder($id)
    {
        $order = session('cart_order', []);
        if (!in_array($id, $order)) {
            $order[] = $id;
        }
        session(['cart_order' => $order]);
    }

    // ✅ **從訪客購物車順序中移除商品**
    private function removeFromCartOrder($id)
    {
        $order = session('cart_order', []);
        $order = array_filter($order, fn ($item) => $item != $id);
        session(['cart_order' => array_values($order)]);
    }

    // 取得購物車商品數量
    public function getCartItemCount()
    {
        if (Auth::check()) {
            // **會員模式：計算購物車內不同商品種類數量**
            $cartCount = DB::table('shopping_carts')
                ->where('user_id', Auth::id())
                ->count();
        } else {
            // **訪客模式：計算 Session 購物車內不同商品種類數量**
            $cartCount = Cart::isEmpty() ? 0 : count(Cart::getContent());
        }

        return response()->json(['count' => $cartCount]);
    }

}
