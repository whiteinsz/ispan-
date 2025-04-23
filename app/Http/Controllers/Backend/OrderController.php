<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\OrderDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * 顯示所有訂單
     */
    public function index(OrderDataTable $dataTable)
    {
        return $dataTable->render('admin.orders.index');
    }

    /**
     * 顯示特定訂單詳情（保留但不實作）
     */
    public function show(string $id)
    {
        //
    }

    /**
     * 建立新訂單（保留但不實作）
     */
    public function store(Request $request)
    {
        //
    }

    public function edit(string $id)
    {
        $order = Order::with('orderitems.product')->findOrFail($id);
        return view('admin.orders.edit', compact('order'));
    }
    /**
     * 更新訂單資訊，例如修改狀態
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'status' => ['required', 'in:pending,processing,shipped,completed,canceled'],
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();
        
        return redirect()->route('admin.order.index')->with('success', '更新成功');;
    }

    /**
     * 刪除訂單
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        DB::beginTransaction();
        try {
            $order->items()->delete(); // 先刪除關聯的 OrderItem
            $order->delete(); // 再刪除訂單本身
            DB::commit();
            return response(['status' => 'success', 'message' => '訂單已刪除！']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['status' => 'error', 'message' => '刪除失敗，請稍後再試！']);
        }
    }

    /**
     * 變更訂單狀態
     */
    public function changeStatus(Request $request)
    {
        $order = Order::findOrFail($request->id);
        $order->status = $request->status == 'true' ? 'completed' : 'pending';
        $order->save();

        return response(['message' => '訂單狀態更新成功！']);
    }
}