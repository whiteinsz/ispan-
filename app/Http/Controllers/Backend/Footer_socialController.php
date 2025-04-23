<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Footer_socialDataTable;
use App\Http\Controllers\Controller;
use App\Models\Footer_social;
use Illuminate\Http\Request;

class Footer_socialController extends Controller
{
    public function index(Footer_socialDataTable $dataTable){
        return $dataTable->render('admin.htmlContent.footer.footer_social.index');
    }

    public function edit(string $id)
    {
        $footer_social = Footer_social::findOrFail($id);
        return view('admin.htmlContent.footer.footer_social.edit', compact('footer_social'));
    }

    public function show(){
        
    }
    public function update(Request $request ,string $id)
    {
        $request->validate([
            'icon' => ['required','max:200'],
            'name' => [ 'required','max:200'],
            'url' => [ 'required','max:200'],
            'status' => [ 'required'],
        ]);
    
        $footer_social = Footer_social::findOrFail($id);
        if ($footer_social) {
            // 更新資料
            $footer_social->name = $request->name;
            $footer_social->url = $request->url;
            $footer_social->icon = $request->icon;
            $footer_social->status = $request->status;
            $footer_social->save();
    
            flash()->success('更新成功!');
        } else {
            flash()->error('無法找到頁尾資訊!');
        }
    
        return redirect()->route('admin.htmlContent.footer_social.index'); // 重新導向到頁尾管理頁面
    }
    
    public function changeStatus(Request $request)
    {
        $footer_social = Footer_social::findOrFail($request->id);
        $footer_social->status = $request->status == 'true' ? 1 : 0;
        $footer_social->save();

        return response(['message' => '更新成功']);
    }
}
