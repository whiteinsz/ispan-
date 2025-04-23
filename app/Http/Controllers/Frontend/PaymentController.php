<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Ecpay\Sdk\Factories\Factory;
use Ecpay\Sdk\Services\UrlService;
use Ecpay\Sdk\Response\VerifiedArrayResponse;
use Ecpay\Sdk\Services\CheckMacValueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/../../../../vendor/autoload.php';

class PaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        $factory = new Factory([
            'hashKey' => env('ECPAY_HASH_KEY'),
            'hashIv'  => env('ECPAY_HASH_IV'),
        ]);
        $autoSubmitFormService = $factory->create('AutoSubmitFormWithCmvService');
        
        $input = [
            'MerchantID'        => env('ECPAY_MERCHANT_ID'),
            'MerchantTradeNo'   => $request->MerchantTradeNo,
            'MerchantTradeDate' => date('Y/m/d H:i:s'),
            'PaymentType'       => 'aio',
            'TotalAmount'       => $request->TotalAmount,
            'TradeDesc'         => UrlService::ecpayUrlEncode($request->ItemName),
            'ItemName'          => $request->ItemName,
            'ChoosePayment'     => 'ALL',
            'EncryptType'       => 1,
            // 請參考 example/Payment/GetCheckoutResponse.php 範例開發
            'ReturnURL'         => 'https://www.ecpay.com.tw/example/receive',
            'OrderResultURL'    => url('/api/fake-payment-success'),
        ];
        $action = env('ECPAY_URL');
        
        echo $autoSubmitFormService->generate($input, $action);
    }

    public function fakePaymentSuccess(Request $request)
    {
        // 模擬綠界付款回應
        $input = $request->all();
        // Log::info('Fake Payment Callback', $input);
        $order = Order::where('order_no', $input['MerchantTradeNo'])->select('id', 'status', 'updated_at')->first();
        if (isset($input['RtnCode']) && $input['RtnCode'] == '1') {
            if ($order) {
                $order->status = 'pending';  // 更新訂單狀態
                $order->updated_at = now();
                $order->save();
            }
        }
        return redirect()->route('member.profile.orderDetail',['id' => $order->id])->with('success', '結帳成功');
    }
}
