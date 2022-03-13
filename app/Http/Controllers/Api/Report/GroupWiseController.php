<?php

namespace App\Http\Controllers\Api\Report;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FoodGroup;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Temporary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupWiseController extends Controller
{
    //get by selected dates
    public function filter(Request $request){
        $foodGroups = FoodGroup::all();
        $foodGroupNames = collect();
        $foodGroupAmount = collect();
        $modifiedOrderGroups = array();
        foreach ($foodGroups as $foodGroup){
            if($foodGroup->is_cancelled == 0){
                $tempAmount = 0;
                $orderItems = OrderItem::where('food_group', $foodGroup->name)->where('created_at', '>=', Carbon::parse($request->fromDate))
                    ->where('created_at', '<', Carbon::parse($request->toDate)->addDay(1))->get();
                if(!is_null($orderItems)){
                    foreach ($orderItems as $item){
                        $tempAmount = $tempAmount+ $item->price;
                    }
                }
                $foodGroupAmount->push($tempAmount);
                $foodGroupNames->push($foodGroup->name);
                foreach ($orderItems as $submittedOrder) {
                    array_push($modifiedOrderGroups, $submittedOrder);
                }
            }
        }
        return [$foodGroupNames, $foodGroupAmount, $modifiedOrderGroups];
    }
}
