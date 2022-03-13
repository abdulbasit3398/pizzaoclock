<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Temporary;
use App\Models\WorkPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\OnlineOrderGroup;
use App\Models\OnlineOrderItem;
use App\Http\Resources\OrderHistory;
use App\Http\Resources\OnlineOrderHistory;

class OrderHistoryController extends Controller
{
    //pos paginated orders
    public function index()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups = OrderHistory::collection(OrderGroup::latest()->paginate(perPagePaginate()));
        } else {
            $submittedOrderGroups =  OrderHistory::collection(OrderGroup::where('branch_id', $user->branch_id)->latest()->paginate(perPagePaginate()));
        }
        return $submittedOrderGroups;
    }
    //pos search orders
    public function indexAll()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroupsAll = OrderHistory::collection(OrderGroup::latest()->get());
        } else {
            $submittedOrderGroupsAll =  OrderHistory::collection(OrderGroup::where('branch_id', $user->branch_id)->latest()->get());
        }
        return $submittedOrderGroupsAll;
    }
    //online all orders
    public function indexOnline()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups = OnlineOrderHistory::collection(OnlineOrderGroup::latest()->paginate(perPagePaginate()));
        } else {
            $submittedOrderGroups =  OnlineOrderHistory::collection(OnlineOrderGroup::where('branch_id', $user->branch_id)->latest()->paginate(perPagePaginate()));
        }
        return $submittedOrderGroups;
    }

    //online search orders
    public function indexAllOnline()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroupsAll = OnlineOrderHistory::collection(OnlineOrderGroup::latest()->get());
        } else {
            $submittedOrderGroupsAll =  OnlineOrderHistory::collection(OnlineOrderGroup::where('branch_id', $user->branch_id)->latest()->get());
        }
        return $submittedOrderGroupsAll;
    }

    //pos order history delete
    public function destroy(Request $request)
    {
        $orderGroup = OrderGroup::where('id', $request->id)->first();
        $items = OrderItem::where('order_group_id', $orderGroup->id)->get();
        foreach ($items as $item) {
            $item->delete();
        }
        $orderGroup->delete();
    }

    //online order history delete
    public function destroyOnline(Request $request)
    {
        $orderGroup = OnlineOrderGroup::where('id', $request->id)->first();
        $items = OnlineOrderItem::where('order_group_id', $orderGroup->id)->get();
        foreach ($items as $item) {
            $item->delete();
        }
        $orderGroup->delete();
    }
}
