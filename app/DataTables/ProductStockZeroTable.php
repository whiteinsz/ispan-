<?php

namespace App\DataTables;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ProductStockZeroTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function($query){
                $editBtn = "<a href='".route('admin.product.productList.edit', $query->id) . '?redirect_from=stock-zero' . "' class='btn btn-primary'><i class='far fa-edit'></i></a>";

                $deleteBtn = "<a href='".route('admin.product.productList.destroy', $query->id)."' class='btn btn-danger ml-2 delete-item'><i class='far fa-trash-alt'></i></a>";

                return $editBtn.$deleteBtn;
            })
            ->addColumn('status', function ($query) {
                // 根據狀態生成開關按鈕
                $checked = $query->status == '1' ? 'checked' : '';
                $strHTML = "" ; 
                if($checked =='checked'){
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
            ->addColumn('image', function($query){
                // 圖片區域
                return "<img width='100px' height='100px' style='object-fit: contain;' src='".asset($query->thumb_image)."' ></img>";
            })
            ->addColumn('stock_status', function ($query) {
                if ($query->qty <= 0) {
                    return '<span class="badge bg-danger fs-6">已缺貨</span>';
                } elseif ($query->qty < ($query->stock_warning_threshold ?? 50)) {
                    return '<span class="badge bg-warning text-dark fs-6">即將缺貨</span>';
                } else {
                    return '<span class="badge bg-success fs-6">庫存正常</span>';
                }
            })
            ->rawColumns(['image','action', 'status','stock_status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        return $model->where(function ($query) {
            $query->where('qty', 0)
                  ->orWhereColumn('qty', '<', 'stock_warning_threshold');
        })->with('category')->newQuery();
        
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('product-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    // ->dom('Bfrtip')
                    ->orderBy(0)
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->title('產品編號')->addClass('text-center'),
            Column::make('image')->title('圖片')->height(100),
            Column::make('name')->title('商品名稱')->addClass(''),
            Column::make('price')->title('價格')->width(100)->addClass('text-center'),
            Column::make('qty')->title('庫存')->addClass('text-center'),
            Column::computed('stock_status')
            ->title('庫存狀態')
            ->exportable(false)
            ->printable(true)
            ->width(120)
            ->addClass('text-center'),
            Column::computed('category')->title('產品類別')->width(100)->addClass('')// 使用 computed 來自定義顯示
                ->data('category.name') // 指定 category 的 name 欄位
                ->name('category.name') // 這裡也要設置 name 來確保排序和搜尋可用
                ->orderable(true)
                ->searchable(true),
            Column::make('status')->width(100)->addClass('text-center ')->title('上/下架'),
            Column::computed('action')->title('編輯/刪除')
            ->exportable(false)
            ->printable(false)
            ->width(150)
            ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Product_' . date('YmdHis');
    }
}
