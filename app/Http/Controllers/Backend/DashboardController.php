<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    
    public function index()
    {
        
        return view('admin.dashboard.dashboard');
    }

    // 取得近期註冊會員
    public function getMonthlyNewUsers(Request $request)
    {
        $months = $request->query('months', 6); // 預設 6 個月
        $startDate = Carbon::now()->subMonths($months);

        // 依月份分組計算會員數
        $usersByMonth = User::where('created_at', '>=', $startDate)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('count', 'month');
        $count = User::where('created_at', '>=', $startDate)->count(); // 計算會員數
        $startColor = "#FFC106";
        $endColor = "rgba(255, 193, 6, 0.1)";
        $hoverColor = '#FFC106';
        $backgroundColor="#FFC106";

        return response()->json([
            'chart_type'=>"line",
            'labels' => $usersByMonth->keys(),
            'data'   => $usersByMonth->values(),
            // 'startColor' =>$startColor,
            // 'endColor' => $endColor,
            'backgroundColors' => $backgroundColor, // ✅ 指定不同的顏色
            'hoverColor' => $hoverColor,
            'count' => $count
        ]);
    }
    // 取得新訂單
    public function getMonthlyOrders(Request $request)
    {
        // 計算所有 "已完成" 訂單數量
        $completedOrders = Order::where('status', 'completed')->count();

        // 計算所有 "未完成" 訂單數量 (包含 pending, processing 等)
        $pendingOrders = Order::where('status', 'pending')->count();
        $shippedOrders = Order::where('status', 'shipped')->count();
        $processingOrders = Order::where('status', 'processing')->count();

        $startColor = "#0DCAF0";
        $endColor = "rgba(255, 193, 6, 0.1)";
        $hoverColor = "#D68910";

        return response()->json([
            'chart_type' => 'pie',
            'labels' => ['未處理','撿貨中','運送中','已完成'],
            'data' => [$pendingOrders,$processingOrders,$shippedOrders,$completedOrders], // Pie 需要陣列格式
            // 'startColor' => $startColor,
            // 'endColor' => $endColor,
            'backgroundColors' => ['#DC3545', '#FFC107', '#17A2B8', '#28A745',], // ✅ 紅、黃、藍、綠
            'hoverColors' => [ '#C82333', '#E0A800', '#138496','#218838',], // ✅ 滑鼠懸停時的顏色
            'count'=>$pendingOrders,
        ]);
    }

    
    public function getInventory(Request $request)
    {
        $months = $request->query('months', 6); // 預設查詢過去 6 個月的庫存情況
        $startDate = Carbon::now()->subMonths($months);

        // 依月份分組計算庫存為 0 的商品數量
        $productsByMonth = Product::where('updated_at', '>=', $startDate)
            ->where('qty', 0) // 只查詢庫存為 0 的商品
            ->selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->pluck('count', 'month');

        // 總庫存為 0 的商品數量
        $count = Product::where('updated_at', '>=', $startDate)
            ->where('qty', 0)
            ->orWhereColumn('qty', '<', 'stock_warning_threshold')
            ->count();

        // 設定顏色
        $startColor = "#198754"; // 顏色從綠色開始
        $endColor = "rgba(255, 193, 6, 0.1)"; // 顏色結束為黃色
        $hoverColor = "#D68910"; // 滑鼠懸停顏色

        return response()->json([
            'labels' => $productsByMonth->keys(), // 產品分組的月份
            'data'   => $productsByMonth->values(), // 各月庫存為 0 的商品數量
            'startColor' =>$startColor,
            'endColor' => $endColor,
            'hoverColor' => $hoverColor,
            'count' => $count // 總庫存為 0 的商品數量
        ]);
    }

    public function getMonthlyReports(Request $request)
    {
        $sevenDaysAgo = Carbon::today()->subDays(6);
        $tomorrow = Carbon::tomorrow(); // 包含今天整天的數據

        $visitsByDay = PageView::whereBetween('created_at', [$sevenDaysAgo, $tomorrow])
            ->where('url', 'http://127.0.0.1:8000') // 限制 url 為特定網址
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        
        // 確保所有日期都有數據（即使當天沒人瀏覽，也要填 0）
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = $tomorrow->copy()->subDays($i)->toDateString();
            $dates[$date] = $visitsByDay[$date] ?? 0;
        }

        // 取得所有瀏覽總人次（7 天內總和）
        $totalVisits = array_sum($dates->values()->toArray());

        return response()->json([
            'chart_type' => "line",
            'labels' => $dates->keys(),   // X 軸：日期
            'data'   => $dates->values(), // Y 軸：每天的瀏覽人次
            'count' => $totalVisits, // 總人次
            'backgroundColors' => "#DC3445",
            'hoverColor' => "#DC3445",
        ]);
    }


}
