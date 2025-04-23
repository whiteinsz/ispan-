<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Footer_info;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;

class Footer_infoController extends Controller
{
    use ImageUploadTrait;
    public function index(){
        $footer_infos = Footer_info::first();
        return view("admin.htmlContent.footer.footer_info.index",compact('footer_infos'));
    }
    public function update(Request $request)
    {
        $request->validate([
            'logo' => ['nullable', 'image', 'max:3000'],
            'phone' => [ 'max:200'],
            'email' => [ 'email', 'max:200'],
            'address' => [],
            'copyright' => [],
        ]);
    
        // 取得 Footer_info 的第一筆資料
        $footerInfo = Footer_info::first();
    
        if ($footerInfo) {
            $imagePath = $this->updateImage($request, 'logo', 'uploads', $footerInfo->logo);

            $footerInfo->logo = empty(!$imagePath) ? $imagePath : $footerInfo->logo;
            // 更新資料
            $footerInfo->phone = $request->phone;
            $footerInfo->email = $request->email;
            $footerInfo->address = $request->address;
            $footerInfo->copyright = $request->copyright;
            $footerInfo->save();
            
            flash()->success('更新成功!');
        } else {
            flash()->error('無法找到頁尾資訊!');
        }
    
        return redirect()->route('admin.htmlContent.footer_info.index'); // 重新導向到頁尾管理頁面
    }
    
}
