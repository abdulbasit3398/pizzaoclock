<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FoodGroup;
use App\Models\Branch;
use App\Models\FoodItem;
use App\Models\OnlineOrderItem;
use App\Models\OnlineOrderGroup;
use App\Models\FoodWithVariation;
use App\Models\PropertyItem;
use App\Models\PropertyGroup;
use App\Models\Temporary;
use App\Models\Variation;
use App\Models\WorkPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Auth;

class DeliveryUserOrderController extends Controller
{
    //alarm
    public function alarm()
    {
        $user = Auth::user();
        return count(OnlineOrderGroup::where('delivery_boy_id', $user->id)->where('is_cancelled', 0)->where('is_delivered', 0)->where('delivery_status', 'pending')->get());
    }


    //get online non delivered orders for deliveryman
    public function index()
    {
        $user = Auth::user();
        $submittedOrderGroups = OnlineOrderGroup::where('delivery_boy_id', $user->id)->where('is_cancelled', 0)->where('is_delivered', 0)->latest()->get();
        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->token = json_decode($submittedOrder->token);
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $theBranch = Branch::where('id', $temp->branch_id)->first();
            $temp->theBranch = $theBranch;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_delivered = $submittedOrder->is_delivered;
            $temp->payment_method = $submittedOrder->payment_method;
            $temp->note_to_rider = $submittedOrder->note_to_rider;
            $temp->delivery_phn_no = $submittedOrder->delivery_phn_no;
            $temp->delivery_address = $submittedOrder->delivery_address;
            $temp->time_to_deliver = $submittedOrder->time_to_deliver;
            $temp->pos_user_id =$submittedOrder->pos_user_id;
            $temp->reason_of_cancel = $submittedOrder->reason_of_cancel;
            $temp->created_at = $submittedOrder->created_at;
            //delivery boy
            $temp->delivery_boy_name = $submittedOrder->delivery_boy_name;
            $temp->delivery_boy_id = $submittedOrder->delivery_boy_id;
            $temp->delivery_status = $submittedOrder->delivery_status;
            //get order items here
            $orderedItems = OnlineOrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [customPaginate($modifiedOrderGroups), $modifiedOrderGroups];
    }

    //change status
    public function changeStatus(Request $request)
    {
        $orderGroup = OnlineOrderGroup::where('id', $request->id)->first();
        $orderGroup->delivery_status = $request->status;
        if ($request->status == "delivered") {
            $orderGroup->is_delivered = 1;
        } else {
            $orderGroup->is_delivered = 0;
        }
        $orderGroup->save();
        return $this->index();
    }


    //get online non delivered orders for deliveryman
    public function indexDelivered()
    {
        $user = Auth::user();
        $submittedOrderGroups = OnlineOrderGroup::where('delivery_boy_id', $user->id)->where('is_cancelled', 0)->where('is_delivered', 1)->latest()->get();
        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->token = json_decode($submittedOrder->token);
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $theBranch = Branch::where('id', $temp->branch_id)->first();
            $temp->theBranch = $theBranch;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_delivered = $submittedOrder->is_delivered;
            $temp->payment_method = $submittedOrder->payment_method;
            $temp->note_to_rider = $submittedOrder->note_to_rider;
            $temp->delivery_phn_no = $submittedOrder->delivery_phn_no;
            $temp->delivery_address = $submittedOrder->delivery_address;
            $temp->time_to_deliver = $submittedOrder->time_to_deliver;
            $temp->pos_user_id =$submittedOrder->pos_user_id;
            $temp->reason_of_cancel = $submittedOrder->reason_of_cancel;
            $temp->created_at = $submittedOrder->created_at;
            //delivery boy
            $temp->delivery_boy_name = $submittedOrder->delivery_boy_name;
            $temp->delivery_boy_id = $submittedOrder->delivery_boy_id;
            $temp->delivery_status = $submittedOrder->delivery_status;
            //get order items here
            $orderedItems = OnlineOrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [customPaginate($modifiedOrderGroups), $modifiedOrderGroups];
    }
}
