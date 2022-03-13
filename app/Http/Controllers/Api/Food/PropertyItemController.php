<?php

namespace App\Http\Controllers\Api\Food;

use App\Http\Controllers\Controller;
use App\Models\PropertyGroup;
use App\Models\PropertyItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyItemController extends Controller
{
    //get Property Item
    public function index($slug){
        $group = PropertyGroup::where('slug', $slug)->first();
        $items = PropertyItem::where('property_group_id', $group->id)->get()->toArray();
        return [$items, $group];
    }

    //save new Property Item
    public function store(Request $request){
        $group = PropertyGroup::where('slug', $request->propertyGroupSlug)->first();
        $item = new PropertyItem;
        $item->name = $request->name;
        $item->extra_price = $request->extraPrice;
        $item->allow_multi_quantity = $request->allow_multi_quantity;
        $item->property_group_id = $group->id;
        $item->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $item->save();
        //get Property Item
        return $this->index($group->slug);
    }

    //update property
    public function update(Request $request){
        $item = PropertyItem::where('slug', $request->editSlug)->first();
        $item->name = $request->name;
        $item->extra_price = $request->extraPrice;
        $item->allow_multi_quantity = $request->allow_multi_quantity;
        $item->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $item->save();
        //get Property Item
        $items = PropertyItem::where('property_group_id', $item->property_group_id)->get()->toArray();
        return [$items];
    }

    //delete property
    public function destroy($slug){
        $item = PropertyItem::where('slug', $slug)->first();
        $group_id = $item->property_group_id;
        $item->delete();
        //get Property Item
        $items = PropertyItem::where('property_group_id', $group_id)->get()->toArray();
        return [$items];
    }
}
