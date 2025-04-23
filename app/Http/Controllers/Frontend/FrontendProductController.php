<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;


class FrontendProductController extends Controller
{
    //
    public function products(){

        // 從資料庫取得所有商品資料 (你可能需要添加條件篩選，例如只顯示上架的商品)
        $products = Product::where('status',1)->get();

        // 將資料傳遞給視圖
        return view('frontend.product.product', compact('products'));
    }

    public function showProduct(string $name)
    {
        $product = Product::with('category', 'productImageGalleries')->where('name', $name)->where('status', 1)->first();
        return view('frontend.product.product-detail', compact('product'));
    }

}
