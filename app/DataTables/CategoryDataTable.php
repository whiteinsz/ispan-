<?php

namespace App\DataTables;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CategoryDataTable extends DataTable
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
                $editBtn = "<a href='".route('admin.product.category.edit', $query->id)."' class='btn btn-primary'><i class='far fa-edit'></i></a>";
                $deleteBtn = "<a href='".route('admin.product.category.destroy', $query->id)."' class='btn btn-danger ml-2 delete-item'><i class='far fa-trash-alt'></i></a>";

                return $editBtn.$deleteBtn;
            })
            // ->addColumn('icon', function($query){
            //     return '<i style="font-size:40px" class="'.$query->icon.'"></i>';
            // })
            ->addColumn('status', function($query){
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
            ->rawColumns(['icon', 'action', 'status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Category $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('category-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
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
            Column::make('id')->title('編號')->width(100)->addClass('text-center'),
            // icon暫時不啟用
            // Column::make('icon')->width(300),
            Column::make('name')->title('產品分類')->addClass('h4'),
            Column::make('status')->width(200),
            Column::computed('action')
            ->exportable(false)
            ->printable(false)
            ->width(200)
            ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Category_' . date('YmdHis');
    }
}
