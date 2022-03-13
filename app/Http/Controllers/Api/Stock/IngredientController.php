<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\IngredientGroup;
use App\Models\IngredientPurchase;
use App\Models\OpeningClosingStock;
use App\Models\IngredientPurchaseHistory;
use App\Models\IngredientStockBranch;
use App\Models\IngredientItem;
use App\Models\Supplier;
use App\Models\Temporary;
use App\Models\WorkPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class IngredientController extends Controller
{
    //get all $ingredientGroup
    public function indexGroup()
    {
        $ingredientGroups = IngredientGroup::all()->toArray();
        return [customPaginate($ingredientGroups), $ingredientGroups];
    }

    //save new $ingredientGroup
    public function storeGroup(Request $request)
    {
        $ingredientGroup = new IngredientGroup;
        $ingredientGroup->name = $request->name;
        $ingredientGroup->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $ingredientGroup->save();
        //get all $ingredientGroup
        return $this->indexGroup();
    }

    //update $ingredientGroup
    public function updateGroup(Request $request)
    {
        $ingredientGroup = IngredientGroup::where('slug', $request->editSlug)->first();
        $ingredientGroup->name = $request->name;
        $ingredientGroup->save();
        //get all $ingredientGroup
        return $this->indexGroup();
    }

    //delete $ingredientGroup
    public function destroyGroup($slug, Request $request)
    {
        $ingredientGroup = IngredientGroup::where('slug', $slug)->first();
        $ingredientItem = IngredientItem::where('ingredient_group_id', $ingredientGroup->id)->get();
        foreach ($ingredientItem as $item) {
            $histories = IngredientPurchaseHistory::where('ingredient_id', $item->id)->get();
            $stocks = IngredientStockBranch::where('ingredient_id', $item->id)->get();
            $openingClosingStock = OpeningClosingStock::where('ingredient_id', $item->id)->get();
            foreach ($histories as $history) {
                $history->delete();
            }
            foreach ($stocks as $stock) {
                $stock->delete();
            }
            foreach ($openingClosingStock as $openingClosing) {
                $openingClosing->delete();
            }
            $item->delete();
        }
        $ingredientGroup->delete();
        //get all $ingredientGroup
        return $this->indexGroup();
    }

    //item
    //get all $ingredientItem
    public function indexItem()
    {
        $ingredientItems = IngredientItem::all()->toArray();
        $ingredientStocks = IngredientStockBranch::all();
        return [customPaginate($ingredientItems), $ingredientItems, $ingredientStocks];
    }

    //save new $ingredientItem
    public function storeItem(Request $request)
    {
        $ingredientItem = new IngredientItem;
        $ingredientItem->name = $request->name;
        $ingredientItem->unit = $request->unit;

        $group = IngredientGroup::where('id', $request->group_id)->first();
        $ingredientItem->ingredient_group_id = $request->group_id;
        $ingredientItem->group_name = $group->name;

        $ingredientItem->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $ingredientItem->save();
        //get all $ingredientItem
        return $this->indexItem();
    }

    //update $ingredientItem
    public function updateItem(Request $request)
    {
        $ingredientItem = IngredientItem::where('slug', $request->editSlug)->first();
        $ingredientItem->name = $request->name;
        $ingredientItem->unit = $request->unit;

        $group = IngredientGroup::where('id', $request->group_id)->first();
        $ingredientItem->ingredient_group_id = $request->group_id;
        $ingredientItem->group_name = $group->name;

        $ingredientItem->save();
        //get all $ingredientItem
        return $this->indexItem();
    }

    //delete $ingredientItem
    public function destroyItem($slug, Request $request)
    {   //check something
        $ingredientItem = IngredientItem::where('slug', $slug)->first();
        $histories = IngredientPurchaseHistory::where('ingredient_id', $ingredientItem->id)->get();
        $stocks = IngredientStockBranch::where('ingredient_id', $ingredientItem->id)->get();
        $openingClosingStock = OpeningClosingStock::where('ingredient_id', $ingredientItem->id)->get();
        foreach ($histories as $history) {
            $history->delete();
        }
        foreach ($stocks as $stock) {
            $stock->delete();
        }
        foreach ($openingClosingStock as $openingClosing) {
            $openingClosing->delete();
        }
        $ingredientItem->delete();
        //get all $ingredientItem
        return $this->indexItem();
    }


    //get all ingredientPurchase
    public function indexPurchase()
    {
        $ingredientPurchase = IngredientPurchase::all()->toArray();
        return [customPaginate($ingredientPurchase), $ingredientPurchase];
    }

    //get all indexPurchaseItems
    public function indexPurchaseItems($id)
    {
        $grp = IngredientPurchase::where('id', $id)->first();
        $ingPurchase = IngredientPurchaseHistory::where('ingredient_purchase_id', $id)->get();
        return [$ingPurchase,$grp];
    }

    //save new ingredientPurchase
    public function storePurchase(Request $request)
    {
        $ingredientPurchase = new IngredientPurchase;
        $ingredientPurchase->branch_id = $request->branch_id;
        $ingredientPurchase->supplier_id = $request->supplier_id;
        $supplier = Supplier::where('id', $request->supplier_id)->first();
        $ingredientPurchase->supplier_name = $supplier->name;
        $ingredientPurchase->invoice_number = $request->invoice_number;
        $ingredientPurchase->purchase_date = Carbon::parse($request->purchase_date)->format('Y-m-d');

        $ingredientPurchase->desc = $request->desc;
        $ingredientPurchase->payment_type = $request->payment_type;
        $ingredientPurchase->total_bill = $request->total_bill;
        $ingredientPurchase->paid_amount = $request->paid_amount;
        if ($request->total_bill - $request->paid_amount >= 0) {
            $ingredientPurchase->credit_amount = $request->total_bill - $request->paid_amount;
        } else {
            $ingredientPurchase->credit_amount = 0;
        }
        $ingredientPurchase->save();

        foreach ($request->slugOfIngredients as $key => $slugOfIngredient) {
            $ingredient = IngredientItem::where('slug', $slugOfIngredient)->first();
            $existing = IngredientStockBranch::where('branch_id', $request->branch_id)
                                            ->where('ingredient_id', $ingredient->id)
                                            ->first();
            //manage stock of branch
            if (is_null($existing)) {
                //store new ingredient purchase
                $newItem = new IngredientStockBranch;
                $newItem->branch_id = $request->branch_id;
                $newItem->ingredient_id = $ingredient->id;
                $newItem->ingredient_name = $ingredient->name;
                //qty
                $newItem->qty = $request->qtys[$key];
                //rate
                $newItem->rate = $request->rates[$key];
                $newItem->save();
            } else {
                //update ingredient stock
                //qty
                $existing->qty = (float)$request->qtys[$key]+$existing->qty;
                //rate is not used in stock branch ingredient
                $existing->save();
            }

            //manage purchased ingredient history
            $newHistory = new IngredientPurchaseHistory;
            $newHistory->branch_id = $request->branch_id;
            $newHistory->ingredient_purchase_id = $ingredientPurchase->id;
            $newHistory->ingredient_id = $ingredient->id;
            $newHistory->ingredient_name = $ingredient->name;
            //qty
            $newHistory->qty = $request->qtys[$key];
            //rate
            $newHistory->rate = $request->rates[$key];
            $newHistory->save();

            //opening closing Stock
            $workPeriod = WorkPeriod::where('branch_id', $request->branch_id)
                                    ->where('ended_at', null)->where('ended_by', null)->first();
            $stock = OpeningClosingStock::where('branch_id', $request->branch_id)
                                          ->where('work_period_id', $workPeriod->id)
                                          ->where('ingredient_id', $ingredient->id)
                                          ->first();
            if (is_null($stock)) {
                $newStock = new OpeningClosingStock;
                $newStock->branch_id = $request->branch_id;
                $newStock->user_id = Auth::user()->id;
                $newStock->ingredient_id = $ingredient->id;
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
        //get all ingredientPurchase
        return $this->indexPurchase();
    }

    //update ingredientPurchase
    public function updatePurchase(Request $request)
    {
        $ingredientPurchaseGrp = IngredientPurchase::where('id', $request->group_id)->first();
        $ingredientPurchaseHistories = IngredientPurchaseHistory::where('ingredient_purchase_id', $ingredientPurchaseGrp->id)->get();
        foreach ($ingredientPurchaseHistories as $history) {
            $stock = IngredientStockBranch::where('ingredient_id', $history->ingredient_id)->first();
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

            $openingClosingStock = OpeningClosingStock::where('branch_id', $ingredientPurchaseGrp->branch_id)
                                                            ->where('ingredient_id', $history->ingredient_id)
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

            $ingredientPurchaseGrp->invoice_number = $request->invoice_number;
            $ingredientPurchaseGrp->desc = $request->desc;
            $ingredientPurchaseGrp->payment_type = $request->payment_type;
            $ingredientPurchaseGrp->paid_amount = $request->paid_amount;
            $ingredientPurchaseGrp->total_bill = $request->total_bill;

            if ($request->total_bill - $request->paid_amount >= 0) {
                $ingredientPurchaseGrp->credit_amount = $request->total_bill - $request->paid_amount;
            } else {
                $ingredientPurchaseGrp->credit_amount = 0;
            }
            if ($request->date) {
                $ingredientPurchaseGrp->purchase_date = Carbon::parse($request->purchase_date)->format('Y-m-d');
            }
            $ingredientPurchaseGrp->save();
        }
        return $this->indexPurchase();
    }

    //delete ingredientPurchase
    public function destroyPurchase($id, Request $request)
    {
        $ingredientPurchaseGrp = IngredientPurchase::where('id', $id)->first();
        $ingredientPurchaseHistories = IngredientPurchaseHistory::where('ingredient_purchase_id', $ingredientPurchaseGrp->id)->get();
        foreach ($ingredientPurchaseHistories as $history) {
            $stock = IngredientStockBranch::where('ingredient_id', $history->ingredient_id)->first();
            $stock->qty = $stock->qty - $history->qty;
            $openingClosingStock = OpeningClosingStock::where('branch_id', $ingredientPurchaseGrp->branch_id)
                                                            ->where('ingredient_id', $history->ingredient_id)
                                                            ->latest()
                                                            ->first();
            $openingClosingStock->subtraction_from_opening = $openingClosingStock->subtraction_from_opening + $history->qty;
            $openingClosingStock->save();
            $stock->save();
            $history->delete();
        }
        $ingredientPurchaseGrp->delete();
        return $this->indexPurchase();
    }
}
