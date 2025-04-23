<?php

namespace App\DataTables;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class OrderDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
     private function getStatusInChinese($status)
    {
        $statusMap = [
            'unpaid'=>'未付款',
            'pending' => '未處理',
            'processing' => '撿貨中',
            'shipped' => '運送中',
            'completed' => '已完成',
            'canceled' => '已取消',
        ];

        return $statusMap[$status] ?? '未知狀態';
    }
    private function getStatusColor($status)
    {
        $statusColorMap = [
            'unpaid' => '#FF4500',      // **橘紅色 (更鮮豔，提醒付款)**
            'pending' => '#FFA500',     // **金黃色 (表示等待處理)**
            'processing' => '#007BFF',  // **藍色 (處理中)**
            'shipped' => '#28A745',     // **亮綠 (已出貨)**
            'completed' => '#006400',   // **深綠 (交易完成)**
            'canceled' => '#DC3545',    // **紅色 (已取消)**
        ];   

        return $statusColorMap[$status] ?? 'gray';
    }
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            // 編輯欄位
            ->editColumn('created_at', function ($order) {
                // 轉換日期格式
                return Carbon::parse($order->created_at)->setTimezone('Asia/Taipei')->format('Y-m-d H:i');
            })
            ->editColumn('updated_at', function ($order) {
                return Carbon::parse($order->updated_at)->setTimezone('Asia/Taipei')->format('Y-m-d H:i');
            })
            ->editColumn('status', function ($order) {
                $status = $this->getStatusInChinese($order->status);
                $statusColor = $this->getStatusColor($order->status);
                return "<span style='color: $statusColor;'>$status</span>";
            })
            // 
            ->addColumn('action', function($query){
                // 查看詳細按鈕
                $editBtn = "<a href='".route('admin.order.edit', $query->id)."' class='btn btn-success'><i class='fas fa-list'></i></a>";
                return $editBtn;
            })
            ->rawColumns(['action','status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Order $model): QueryBuilder
    {
        $query = $model->newQuery();

        // 判斷是否有 status 篩選條件
        if (request()->has('status') && request()->status != '') {
            $query->where('status', request()->status);
        }
    
        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('order-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->orderBy(4)
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
            Column::make('order_no')->title('訂單編號'),
            Column::make('user_id')->title('用戶 ID'),
            Column::make('status')->title('狀態'),
            Column::make('total_price')->title('總金額'),
            Column::make('created_at')->title('建立時間'),
            Column::make('updated_at')->title('更新時間'),
            Column::computed('action')->title('查看')
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
        return 'Order_' . date('YmdHis');
    }
}
