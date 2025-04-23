<?php


namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\SidebarController;

class SidebarComposer
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function compose(View $view)
    {
        // 取得側邊欄選單資料
        $sidebar = [
            'dashboard' => $this->request->routeIs('admin.dashboard.index'),
            'customer' =>  $this->request->routeIs('admin.customer.index'),
            'product' =>  $this->request->is('admin/product*'),
            'category' =>  $this->request->is('admin/category*'),
            'htmlContent' =>  $this->request->is('admin/htmlContent*'),
            'order' =>  $this->request->is('admin/order*'),
        ];

        // 傳遞到視圖
        $view->with('sidebar', $sidebar);
    }
}
