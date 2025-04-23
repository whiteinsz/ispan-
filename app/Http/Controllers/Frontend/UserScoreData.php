<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class UserScoreData extends Controller
{
    public function getUserScoreData()
    {
        $Score = []; // 预设为空数组，避免未定义变量错误

        if (Auth::check()) {
            // **会员模式**
            $Score = DB::table('user_scores')
                ->where('user_id', Auth::id())
                ->get(); // 获取查询结果
        }

        return response()->json($Score);
    }
}
