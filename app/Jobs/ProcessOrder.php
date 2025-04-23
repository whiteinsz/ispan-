<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shopping_cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $cartTotal;
    protected $cartItems;
    protected $requestData;

    public function __construct($userId, $cartTotal, $cartItems, $requestData)
    {
        $this->userId = $userId;
        $this->cartTotal = $cartTotal;
        $this->cartItems = $cartItems;
        $this->requestData = $requestData;
    }

    public function handle()
    {
        DB::beginTransaction();
        try {
            $user = User::find($this->userId);
            if (!$user) {
                throw new \Exception("找不到用戶");
            }
            $orderNo = 'OD' . strtoupper(Str::random(10)); // 例如: OD8FJ2KPLN1
            // 創建訂單
            $order = Order::create([
                'user_id' => $user->id,
                'order_no' => $orderNo, // 這行確保 order_no 被寫入
                'total_price' => $this->cartTotal,
                'status' => 'unpaid',
                'receivers' => $this->requestData['receiver'],
                'phone' => $this->requestData['phone'],
                'address' => $this->requestData['address'],
                'zipcode' => $this->requestData['zipcode'],
            ]);

            foreach ($this->cartItems as $item) {
                // **加鎖避免超賣**
                $product = Product::where('id', $item->product->id)->lockForUpdate()->first();
                
                if ($item->quantity > $product->qty) {
                    throw new \Exception("產品 {$item->product->name} 庫存不足");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                ]);

                // **扣庫存**
                $product->decrement('qty', $item->quantity);
            }

            // 清空購物車
            Shopping_cart::where('user_id', $this->userId)->delete();

            DB::commit();
            Log::info("訂單處理完成: 訂單ID {$order->id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('訂單處理失敗: ' . $e->getMessage());
        }
    }
    
    // 用tinker 測試超賣 
    // use App\Jobs\ProcessOrder;
    // use App\Models\User;
    // use App\Models\Shopping_cart;
    // use Illuminate\Support\Facades\DB;

    // $user = User::find(1); // 測試用的用戶
    // $cartItems = Shopping_cart::where('user_id', $user->id)->with(['product:id,name,price,qty'])->get();
    // $cartTotal = $cartItems->sum(fn($item) => $item->quantity * $item->product->price);
    // $requestData = [
    //     'receiver' => '測試用戶',
    //     'phone' => '0912345678',
    //     'address' => '台北市測試地址',
    //     'zipcode' => '100',
    // ];

    // // **同時發送多個請求（模擬高併發）**
    // foreach (range(1, 10) as $i) {
    //     ProcessOrder::dispatch($user->id, $cartTotal, $cartItems, $requestData);
    // }

}

