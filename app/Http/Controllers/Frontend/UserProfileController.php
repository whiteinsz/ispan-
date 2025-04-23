<?php

namespace App\Http\Controllers\Frontend;

use App\DataTables\FrontUserOrderDataTable;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    use ImageUploadTrait;
    // public function index(){
    //     return view('frontend.member.profile');
    // }

    public function index(FrontUserOrderDataTable $dataTable)
    {
        return $dataTable->render('frontend.member.profile');
    }

    public function profileEdit(Request $request)
    {
        // 驗證 request 的資料
        $rules = [
            'name' => 'string|max:50',
            'phone' => 'regex:/^09\d{8}$/', // 台灣手機號碼驗證
            'birthday' => 'nullable|date_format:Y-m-d', // 允許生日為 null，且格式必須正確
            'gender' => 'integer',
        ];
    
        $messages = [
            'name.string' => '姓名必須是字串。',
            'name.max' => '姓名最多只能 50 個字元。',
            'phone.regex' => '手機號碼格式錯誤，應為 09 開頭的 10 位數字。',
            'birthday.date_format' => '生日格式必須為 YYYY-MM-DD，例如：2025-03-17。',
            'gender.integer' => '性別必須是整數。',
        ];
    
        $request->validate($rules, $messages);
        
    
        // 確保使用者已登入
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', '請先登入');
        }
         /** @var \App\Models\User $user */

        $user = Auth::user();

        // 引用ImageUploadTrait的uploadImage2方法
        // 需要的參數
        // updateImage2(Request $request, $inputName, $path, $oldPath = null, $width = 300, $height = 300)

        $imagePath = $this->updateImage2($request, 'avatar', 'uploads/avatars',$user->avatar, 300, 300);
        try {
            // 使用 update() 方法來更新資料
            $user->update([
                'avatar'=>$imagePath,
                'name' => $request->name,
                'phone' => $request->phone,
                'birthday' => $request->birthday ? date('Y-m-d', strtotime($request->birthday)) : null,
                'gender' => intval($request->gender),
            ]);
    
            return redirect()->back()->with('success', '會員資料已更改');

        } catch (\Exception $e) {

            Log::error('更改失敗: ' . $e->getMessage());
            return redirect()->back()->with('error', '更改失敗，請稍後再試');
        }
    }

    public function orderDetail($id){
        $order = Order::with('orderitems.product')->findOrFail($id);
        return view('frontend.member.layouts.orderDetail',compact('order'));
    }
    public function orderUpdate(Request $request,string $id)
    {
        $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,completed,canceled'],
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();
        
        return redirect()->route('frontend.member.profile')->with('success', '更新成功');
    }
    
}
