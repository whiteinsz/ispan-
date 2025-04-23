<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductReview;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|max:500',
        ]);

        ProductReview::create([
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'review' => $request->review,
            'status' => 1, // ✅ 設定為 1，表示直接顯示
        ]);

        return redirect()->route('home.index')->with('success', '評論提交成功！');
    }
}
