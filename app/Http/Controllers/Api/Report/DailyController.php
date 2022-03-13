<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Temporary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        $branchNames = collect();
        $branchAmount = collect();
        $modifiedOrderGroups = array();
        foreach ($branches as $branch) {
            $tempAmount = 0;
            $orderGroups = OrderGroup::where('branch_id', $branch->id)->where('is_cancelled', 0)->whereDate('created_at', Carbon::today())->get();
            if (!is_null($orderGroups)) {
                foreach ($orderGroups as $group) {
                    $tempAmount = $tempAmount+ $group->total_payable;
                }
            }
            $branchAmount->push($tempAmount);
            $branchNames->push($branch->name);
            foreach ($orderGroups as $submittedOrder) {
                $temp = new Temporary;
                $temp->id = $submittedOrder->id;
                $temp->work_period_id = $submittedOrder->work_period_id;
                $temp->user_id = $submittedOrder->user_id;
                $temp->user_name = $submittedOrder->user_name;
                $temp->branch_id = $submittedOrder->branch_id;
                $theBranch = Branch::where('id', $temp->branch_id)->first();
                $temp->theBranch = $theBranch;
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
                $temp->service_charge = $submittedOrder->service_charge != null ?$submittedOrder->service_charge:0 ;
                $temp->discount = $submittedOrder->discount != null ?$submittedOrder->discount:0;
                $temp->order_bill = $submittedOrder->order_bill;
                $temp->vat = $submittedOrder->vat;
                $temp->vat_system = $submittedOrder->vat_system;
                $temp->cgst = $submittedOrder->cgst;
                $temp->sgst = $submittedOrder->sgst;
                $temp->dept_commission = $submittedOrder->dept_commission;
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
        }
        return [$branchNames, $branchAmount, $modifiedOrderGroups];
    }
}
