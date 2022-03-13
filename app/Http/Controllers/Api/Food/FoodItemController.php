<?php

namespace App\Http\Controllers\Api\Food;

use App\Http\Controllers\Controller;
use App\Models\FoodGroup;
use App\Models\FoodItem;
use App\Models\FoodStockBranch;
use App\Models\FoodWithVariation;
use App\Models\FoodPurchaseHistory;
use App\Models\OpeningClosingStockFood;
use App\Models\PropertyItem;
use App\Models\Temporary;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FoodItemController extends Controller
{
    //get all foods
    public function index(Request $request)
    {
        $foods = FoodItem::all();
        $modifiedFoods = array();
        foreach ($foods as $food) {
            $temp = new Temporary;
            $temp->id = $food->id;

            $temp->food_group_id = $food->food_group_id;
            $foodGroup = FoodGroup::where('id', $food->food_group_id)->first();
            $temp->food_group = $foodGroup->name;

            $temp->name = $food->name;
            $temp->slug = $food->slug;
            $temp->is_special = $food->is_special;

            if (!is_null($food->image)) {
                if ($request->ip()=="127.0.0.1" || $request->ip()=="::1") {
                    $theImage = substr($food->image, 1);
                    $temp->image = asset('').$theImage;
                } else {
                    $temp->image = asset('').$food->image;
                }
            } else {
                $temp->image = null;
            }

            //variations
            $temp->has_variation = $food->has_variation;
            if ($food->has_variation == 1) {
                $variations = FoodWithVariation::where('food_item_id', $food->id)->get();
                $modifiedVariations = array();
                foreach ($variations as $variation) {
                    $variationItem = Variation::where('id', $variation->variation_id)->first();
                    $tempVariation = new Temporary;

                    //food with variation table
                    $tempVariation->food_with_variation_id = $variation->id;
                    $tempVariation->food_with_variation_price = $variation->price;

                    //variation table
                    $tempVariation->variation_name = $variationItem->name;
                    array_push($modifiedVariations, $tempVariation);
                }
                $temp->variations = $modifiedVariations;
            } else {
                $temp->price = $food->price;
            }

            //properties
            $temp->has_property = $food->has_property;
            if ($food->has_property == 1) {
                $property_groups = json_decode($food->property_group_ids);
                $modifiedPropertyGroups = array();
                foreach ($property_groups as $property_group) {
                    $property_items = PropertyItem::where('property_group_id', $property_group)->get();
                    if (!is_null($property_items)) {
                        array_push($modifiedPropertyGroups, $property_items);
                    }
                }
                $temp->properties = $modifiedPropertyGroups;
                $temp->property_groups = $property_groups;
            }

            //end $foods foreach after pushing
            array_push($modifiedFoods, $temp);
        }
        return [customPaginate($modifiedFoods), $modifiedFoods];
    }


    //storing new food item
    public function store(Request $request)
    {
        $foodItem = new FoodItem;
        $foodItem->food_group_id = $request->food_group_id;
        $foodItem->name = $request->name;
        $foodItem->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $foodItem->is_special = $request->isSpecial;

        $foodImage = $request->file('image');
        if (!is_null($foodImage)) {
            $request->validate(
                [
                'image'  => ['file','mimes:jpg,jpeg,png,gif','max:5000']
            ],
                [
                    'image.mimes'                => 'Please select a valid image file',
                    'image.max'                  => 'Please select a file less than 5MB'
                ]
            );
            //storing file to server
            $name = time()."-".Str::slug($foodImage->getClientOriginalName()).".".$foodImage->getClientOriginalExtension();
            $foodImage->move(public_path().'/images/food_item/', $name);
            //updating db
            $foodItem->image = '/images/food_item/'.$name;
        }

        $foodItem->has_property = $request->hasProperty;
        if ($request->hasProperty == 1) {
            $foodItem->property_group_ids =  "[".$request->properties."]";
        }

        $foodItem->has_variation = $request->hasVariation;
        if ($request->hasVariation == 0) {
            $foodItem->price = $request->price;
            $foodItem->save();
        } else {
            $foodItem->save();
            foreach ($request->variations as $key => $variation) {
                $getVariationSlug = explode(',', $variation)[0];
                if (in_array($getVariationSlug, $request->slugOfVariations)) {
                    $theVariation = Variation::where('slug', $getVariationSlug)->first();
                    $foodVariation = new FoodWithVariation;
                    $foodVariation->food_item_id = $foodItem->id;
                    $foodVariation->variation_id = $theVariation->id;
                    $foodVariation->price = explode(',', $variation)[1];
                    $foodVariation->save();
                }
            }
        }
        return $this->index($request);
    }


    //update food item
    public function update(Request $request)
    {
        $foodItem = FoodItem::where('id', $request->itemId)->first();
        $foodImage = $request->file('image');
        if (!is_null($foodImage)) {
            $request->validate(
                [
                'image'  => ['file','mimes:jpg,jpeg,png,gif','max:5000']
            ],
                [
                    'image.mimes'                => 'Please select a valid image file',
                    'image.max'                  => 'Please select a file less than 5MB'
                ]
            );

            if (!is_null($foodItem->image)) {
                //delete old image
                if (file_exists(public_path($foodItem->image))) {
                    unlink(public_path($foodItem->image));
                }
            }

            //storing file to server
            $name = time()."-".Str::slug($foodImage->getClientOriginalName()).".".$foodImage->getClientOriginalExtension();
            $foodImage->move(public_path().'/images/food_item/', $name);
            //updating db
            $foodItem->image = '/images/food_item/'.$name;
        } else {
            $foodItem->name = $request->name;
            if (!is_null($request->itemNewFoodGroup)) {
                $foodItem->food_group_id = $request->itemNewFoodGroup["id"];
            }

            if ($foodItem->has_variation == 0 && !is_null($request->price)) {
                $foodItem->price = $request->price;
            }


            $foodItem->is_special = $request->isSpecial;

            if ($request->deleteProperty == 1) {
                $foodItem->property_group_ids = null;
                $foodItem->has_property = 0;
            } else {
                if (!is_null($request->newPropertyGroups)) {
                    $tempPropertyIds = array();
                    foreach ($request->newPropertyGroups as $property_group) {
                        array_push($tempPropertyIds, $property_group['id']);
                    }
                    $foodItem->property_group_ids = json_encode($tempPropertyIds);
                    $foodItem->has_property = 1;
                }
            }
        }
        $foodItem->save();
        return $this->index($request);
    }



    //updating variations of specific food item
    public function updateVariation(Request $request)
    {
        $variations = FoodWithVariation::where('food_item_id', $request->foodItemId)->get();

        //if all variation be deleted, set price here for main item
        if (!is_null($request->deletedVariationsArray)) {
            if (count($variations) == count($request->deletedVariationsArray)) {
                $foodItem = FoodItem::where('id', $request->foodItemId)->first();
                $foodItem->price = $request->priceAfterAllVariationDelete;
                $foodItem->has_variation = 0;
                $foodItem->save();
            }
        }

        //loop through variations
        foreach ($variations as $variation) {

            //set new price for each variation if it exists in-->newPriceArray request
            if (!is_null($request->newPriceArray)) {

                //loop through the new price of variations
                foreach ($request->newPriceArray as $setNewPriceVariationItem) {
                    if ($variation->id == $setNewPriceVariationItem[0]) {

                        //if there is item to delete
                        if (!is_null($request->deletedVariationsArray)) {

                            //check if item to delete or not, if not then update price
                            if (!in_array($setNewPriceVariationItem[0], $request->deletedVariationsArray)) {
                                if (!is_null($setNewPriceVariationItem[1])) {
                                    $variation->price = $setNewPriceVariationItem[1];
                                    $variation->save();
                                }
                            }
                        } else {
                            //if there is no item to delete
                            if (!is_null($setNewPriceVariationItem[1])) {
                                $variation->price = $setNewPriceVariationItem[1];
                                $variation->save();
                            }
                        }
                    }
                }
            }

            //delete variation if exists in-->deleted_variation_array request
            if (!is_null($request->deletedVariationsArray)) {
                if (in_array($variation->id, $request->deletedVariationsArray)) {
                    $variation->delete();
                }
            }
        }
        return $this->index($request);
    }



    //store new variations of specific food item
    public function storeVariation(Request $request)
    {
        $foodItem = FoodItem::where('id', $request->foodItemId)->first();
        foreach ($request->variations as $key => $variation) {
            $getVariationSlug = explode(',', $variation)[0];
            if (in_array($getVariationSlug, $request->slugOfVariations)) {
                $theVariation = Variation::where('slug', $getVariationSlug)->first();
                $exist = FoodWithVariation::where('food_item_id', $foodItem->id)->where('variation_id', $theVariation->id)->first();
                if (is_null($exist)) {
                    $foodVariation = new FoodWithVariation;
                    $foodVariation->food_item_id = $foodItem->id;
                    $foodVariation->variation_id = $theVariation->id;
                    $foodVariation->price = explode(',', $variation)[1];
                    $foodVariation->save();
                } else {
                    $exist->price = explode(',', $variation)[1];
                    $exist->save();
                }
            }
        }
        $foodItem->price = null;
        $foodItem->has_variation = 1;
        $foodItem->save();

        return $this->index($request);
    }


    //delete food item
    public function destroy($slug, Request $request)
    {
        $foodItem = FoodItem::where('slug', $slug)->first();
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
        return $this->index($request);
    }
}
