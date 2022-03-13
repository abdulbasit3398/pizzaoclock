<?php

namespace App\Http\Controllers\Api\Food;

use App\Http\Controllers\Controller;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\FoodStockBranch;
use App\Models\FoodWithVariation;
use App\Models\FoodPurchaseHistory;
use App\Models\OpeningClosingStockFood;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FoodGroupController extends Controller
{
    //get all food group
    public function index()
    {
        $foodGroups = FoodGroup::all()->toArray();
        $foodStocks = FoodStockBranch::all();
        return [customPaginate($foodGroups), $foodGroups,$foodStocks];
    }

    //save new food group
    public function store(Request $request)
    {
        $request->validate(
            [
            'name'   => ['required', 'unique:food_groups']
        ],
            [
                'name.unique'                => 'A food group already exists with this name',
            ]
        );
        $foodGroup = new FoodGroup;
        $foodGroup->name = $request->name;
        $foodGroup->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $foodGroup->save();
        //get all food group
        return $this->index();
    }

    //update food group
    public function update(Request $request)
    {
        $foodGroup = FoodGroup::where('slug', $request->editSlug)->first();
        if ($request->name != $foodGroup->name) {
            $request->validate(
                [
                'name' => ['required', 'unique:food_groups,name,' . $foodGroup->name]
            ],
                [
                    'name.unique' => 'A food group already exists with this name'
                ]
            );
        }
        $foodGroup->name = $request->name;
        $foodGroup->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $foodGroup->save();
        //get all food group
        return $this->index();
    }

    //delete food group
    public function destroy($slug)
    {
        $foodGroup = FoodGroup::where('slug', $slug)->first();
        //food items
        $items = FoodItem::where('food_group_id', $foodGroup->id)->get();
        foreach ($items as $foodItem) {
            if ($foodItem->has_variation == 1) {
                $variations = FoodWithVariation::where('food_item_id', $foodItem->id)->get();
                foreach ($variations as $variation) {
                    $variation->delete();
                }
            }
            if (!is_null($foodItem->image)) {
                //delete old image
                if (file_exists(public_path($foodItem->image))) {
                    unlink(public_path($foodItem->image));
                }
            }

            $histories = FoodPurchaseHistory::where('food_id', $foodItem->id)->get();
            $stocks = FoodStockBranch::where('food_id', $foodItem->id)->get();
            $openingClosingStock = OpeningClosingStockFood::where('food_id', $foodItem->id)->get();
            foreach ($histories as $history) {
                $history->delete();
            }
            foreach ($stocks as $stock) {
                $stock->delete();
            }
            foreach ($openingClosingStock as $openingClosing) {
                $openingClosing->delete();
            }
            $foodItem->delete();
        }
        $foodGroup->delete();
        //get all food group
        return $this->index();
    }
}
