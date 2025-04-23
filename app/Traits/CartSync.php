<?php

namespace App\Traits;

use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Facades\DB;

trait CartSync
{
    public function syncCartWithDatabase($user)
    {
        $guestCart = Cart::getContent();

        foreach ($guestCart as $item) {
            // 檢查資料庫是否已有該商品
            $existingCartItem = DB::table('shopping_carts')
                ->where('user_id', $user->id)
                ->where('product_id', $item->id)
                ->first();

            if ($existingCartItem) {
                // 更新數量
                DB::table('shopping_carts')
                    ->where('id', $existingCartItem->id)
                    ->update([
                        'quantity' => $existingCartItem->quantity + $item->quantity,
                        'updated_at' => now()
                    ]);
            } else {
                // 新增到資料庫
                DB::table('shopping_carts')->insert([
                    'user_id' => $user->id,
                    'product_id' => $item->id,
                    'quantity' => $item->quantity,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // 清除 Session 購物車，轉換為資料庫管理
        Cart::clear();
    }
}
