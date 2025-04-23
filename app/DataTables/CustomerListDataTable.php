<?php

namespace App\DataTables;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CustomerListDataTable extends DataTable // 修正類別名稱
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('status', function ($query) {
                // 根據狀態生成開關按鈕
                $checked = $query->status ? 'checked' : '';
                $strHTML = "" ; 
                if($checked){
                    $strHTML = '<span class="cform-check-label text-success">已啟用</span>';
                }else{
                    $strHTML = '<span class="cform-check-label text-danger">已停用</span>';
                };
                $button = 
                '
                    <label class="form-check form-switch mt-2">
                        <input type="checkbox" ' . $checked . ' name="custom-switch-checkbox" data-id="' . $query->id . '" class="form-check-input change-status">
                        <span class="cform-check-label"></span>
                        '.$strHTML.'
                    </label>
                ';
                return $button;
            })
            ->rawColumns(['status']) // 允許渲染 HTML
            ->setRowId('id'); // 設置行 ID
    }

    /**
     * 取得資料表的查詢來源。
     */
    public function query(User $model): QueryBuilder
    {
        $query = $model->newQuery()->select('id', 'name', 'email', 'status', 'role')->where('role', 'user');
        return $query;
    }
    

    /**
     * 可選方法：如果你想使用 HTML 建構器。
     */
    public function html()
    {
        return $this->builder()
                    ->setTableId('customerlist-table') // 設置表格 ID
                    ->columns($this->getColumns()) // 設置欄位
                    ->minifiedAjax() // 使用簡化的 AJAX
                    ->orderBy(0) // 預設排序欄位
                    ->selectStyleSingle() // 啟用單選模式
                    ->buttons([ // 添加按鈕
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * 取得資料表欄位定義。
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->title('會員編號'), // 明確指定 'member_id'
            Column::make('name')->title('姓名'),
            Column::make('email')->title('電子郵件'),
            Column::make('status')->title('狀態'),
        ];
    }
    

    /**
     * 取得匯出檔案的名稱。
     */
    protected function filename(): string
    {
        return 'CustomerList_' . date('YmdHis'); // 檔案名稱加上時間戳
    }
}