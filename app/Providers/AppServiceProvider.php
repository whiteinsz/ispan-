<?php

namespace App\Providers;

use App\Http\ViewComposers\BreadcrumbsComposer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\ViewComposers\SidebarComposer;
use App\Models\Category;
use App\Models\Footer_info;
use App\Models\Footer_social;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('admin.layouts.app', SidebarComposer::class);
        View::composer('*', BreadcrumbsComposer::class);
        $categories_name = Category::where('status', 1)
                           ->pluck('name', 'id');

        $footer_infos = Footer_info::get();
        $footer_socias = Footer_social::get();
    
        // 分享資料給所有視圖
        view()->share('categories_name', $categories_name);
        view()->share('footer_infos', $footer_infos);
        view()->share('footer_socias', $footer_socias);
    }
}
