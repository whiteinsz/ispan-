<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next )
    {
        // 確保用戶已登入
        if (!Auth::check()) {
            return redirect('admin/login')->with('error', '請先登入');
        }

        // 確保用戶是管理員
        if (Auth::user()->role !== 'admin') {
            return redirect('/')->with('error', '您無權訪問此頁面');
        }

        return $next($request);
    }
}


