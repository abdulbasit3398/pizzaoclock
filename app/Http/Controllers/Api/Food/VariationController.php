<?php

namespace App\Http\Controllers\Api\Food;

use App\Http\Controllers\Controller;
use App\Models\FoodWithVariation;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VariationController extends Controller
{
    //get all variation
    public function index(){
        $variations = Variation::all()->toArray();
        return [customPaginate($variations), $variations];
    }

    //save new variation
    public function store(Request $request){
        $request->validate([
            'name'   => ['required', 'unique:variations']
        ],
            [
                'name.unique'                => 'A variation already exists with this name',
            ]
        );
        $variation = new Variation;
        $variation->name = $request->name;
        $variation->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $variation->save();
        //get all variation
        return $this->index();
    }

    //update variation
    public function update(Request $request){

        $variation = Variation::where('slug', $request->editSlug)->first();
        if($request->name != $variation->name) {
            $request->validate([
                'name' => ['required', 'unique:variations,name,' . $variation->name]
            ],
                [
                    'name.unique' => 'A variation already exists with this name'
                ]
            );
        }
        $variation->name = $request->name;
        $variation->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $variation->save();
        //get all variation
        return $this->index();
    }

    //delete variation
    public function destroy($slug){
        $variation = Variation::where('slug', $slug)->first();
        $item = FoodWithVariation::where('variation_id',$variation->id)->first();
        if(is_null($item)){
            $variation->delete();
            //get all variation
            return $this->index();
        }else{
            return "data exist";
        }
    }
}
