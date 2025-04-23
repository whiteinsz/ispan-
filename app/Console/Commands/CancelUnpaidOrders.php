<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\CancelOrderJob;

class CancelUnpaidOrders extends Command
{
    // 設定命令名稱
    protected $signature = 'orders:cancel-unpaid';

    // 設定命令描述
    protected $description = 'Cancel unpaid orders that have been pending for more than 10 minutes';

    // 執行命令的邏輯
    public function handle()
    {
        $now = Carbon::now();
        $orders = Order::where('status', 'unpaid') // 未付款狀態
                        ->where('created_at', '<=', $now->subMinutes(10)) // 超過 10 分鐘
                        ->get();
    
        foreach ($orders as $order) {
            CancelOrderJob::dispatch($order);
        }
    }
    
}
