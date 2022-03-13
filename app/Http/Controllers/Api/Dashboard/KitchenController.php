<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Temporary;
use App\Models\WorkPeriod;
use App\Models\OnlineOrderItem;
use App\Models\OnlineOrderGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KitchenController extends Controller
{
    //get all non-ready orders, is_cancelled == 0
    public function index()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups= collect();
            $tempSubmittedOrderGroups = OrderGroup::where('is_ready', 0)->where('is_cancelled', 0)->orderBy('id', 'desc')->get();
            foreach ($tempSubmittedOrderGroups as $tempGroup) {
                $workPeriod = WorkPeriod::where('id', $tempGroup->work_period_id)->first();
                if ($workPeriod->ended_at == null) {
                    $submittedOrderGroups->push($tempGroup);
                }
            }
        } else {
            //get not ended work period of user's branch
            $workPeriod = WorkPeriod::where('branch_id', $user->branch_id)->where('ended_at', null)->first();
            if (!is_null($workPeriod)) {
                $submittedOrderGroups = OrderGroup::where('work_period_id', $workPeriod->id)->where('is_ready', 0)->where('is_cancelled', 0)->orderBy('id', 'desc')->get();
            }
        }
        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->customer_id = $submittedOrder->customer_id;
            $temp->customer_name = $submittedOrder->customer_name;
            $temp->table_id = $submittedOrder->table_id;
            $temp->table_name = $submittedOrder->table_name;
            $temp->waiter_id = $submittedOrder->waiter_id;
            $temp->waiter_name = $submittedOrder->waiter_name;
            $temp->dept_tag_id = $submittedOrder->dept_tag_id;
            $temp->dept_tag_name = $submittedOrder->dept_tag_name;
            $temp->token = json_decode($submittedOrder->token);
            $temp->total_guest = $submittedOrder->total_guest;
            $temp->service_charge = $submittedOrder->service_charge;
            $temp->discount = $submittedOrder->discount;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->bill_distribution = json_decode($submittedOrder->bill_distribution);
            $temp->paid_amount = $submittedOrder->paid_amount;
            $temp->return_amount = $submittedOrder->return_amount;
            $temp->is_paid = $submittedOrder->is_paid;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_settled = $submittedOrder->is_settled;
            $temp->is_ready = $submittedOrder->is_ready;
            //get order items here
            $orderedItems = OrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [$modifiedOrderGroups];
    }


    //accept an order
    public function accept(Request $request)
    {
        $orderGroup = OrderGroup::where('id', $request->id)->first();
        if ($orderGroup->is_cancelled != 1) {
            if ($orderGroup->is_accepted == 1) {
                $orderGroup->is_accepted = 0;
            } else {
                $orderGroup->is_accepted = 1;
            }
            $orderGroup->save();
        } else {
            abort(404);
        }
    }

    //mark an item ready
    public function itemReady(Request $request)
    {
        $orderGroup = OrderGroup::where('id', $request->orderGroupId)->first();
        if ($orderGroup->is_cancelled != 1) {
            $item = OrderItem::where('id', $request->id)->first();
            if ($item->is_cooking == 1) {
                $item->is_cooking = 0;
            } else {
                $item->is_cooking = 1;
            }
            $item->save();
            $orderGroup->is_accepted = 1;
            $orderGroup->save();
        } else {
            abort(404);
        }
    }

    //mark order and all items ready
    public function orderReady(Request $request)
    {
        $orderGroup = OrderGroup::where('id', $request->id)->first();
        if ($orderGroup->is_cancelled != 1) {
            $items = OrderItem::where('order_group_id', $orderGroup->id)->get();
            foreach ($items as $item) {
                $item->is_cooking = 1;
                $item->is_ready = 1;
                $item->save();
            }
            $orderGroup->is_accepted = 1;
            $orderGroup->is_ready = 1;
            $orderGroup->save();
        } else {
            abort(404);
        }
    }

    //get all non-ready orders, is_cancelled == 0
    public function indexOnline()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups= collect();
            $tempSubmittedOrderGroups = OnlineOrderGroup::where('is_ready', 0)->where('is_cancelled', 0)->where('is_accepted', 1)->orderBy('id', 'desc')->get();
            foreach ($tempSubmittedOrderGroups as $tempGroup) {
                $workPeriod = WorkPeriod::where('id', $tempGroup->work_period_id)->first();
                if ($workPeriod->ended_at == null) {
                    $submittedOrderGroups->push($tempGroup);
                }
            }
        } else {
            //get not ended work period of user's branch
            $workPeriod = WorkPeriod::where('branch_id', $user->branch_id)->where('ended_at', null)->first();
            if (!is_null($workPeriod)) {
                $submittedOrderGroups = OnlineOrderGroup::where('work_period_id', $workPeriod->id)->where('is_ready', 0)->where('is_accepted', 1)->where('is_cancelled', 0)->orderBy('id', 'desc')->get();
            }
        }
        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->customer_id = $submittedOrder->customer_id;
            $temp->customer_name = $submittedOrder->customer_name;
            $temp->table_id = $submittedOrder->table_id;
            $temp->table_name = $submittedOrder->table_name;
            $temp->waiter_id = $submittedOrder->waiter_id;
            $temp->waiter_name = $submittedOrder->waiter_name;
            $temp->dept_tag_id = $submittedOrder->dept_tag_id;
            $temp->dept_tag_name = $submittedOrder->dept_tag_name;
            $temp->token = $submittedOrder->token;
            $temp->total_guest = $submittedOrder->total_guest;
            $temp->service_charge = $submittedOrder->service_charge;
            $temp->discount = $submittedOrder->discount;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->bill_distribution = json_decode($submittedOrder->bill_distribution);
            $temp->paid_amount = $submittedOrder->paid_amount;
            $temp->return_amount = $submittedOrder->return_amount;
            $temp->is_paid = $submittedOrder->is_paid;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_accepted_by_kitchen = $submittedOrder->is_accepted_by_kitchen;
            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_settled = $submittedOrder->is_settled;
            $temp->is_ready = $submittedOrder->is_ready;
            $temp->created_at = $submittedOrder->created_at;
            //get order items here
            $orderedItems = OnlineOrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [$modifiedOrderGroups];
    }

    //accept an order
    public function acceptOnline(Request $request)
    {
        $orderGroup = OnlineOrderGroup::where('id', $request->id)->first();
        if ($orderGroup->is_cancelled != 1) {
            if ($orderGroup->is_accepted_by_kitchen == 1) {
                $orderGroup->is_accepted_by_kitchen = 0;
            } else {
                $orderGroup->is_accepted_by_kitchen = 1;
            }
            $orderGroup->save();
        } else {
            abort(404);
        }
    }


    //mark an item ready
    public function itemReadyOnline(Request $request)
    {
        $orderGroup = OnlineOrderGroup::where('id', $request->orderGroupId)->first();
        if ($orderGroup->is_cancelled != 1) {
            $item = OnlineOrderItem::where('id', $request->id)->first();
            if ($item->is_cooking == 1) {
                $item->is_cooking = 0;
            } else {
                $item->is_cooking = 1;
            }
            $item->save();
            $orderGroup->is_accepted_by_kitchen = 1;
            $orderGroup->save();
        } else {
            abort(404);
        }
    }

    //mark order and all items ready
    public function orderReadyOnline(Request $request)
    {
        $orderGroup = OnlineOrderGroup::where('id', $request->id)->first();
        if ($orderGroup->is_cancelled != 1) {
            $items = OnlineOrderItem::where('order_group_id', $orderGroup->id)->get();
            foreach ($items as $item) {
                $item->is_cooking = 1;
                $item->is_ready = 1;
                $item->save();
            }
            $orderGroup->is_accepted_by_kitchen = 1;
            $orderGroup->is_ready = 1;
            $orderGroup->save();
        } else {
            abort(404);
        }
    }
}
