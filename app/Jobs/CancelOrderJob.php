<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelOrderJob implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * 建構子，接收要取消的訂單
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * 執行 Job
     */

    public function handle()
    {
        DB::transaction(function () {
            // 重新查詢訂單，確保最新狀態，並鎖住這筆訂單
            $order = Order::lockForUpdate()->find($this->order->id);

            if ($order && $order->status === 'unpaid') {
                // 更新訂單狀態為已取消
                $order->status = 'canceled';
                $order->save();

                // 回補庫存（使用 increment 避免 race condition）
                foreach ($order->orderitems as $item) {
                    $item->product?->increment('qty', $item->quantity);
                }

                Log::info("訂單 {$order->id} 已取消，庫存已補回。");
            }
        });
    }
}
