<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\OrderGroup;
use App\Models\FoodItem;
use App\Models\Temporary;
use App\Models\FoodGroup;
use App\Models\WorkPeriod;
use App\Models\IngredientItem;
use App\Models\IngredientGroup;
use App\Models\FoodStockBranch;
use App\Models\OpeningClosingStock;
use App\Models\IngredientStockBranch;
use App\Models\OpeningClosingStockFood;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkPeriodController extends Controller
{
    //get all work periods
    public function index()
    {
        $user = Auth::user();
        if (!is_null($user->branch_id)) {
            $periods = WorkPeriod::where('branch_id', $user->branch_id)->orderBy('id', 'desc')->get()->toArray();
        } else {
            $periods = WorkPeriod::orderBy('id', 'desc')->get()->toArray();
        }
        return [customPaginate($periods), $periods];
    }

    //store new work period
    public function store(Request $request)
    {
        $user = Auth::user();
        $branch = Branch::where('id', $request->branch_id)->first();
        $checkExist = WorkPeriod::where('branch_id', $request->branch_id)->where('ended_at', null)->first();
        if (is_null($checkExist)) {
            $newPeriod = new WorkPeriod;
            $newPeriod->date = $request->date;
            $newPeriod->branch_name = $branch->name;
            $newPeriod->started_by = $user->name;
            $newPeriod->started_at = $request->started_at;
            $newPeriod->branch_id = $branch->id;
            $newPeriod->token = 1;
            $newPeriod->save();
            //add opening Stock ingredient
            $ingredients = IngredientItem::all();
            foreach ($ingredients as $ingredient) {
                $ingredientStockItem = IngredientStockBranch::where('branch_id', $request->branch_id)
                                                    ->where('ingredient_id', $ingredient->id)->first();
                $openingStock = new OpeningClosingStock;
                $openingStock->branch_id = $request->branch_id;
                $openingStock->user_id = $user->id;
                $openingStock->ingredient_id = $ingredient->id;
                $openingStock->work_period_id = $newPeriod->id;
                if (!is_null($ingredientStockItem)) {
                    $openingStock->opening_stock = $ingredientStockItem->qty;
                } else {
                    $openingStock->opening_stock = 0;
                }
                $openingStock->save();
            }

            //add opening Stock food
            $foods = FoodItem::all();
            foreach ($foods as $food) {
                $foodStockItem = FoodStockBranch::where('branch_id', $request->branch_id)
                                                    ->where('food_id', $food->id)->first();
                $openingStock2 = new OpeningClosingStockFood;
                $openingStock2->branch_id = $request->branch_id;
                $openingStock2->user_id = $user->id;
                $openingStock2->food_id = $food->id;
                $openingStock2->work_period_id = $newPeriod->id;
                if (!is_null($foodStockItem)) {
                    $openingStock2->opening_stock = $foodStockItem->qty;
                } else {
                    $openingStock2->opening_stock = 0;
                }
                $openingStock2->save();
            }


            return $this->index();
        } else {
            return "exist";
        }
    }

    //end work period
    public function update(Request $request)
    {
        $workPeriod = WorkPeriod::where('id', $request->id)->first();
        $checkArray  = array();
        $openingClosing = OpeningClosingStock::where('work_period_id', $workPeriod->id)->pluck('closing_stock');
        if (count($openingClosing)!== 0) {
            foreach ($openingClosing as $one) {
                if (json_encode($one) == "null") {
                    array_push($checkArray, $one);
                }
            }
            if (count($openingClosing) == count($checkArray)) {
                return "addClosing";
            } else {
                $orderGroups = OrderGroup::where('work_period_id', $workPeriod->id)->where('is_cancelled', 0)->where('is_settled', 0)->first();
                if (is_null($orderGroups)) {
                    $workPeriod->ended_at = $request->ended_at;
                    $workPeriod->ended_by = Auth::user()->name;
                    //add food closing stock
                    $foodItems = FoodItem::all();
                    foreach ($foodItems as $item) {
                        $openingClosing = OpeningClosingStockFood::where('food_id', $item->id)
                                        ->where('branch_id', $workPeriod->branch_id)
                                        ->where('work_period_id', $workPeriod->id)->first();
                        //to reduce qty
                        if (!is_null($openingClosing)) {
                            $foodStockBranch = FoodStockBranch::where('branch_id', $openingClosing->branch_id)->where('food_id', $item->id)->first();

                            if (!is_null($foodStockBranch)) {
                                $openingClosing->closing_stock = $foodStockBranch->qty;
                            } else {
                                $openingClosing->closing_stock = 0;
                            }

                            $openingClosing->save();
                        }
                    }
                    $workPeriod->save();
                    return $this->index();
                } else {
                    return "orderExist";
                }
            }
        } else {
            $orderGroups = OrderGroup::where('work_period_id', $workPeriod->id)->where('is_cancelled', 0)->where('is_settled', 0)->first();
            if (is_null($orderGroups)) {
                $workPeriod->ended_at = $request->ended_at;
                $workPeriod->ended_by = Auth::user()->name;
                //add food closing stock
                $foodItems = FoodItem::all();
                foreach ($foodItems as $item) {
                    $openingClosing = OpeningClosingStockFood::where('food_id', $item->id)
                                        ->where('branch_id', $workPeriod->branch_id)
                                        ->where('work_period_id', $workPeriod->id)->first();
                    //to reduce qty
                    if (!is_null($openingClosing)) {
                        $foodStockBranch = FoodStockBranch::where('branch_id', $openingClosing->branch_id)->where('food_id', $item->id)->first();

                        if (!is_null($foodStockBranch)) {
                            $openingClosing->closing_stock = $foodStockBranch->qty;
                        } else {
                            $openingClosing->closing_stock = 0;
                        }

                        $openingClosing->save();
                    }
                }
                $workPeriod->save();
                return $this->index();
            } else {
                return "orderExist";
            }
        }
    }

    public function getStockItems($id)
    {
        $workPeriod = WorkPeriod::where('started_at', $id)->first();
        $groups = IngredientGroup::all();
        $items = OpeningClosingStock::where('work_period_id', $workPeriod->id)->get();
        $modifiedItems = array();
        foreach ($items as $item) {
            $temp = new Temporary;
            $temp->id = $item->id;
            $temp->work_period_id = $item->work_period_id;
            $temp->ingredient_id = $item->ingredient_id;
            $ingredient = IngredientItem::where('id', $item->ingredient_id)->first();
            $temp->ingredient_name = $ingredient->name;
            $temp->ingredient_unit = $ingredient->unit;
            $temp->ingredient_group_id = $ingredient->ingredient_group_id;
            $temp->opening_stock = $item->opening_stock;
            $temp->addition_to_opening = $item->addition_to_opening;
            $temp->subtraction_from_opening = $item->subtraction_from_opening;
            $temp->closing_stock = $item->closing_stock;
            array_push($modifiedItems, $temp);
        }
        return [$modifiedItems, $groups];
    }

    //update closing stock
    public function updateStockItems(Request $request)
    {
        foreach ($request->items as $item) {
            $openingClosing = OpeningClosingStock::where('id', $item['id'])->first();
            //to reduce qty
            $ingredientStockBranch = IngredientStockBranch::where('branch_id', $openingClosing->branch_id)
                                                          ->where('ingredient_id', $item['ingredient_id'])->first();
            if (!is_null($item['closing_stock'])) {
                $openingClosing->closing_stock = $item['closing_stock'];
                //reduce stock qty
                if (!is_null($ingredientStockBranch)) {
                    $ingredientStockBranch->qty = $item['closing_stock'];
                    $ingredientStockBranch->save();
                }
            } else {
                $addition = 0;
                if ($item['addition_to_opening'] !== null) {
                    $addition = $item['addition_to_opening'];
                }
                $subtraction = 0;
                if ($item['subtraction_from_opening'] !== null) {
                    $addition = $item['subtraction_from_opening'];
                }

                $openingClosing->closing_stock = $item['opening_stock']+ $addition - $subtraction;
                //reduce stock qty
                if (!is_null($ingredientStockBranch)) {
                    $ingredientStockBranch->qty = $openingClosing->closing_stock;
                    $ingredientStockBranch->save();
                }
            }
            $openingClosing->save();
        }
    }

    public function getStockItemsFood($id)
    {
        $workPeriod = WorkPeriod::where('started_at', $id)->first();
        $groups = FoodGroup::all();
        $items = OpeningClosingStockFood::where('work_period_id', $workPeriod->id)->get();
        $modifiedItems = array();
        foreach ($items as $item) {
            $temp = new Temporary;
            $temp->id = $item->id;
            $temp->work_period_id = $item->work_period_id;
            $temp->ingredient_id = $item->food_id;
            $ingredient = FoodItem::where('id', $item->food_id)->first();
            $temp->ingredient_name = $ingredient->name;
            $temp->ingredient_group_id = $ingredient->food_group_id;
            $temp->opening_stock = $item->opening_stock;
            $temp->addition_to_opening = $item->addition_to_opening;
            $temp->subtraction_from_opening = $item->subtraction_from_opening;
            $temp->closing_stock = $item->closing_stock;
            array_push($modifiedItems, $temp);
        }
        return [$modifiedItems, $groups];
    }
}
