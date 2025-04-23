<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use App\Models\Category;
use App\Models\Footer_info;
use App\Models\Footer_social;
use App\Models\Index_roller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HomeController extends Controller
{
    /**
     * 顯示完整的首頁（包括產品、分類、輪播圖、評論等）
     */
    public function index()
    {
        $categories = Category::pluck('name', 'id');
        $footer_infos = Footer_info::get();
        $footer_socias = Footer_social::get();
        $carousel_image_paths = Carousel::get();
        $product_types = Product::where('product_type', 'new_arrival')
            ->where('status', 1)
            ->get(['id', 'name', 'thumb_image']);

        // 取得最近 6 筆 4 星以上的評論
        
        $avgRating = ProductReview::where('status', 1)->avg('rating') ?? 0;
        $reviews = ProductReview::where('status', 1)
            ->where('rating', '>=', 4)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->with('user')
            ->get()
            ->map(function ($review) {
                $review->formatted_date = Carbon::parse($review->created_at)->format('Y-m-d H:i');
                return $review;
            });

        $roller = Index_roller::get();
        $memberCount = User::count(); // 計算 users 表的總數

        return view('frontend.index', compact(
            'categories',
            'footer_infos',
            'footer_socias',
            'product_types',
            'carousel_image_paths',
            'reviews',
            'avgRating',
            'roller',
            'memberCount',
        ));
    }

    /**
     * 顯示獨立的評論頁面（不包含首頁內容）
     */
    public function reviews()
    {
        $reviews = ProductReview::where('status', 1)
            ->where('rating', '>=', 4)
            ->orderBy('created_at', 'desc')
            ->with('user')
            ->get();

        return view('frontend.layouts.product_review', compact('reviews'));
    }

    /**
     * 儲存用戶提交的評論
     */
    public function storeReview(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:100',
        ]);

        if (!Auth::check()) {
            return redirect()->back()->with('error', '請先登入後再提交評論。');
        }

        ProductReview::create([
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'review' => $request->review,
            'status' => 1, 
            'product_id' => null, // ✅ 明確設為 `NULL`
        ]);

        return redirect()->back()->with('success', '評論已提交，待審核後將顯示。');
    }
}
