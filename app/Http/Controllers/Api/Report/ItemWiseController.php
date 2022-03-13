<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ItemWiseController extends Controller
{
    //get by selected dates
    public function filter(Request $request){
        $foodItems = FoodItem::all();
        $foodItemNames = collect();
        $foodItemAmount = collect();
        $modifiedOrderGroups = array();
        foreach ($foodItems as $foodItem){
            $tempAmount = 0;
            $orderItems = OrderItem::where('food_item', $foodItem->name)->where('created_at', '>=', Carbon::parse($request->fromDate))
                ->where('created_at', '<', Carbon::parse($request->toDate)->addDay(1))->get();
            if(!is_null($orderItems)){
                foreach ($orderItems as $item){
                    $group = OrderGroup::where('id',$item->order_group_id)->where('is_cancelled',0)->first();
                    if(!is_null($group)){
                        $tempAmount = $tempAmount+ $item->price;
                        array_push($modifiedOrderGroups, $item);
                    }
                }
            }
            $foodItemAmount->push($tempAmount);
            $foodItemNames->push($foodItem->name);
        }
        return [$foodItemNames, $foodItemAmount, $modifiedOrderGroups];
    }
}
