<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PageView;
use Illuminate\Support\Facades\Log;

class TrackVisitor
{
    public function handle(Request $request, Closure $next)
    {
        // 只記錄網址為 'http://127.0.0.1:8000/' 的訪問
        if ($request->is('/') || $request->fullUrl() == 'http://127.0.0.1:8000/') {
            try {
                PageView::create([
                    'url'         => $request->fullUrl(),
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->header('User-Agent'),
                    'session_id'  => session()->getId(),
                ]);
            } catch (\Exception $e) {
                Log::error('PageView 記錄失敗：' . $e->getMessage());
            }
        }

        return $next($request);
    }
}

