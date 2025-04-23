<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;

class ShopController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->get();
        $categories = Category::where('status', 1)->get();
        return view('frontend.cart.shop', compact('products','categories')); // 加上 cart.
    }
     // 新增搜尋方法
     public function search(Request $request)
     {
         $query = $request->input('query');
         $categories = Category::where('status', 1)->get();

 
         // 檢查是否有輸入搜尋關鍵字
         if ($query) {
             // 查找符合名稱或描述的商品
             $products = Product::where('name', 'LIKE', "%{$query}%")
                                ->orWhere('short_description', 'LIKE', "%{$query}%")
                                ->get();
         } else {
             // 若沒有輸入，則顯示全部商品
             $products = Product::all();
         }
 
         return view('frontend.cart.shop', compact('products','categories'));
     }

     public function categoryFilter($categoryId)
     {
        $categories = Category::where('status', 1)->get();// 取得所有分類
         $products = Product::where('category_id', $categoryId)->get(); // 取得對應分類的商品
         $selectedCategory = Category::find($categoryId); // 取得當前選中的分類
 
         return view('frontend.cart.shop', compact('products', 'categories', 'selectedCategory'));
     }

     public function showProduct(string $name)
     {
         $product = Product::with('category', 'productImageGalleries')->where('name', $name)->where('status', 1)->first();
         return view('frontend.product.product-detail', compact('product'));
     }
}
