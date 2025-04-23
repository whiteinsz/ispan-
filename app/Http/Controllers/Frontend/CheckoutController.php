<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOrder;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shopping_cart;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        $rules = [
            'receiver' => 'required|string|max:50',
            'email' => 'required|email',
            'phone' => ['required', 'regex:/^09\d{8}$/'],
        ];

        if ($request->has('shipping') && $request->shipping === 'blackCat') {
            $rules['address'] = 'required|string|max:100';
        } else {
            $rules['address'] = 'nullable|string|max:100';
        }

        $messages = [
            'receiver.required' => '請輸入收件者姓名',
            'email.required' => '請輸入電子信箱',
            'phone.required' => '請輸入手機號碼',
            'phone.regex' => '請輸入正確的手機號碼（09開頭，共10碼）',
            'address.required' => '請輸入詳細住址',
        ];

        $request->validate($rules, $messages);

        $user = Auth::user();
        $cartTotal = $request->input('cart_total');
        $cartItems = Shopping_cart::where('user_id', $user->id)->with(['product:id,name,price,qty'])->get();
        

        if ($cartItems->isEmpty()) {
            return redirect()->back()->with('error', '購物車是空的');
        }

        // **推送 Job 進隊列**
        ProcessOrder::dispatch($user->id, $cartTotal, $cartItems, $request->all());

        return redirect(route('member.profile') . '#order')->with('success', '訂單正在處理中，請稍後');

    }

}
