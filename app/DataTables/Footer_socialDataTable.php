<?php

namespace App\DataTables;

use App\Models\Footer_social;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class Footer_socialDataTable extends DataTable
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
                $editBtn = "<a href='".route('admin.htmlContent.footer_social.edit', $query->id)."' class='btn btn-primary'><i class='far fa-edit'></i></a>";
                return $editBtn;
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
            ->rawColumns(['action', 'status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Footer_social $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('footer_social-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy(1)
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
            Column::make('name')->title('名稱'),
            Column::make('url')->title('網址'),
            Column::make('status')->width(120)->addClass('text-center ')->title('啟用/停用'),
            Column::computed('action')
            ->title('編輯')
            ->exportable(false)
            ->printable(false)
            ->width(60)
            ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Footer_social_' . date('YmdHis');
    }
}
