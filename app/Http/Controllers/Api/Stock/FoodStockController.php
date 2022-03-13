<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Branch;
use App\Models\IngredientGroup;
use App\Models\IngredientPurchase;
use App\Models\OpeningClosingStockFood;
use App\Models\FoodPurchaseHistory;
use App\Models\FoodStockBranch;
use App\Models\FoodItem;
use App\Models\Supplier;
use App\Models\Temporary;
use App\Models\OrderItem;
use App\Models\WorkPeriod;
use App\Models\OrderGroup;
use App\Models\FoodPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FoodStockController extends Controller
{
    //get all foodPurchase
    public function indexPurchase()
    {
        $foodPurchase = FoodPurchase::all()->toArray();
        return [customPaginate($foodPurchase), $foodPurchase];
    }

    //get all indexPurchaseItems
    public function indexPurchaseItems($id)
    {
        $grp = FoodPurchase::where('id', $id)->first();
        $foodPurchase = FoodPurchaseHistory::where('food_purchase_id', $id)->get();
        return [$foodPurchase,$grp];
    }


    //save new foodPurchase
    public function storePurchase(Request $request)
    {
        $foodPurchase = new FoodPurchase;
        $foodPurchase->branch_id = $request->branch_id;
        $foodPurchase->supplier_id = $request->supplier_id;
        $supplier = Supplier::where('id', $request->supplier_id)->first();
        $foodPurchase->supplier_name = $supplier->name;
        $foodPurchase->invoice_number = $request->invoice_number;
        $foodPurchase->purchase_date = Carbon::parse($request->purchase_date)->format('Y-m-d');

        $foodPurchase->desc = $request->desc;
        $foodPurchase->payment_type = $request->payment_type;
        $foodPurchase->total_bill = $request->total_bill;
        $foodPurchase->paid_amount = $request->paid_amount;
        if ($request->total_bill - $request->paid_amount >= 0) {
            $foodPurchase->credit_amount = $request->total_bill - $request->paid_amount;
        } else {
            $foodPurchaseGrp->credit_amount = 0;
        }
        $foodPurchase->save();

        foreach ($request->slugOfFoods as $key => $slugOfFood) {
            $food = FoodItem::where('slug', $slugOfFood)->first();
            $existing = FoodStockBranch::where('branch_id', $request->branch_id)
                                          ->where('food_id', $food->id)
                                          ->first();
            //manage stock of branch
            if (is_null($existing)) {
                //store new food purchase
                $newItem = new FoodStockBranch;
                $newItem->branch_id = $request->branch_id;
                $newItem->food_id = $food->id;
                $newItem->food_name = $food->name;
                //qty
                $newItem->qty = $request->qtys[$key];
                //rate
                $newItem->rate = $request->rates[$key];
                $newItem->save();
            } else {
                //update food stock
                //qty
                $existing->qty = (float)$request->qtys[$key]+$existing->qty;
                //rate is not used in stock branch food
                $existing->save();
            }

            //manage purchased food history
            $newHistory = new FoodPurchaseHistory;
            $newHistory->branch_id = $request->branch_id;
            $newHistory->food_purchase_id = $foodPurchase->id;
            $newHistory->food_id = $food->id;
            $newHistory->food_name = $food->name;
            //qty
            $newHistory->qty = $request->qtys[$key];
            //rate
            $newHistory->rate = $request->rates[$key];
            $newHistory->save();

            //opening closing Stock
            $workPeriod = WorkPeriod::where('branch_id', $request->branch_id)
                                    ->where('ended_at', null)->where('ended_by', null)->first();
            $stock = OpeningClosingStockFood::where('branch_id', $request->branch_id)
                                        ->where('work_period_id', $workPeriod->id)
                                        ->where('food_id', $food->id)
                                        ->first();
            if (is_null($stock)) {
                $newStock = new OpeningClosingStockFood;
                $newStock->branch_id = $request->branch_id;
                $newStock->user_id = Auth::user()->id;
                $newStock->food_id = $food->id;
                $newStock->work_period_id = $workPeriod->id;
                $newStock->opening_stock = $request->qtys[$key];
                $newStock->save();
            } else {
                $counter = 0;
                if (!is_null($stock->addition_to_opening)) {
                    $counter = $stock->addition_to_opening;
                }
                $stock->addition_to_opening = $request->qtys[$key]+ $counter;
                $stock->save();
            }
        }
        //get all foodPurchase
        return $this->indexPurchase();
    }

    //update foodPurchase
    public function updatePurchase(Request $request)
    {
        $foodPurchaseGrp = FoodPurchase::where('id', $request->group_id)->first();
        $foodPurchaseHistories = FoodPurchaseHistory::where('food_purchase_id', $foodPurchaseGrp->id)->get();
        foreach ($foodPurchaseHistories as $history) {
            $stock = FoodStockBranch::where('food_id', $history->food_id)->first();
            $temp;
            foreach ($request->items as $i) {
                if ($i['id']== $history->id) {
                    $temp = $i;
                }
            }
            if ($history->qty > $temp['qty']) {
                $sub = $history->qty - $temp['qty'];
                $stock->qty = $stock->qty - $sub;
            } else {
                $add = $temp['qty'] - $history->qty;
                $stock->qty = $stock->qty + $add;
            }

            $openingClosingStock = OpeningClosingStockFood::where('branch_id', $foodPurchaseGrp->branch_id)
                                                              ->where('food_id', $history->food_id)
                                                              ->latest()
                                                              ->first();
            if ($history->qty > $temp['qty']) {
                $sub = $history->qty - $temp['qty'];
                $openingClosingStock->subtraction_from_opening =$openingClosingStock->subtraction_from_opening + $sub;
            } else {
                $add = $temp['qty'] - $history->qty;
                $openingClosingStock->addition_to_opening =$openingClosingStock->addition_to_opening + $add;
            }
            $openingClosingStock->save();
            $stock->save();

            $history->qty =$temp['qty'];
            $history->rate =$temp['rate'];
            $history->save();

            $foodPurchaseGrp->invoice_number = $request->invoice_number;
            $foodPurchaseGrp->desc = $request->desc;
            $foodPurchaseGrp->payment_type = $request->payment_type;
            $foodPurchaseGrp->paid_amount = $request->paid_amount;
            $foodPurchaseGrp->total_bill = $request->total_bill;

            if ($request->total_bill - $request->paid_amount >= 0) {
                $foodPurchaseGrp->credit_amount = $request->total_bill - $request->paid_amount;
            } else {
                $foodPurchaseGrp->credit_amount = 0;
            }
            if ($request->date) {
                $foodPurchaseGrp->purchase_date = Carbon::parse($request->purchase_date)->format('Y-m-d');
            }
            $foodPurchaseGrp->save();
        }
        return $this->indexPurchase();
    }

    //delete foodPurchase
    public function destroyPurchase($id, Request $request)
    {
        $foodPurchaseGrp = FoodPurchase::where('id', $id)->first();
        $foodPurchaseHistories = FoodPurchaseHistory::where('food_purchase_id', $foodPurchaseGrp->id)->get();
        foreach ($foodPurchaseHistories as $history) {
            $stock = FoodStockBranch::where('food_id', $history->food_id)->first();
            $stock->qty = $stock->qty - $history->qty;
            $openingClosingStock = OpeningClosingStockFood::where('branch_id', $foodPurchaseGrp->branch_id)
                                                            ->where('food_id', $history->food_id)
                                                            ->latest()
                                                            ->first();
            $openingClosingStock->subtraction_from_opening = $openingClosingStock->subtraction_from_opening + $history->qty;
            $openingClosingStock->save();
            $stock->save();
            $history->delete();
        }
        $foodPurchaseGrp->delete();
        return $this->indexPurchase();
    }


    //get by selected branch
    public function filter(Request $request)
    {
        $branch = Branch::where('id', $request->branch['id'])->first();
        $modifiedOrderGroups = array();
        $workPeriods = WorkPeriod::where('branch_id', $branch->id)
            ->where('created_at', '>=', Carbon::parse($request->fromDate))
            ->where('created_at', '<', Carbon::parse($request->toDate)->addDay(1))->get();

        return $workPeriods;
    }
}
