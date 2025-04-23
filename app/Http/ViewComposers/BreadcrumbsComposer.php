<?php
namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BreadcrumbsComposer
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function compose(View $view)
    {
        // 取得當前 Route 名稱
        $routeName = Route::currentRouteName();

        // 取得當前的 Request (方便拿動態參數)
        $request = request();

        // 定義 Route 對應的名稱
        $breadcrumbs = [
            'admin.dashboard.index' => ['Home'],
            'admin.customer.index' => ['Home' => route('admin.dashboard.index'), '會員管理'],
            'admin.order.index' => ['Home' => route('admin.dashboard.index'), '訂單管理'],
            'admin.product.category.index' => ['Home' => route('admin.dashboard.index'), '產品管理' => '#', '產品類別'],
            'admin.product.productList.index' => ['Home' => route('admin.dashboard.index'), '產品管理' => '#', '產品列表'],
            'admin.htmlContent.footer_info.index' => ['Home' => route('admin.dashboard.index'), '網頁管理' => '#', '頁尾資訊管理'],
            'admin.htmlContent.footer_social.index' => ['Home' => route('admin.dashboard.index'), '網頁管理' => '#', '頁尾社群管理'],
            
            // 'order.index' => ['Home' => route('dashboard.index'), '訂單管理'],
        ];
            // **動態麵包屑** - 讓最後一層的標題來自 Controller
        if ($routeName === 'admin.product.productList' && $request->route('product')) {
            $product = $request->route('product'); // 取得 Product Model
            $breadcrumbs[$routeName] = [
                'Home' => route('admin.dashboard.index'),
                '產品管理' => '#',
                '產品列表' => route('admin.product.productList.index'),
                $product->name => request()->url(), // 產品名稱當作最後一層
            ];
        }

        if ($routeName === 'admin.customer.show' && $request->route('customer')) {
            $customer = $request->route('customer'); // 取得 Customer Model
            $breadcrumbs[$routeName] = [
                'Home' => route('admin.dashboard.index'),
                '會員管理' => route('admin.customer.index'),
                $customer->name => request()->url(), // 會員名稱當作最後一層
            ];
        }

        // 取得對應的麵包屑，如果沒有則回傳 Home
        $breadcrumbLinks = $breadcrumbs[$routeName] ?? ['Home'];

        $view->with('breadcrumbs', $breadcrumbLinks);
    }
}
