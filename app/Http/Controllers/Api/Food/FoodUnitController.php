<?php

namespace App\Http\Controllers\Api\Food;

use App\Http\Controllers\Controller;
use App\Models\FoodUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FoodUnitController extends Controller
{
    //get all food unit
    public function index(){
        $foodUnits = FoodUnit::all()->toArray();
        return [customPaginate($foodUnits), $foodUnits];
    }

    //save new food unit
    public function store(Request $request){
        $request->validate([
            'name'   => ['required', 'unique:food_units']
        ],
            [
                'name.unique'                => 'A food unit already exists with this name',
            ]
        );
        $foodUnit = new FoodUnit;
        $foodUnit->name = $request->name;
        $foodUnit->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $foodUnit->save();
        //get all food unit
        return $this->index();
    }

    //update food unit
    public function update(Request $request){

        $foodUnit = FoodUnit::where('slug', $request->editSlug)->first();
        if($request->name != $foodUnit->name) {
            $request->validate([
                'name' => ['required', 'unique:food_units,name,' . $foodUnit->name]
            ],
                [
                    'name.unique' => 'A food unit already exists with this name'
                ]
            );
        }
        $foodUnit->name = $request->name;
        $foodUnit->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $foodUnit->save();
        //get all food unit
        return $this->index();
    }

    //delete food unit
    public function destroy($slug){
        $foodUnit = FoodUnit::where('slug', $slug)->first();
        $foodUnit->delete();
        //get all food unit
        return $this->index();
    }
}
