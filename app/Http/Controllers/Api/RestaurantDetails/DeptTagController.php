<?php

namespace App\Http\Controllers\Api\RestaurantDetails;

use App\Http\Controllers\Controller;
use App\Models\DepartmentTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeptTagController extends Controller
{
    //get all department tag
    public function index(){
        $deptTags = DepartmentTag::all()->toArray();
        return [customPaginate($deptTags), $deptTags];
    }

    //save new department tag
    public function store(Request $request){
        $request->validate([
            'name'   => ['required', 'unique:department_tags']
        ],
            [
                'name.unique'                => 'A department tag already exists with this name',
            ]
        );
        $deptTag = new DepartmentTag;
        $deptTag->name = $request->name;
        $deptTag->commission = $request->commission;
        $deptTag->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $deptTag->save();
        //get all department tag
        return $this->index();
    }

    //update department tag
    public function update(Request $request){

        $deptTag = DepartmentTag::where('slug', $request->editSlug)->first();
        if($request->name != $deptTag->name) {
            $request->validate([
                'name' => ['required', 'unique:department_tags,name,' . $deptTag->name]
            ],
                [
                    'name.unique' => 'A department tag already exists with this name'
                ]
            );
        }
        $deptTag->name = $request->name;
        $deptTag->commission = $request->commission;
        $deptTag->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $deptTag->save();
        //get all department tag
        return $this->index();
    }

    //delete department tag
    public function destroy($slug){
        $deptTag = DepartmentTag::where('slug', $slug)->first();
        $deptTag->delete();
        //get all department tag
        return $this->index();
    }
}
