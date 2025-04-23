<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\CustomerListDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerListController extends Controller
{
    
    public function index(CustomerListDataTable $dataTable)
    {
        
        return $dataTable->render('admin.customer-list.index');
    }

    //更改會員狀態
    public function changeStatus(Request $request)
    {
        $customer = User::where('id', $request->id)->firstOrFail();
        $customer->status = $request->status == 'true' ? 1 : 0;
        $customer->save();

        return response(['message' => '會員狀態已更新!']);
    }

    public function test(){
        return response()->json(User::all());
    }

}
