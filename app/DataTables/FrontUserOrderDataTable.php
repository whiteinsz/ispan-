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
use Illuminate\Support\Facades\Auth;

class FrontUserOrderDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
     private function getStatusInChinese($status)
    {
        $statusMap = [
            'unpaid' =>'未付款',
            'pending' => '已付款',
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
                $editBtn = "<a href='".route('member.profile.orderDetail', $query->id)."'  class='btn btn_addToCart'><i class='fas fa-list'></i></a>";
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
        // 取得目前登入的使用者
         $user = Auth::user();
        // 判斷是否有 status 篩選條件
        if (request()->has('status') && request()->status != '') {
            $query->where('status', request()->status);
        }
        
        // 若需要根據目前登入的使用者篩選訂單（假設每個訂單有 user_id 欄位）
        if ($user) {
            $query->where('user_id', $user->id); // 假設訂單模型中有 user_id 欄位
        }

    
        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('frotnUserOrder-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->orderBy('3')
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ])->language([
                        'sProcessing'     => '處理中...',
                        'sLengthMenu'     => '每頁顯示 _MENU_ 筆資料',
                        'sZeroRecords'    => '沒有符合的記錄',
                        'sInfo'           => '顯示第 _START_ 至 _END_ 筆記錄，共 _TOTAL_ 筆',
                        'sInfoEmpty'      => '顯示第 0 至 0 筆記錄，共 0 筆',
                        'sInfoFiltered'   => '(從 _MAX_ 筆記錄中篩選)',
                        'sSearch'         => '搜尋:',
                        'oPaginate'       => [
                            'sFirst'    => '首頁',
                            'sPrevious' => '上一頁',
                            'sNext'     => '下一頁',
                            'sLast'     => '最後一頁',
                        ],
                        'select'=> [
                            'rows'=> '已選取 %d 行',
                        ]
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('order_no')->title('訂單編號'),
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
