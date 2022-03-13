<?php

namespace App\Http\Controllers\Api\Food;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use App\Models\PropertyGroup;
use App\Models\PropertyItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyGroupController extends Controller
{
    //get Property Group
    public function index(){
        $properties = PropertyGroup::all()->toArray();
        return [customPaginate($properties), $properties];
    }

    //save new Property Group
    public function store(Request $request){
        $request->validate([
            'name'   => ['required', 'unique:property_groups']
        ],
            [
                'name.unique'                => 'A property already exists with this name',
            ]
        );
        $property = new PropertyGroup;
        $property->name = $request->name;
        $property->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $property->save();
        //get Property Group
        return $this->index();
    }

    //update property
    public function update(Request $request){
        $property = PropertyGroup::where('slug', $request->editSlug)->first();
        if($request->name != $property->name) {
            $request->validate([
                'name' => ['required', 'unique:property_groups,name,' . $property->name]
            ],
                [
                    'name.unique' => 'A property already exists with this name'
                ]
            );
        }
        $property->name = $request->name;
        $property->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $property->save();
        //get Property Group
        return $this->index();
    }

    //delete property
    public function destroy($slug){
        $property = PropertyGroup::where('slug', $slug)->first();

        $foodItems = FoodItem::all();
        foreach ($foodItems as $foodItem){
            if(!is_null($foodItem->property_group_ids)){
                $property_groups = json_decode($foodItem->property_group_ids);
                $modifiedPropertyGroups = array();
                foreach($property_groups as $property_group){
                    if($property_group != $property->id){
                        array_push($modifiedPropertyGroups,$property_group);
                    }
                }
                $foodItem->property_group_ids = json_encode($modifiedPropertyGroups);
                $foodItem->save();
            }
        }

        $items = PropertyItem::where('property_group_id', $property->id)->get();
        if(!is_null($items)){
            foreach ($items as $item){
                $item->delete();
            }
        }

        $property->delete();
        //get Property Group
        return $this->index();
    }
}
