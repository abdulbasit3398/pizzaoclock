<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Temporary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        $branches = Branch::all();
        $branchNamesToday = collect();
        $branchAmountToday = collect();

        $branchNamesLastMonths = collect();
        $branchAmountLastMonths = collect();

        //last month
        $lastMonthStart = Carbon::today()->subMonth(1)->startOfMonth();
        $lastMonthEnd = Carbon::today()->subMonth(1)->endOfMonth();

        foreach ($branches as $branch){
            //today
            $tempAmount = 0;
            $orderGroups = OrderGroup::where('branch_id',$branch->id)->where('is_cancelled',0)->whereDate('created_at', Carbon::today())->get();
            if(!is_null($orderGroups)){
                foreach ($orderGroups as $group){
                    $tempAmount = $tempAmount+ $group->total_payable;
                }
            }
            $branchAmountToday->push($tempAmount);
            $branchNamesToday->push($branch->name);

            //last month branch wise
            $tempAmount3 = 0;
            $orderGroups = OrderGroup::where('branch_id',$branch->id)->where('is_cancelled',0)
                ->where('created_at', '>=',$lastMonthStart)
                ->where('created_at', '<',$lastMonthEnd)->get();
            if(!is_null($orderGroups)){
                foreach ($orderGroups as $group){
                    $tempAmount3 = $tempAmount3+ $group->total_payable;
                }
            }
            $branchAmountLastMonths->push($tempAmount3);
            $branchNamesLastMonths->push($branch->name);
        }

        $foodGroups = FoodGroup::all();
        $foodGroupNames = collect();
        $foodGroupAmount = collect();
        foreach ($foodGroups as $foodGroup){
            if($foodGroup->is_cancelled == 0){
                $tempAmount2 = 0;
                $orderItems = OrderItem::where('food_group', $foodGroup->name)->whereDate('created_at', Carbon::today())->get();
                if(!is_null($orderItems)){
                    foreach ($orderItems as $item){
                        $tempAmount2 = $tempAmount2+ $item->price;
                    }
                }
                $foodGroupAmount->push($tempAmount2);
                $foodGroupNames->push($foodGroup->name);
            }
        }

        $foodItems = FoodItem::all();
        $foodItemNames = collect();
        $foodItemAmount = collect();
        foreach ($foodItems as $foodItem){
            $tempAmount4 = 0;
            $orderItems = OrderItem::where('food_item', $foodItem->name)
                            ->where('created_at', '>=',$lastMonthStart)
                            ->where('created_at', '<', $lastMonthEnd)->get();
            if(!is_null($orderItems)){
                foreach ($orderItems as $item){
                    $group = OrderGroup::where('id',$item->order_group_id)->where('is_cancelled',0)->first();
                    if(!is_null($group)){
                        $tempAmount4 = $tempAmount4+ $item->price;
                    }
                }
            }
            $foodItemAmount->push($tempAmount4);
            $foodItemNames->push($foodItem->name);
        }

        return [$branchNamesToday, $branchAmountToday, $branchNamesLastMonths, $branchAmountLastMonths, $foodGroupNames, $foodGroupAmount, $foodItemNames,$foodItemAmount];
    }
}
