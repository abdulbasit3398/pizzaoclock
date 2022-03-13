<?php

namespace App\Http\Controllers\Api\RestaurantDetails;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Table;
use App\Models\Temporary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TableController extends Controller
{
    //get all table
    public function index(){
        $user = Auth::user();
        if($user->branch_id == null){
            $tables = Table::all();
        }else{
            $tables = Table::where('branch_id',$user->branch_id)->get();
        }
        $modifiedTables = array();
        foreach ($tables as $table) {
            $temp = new Temporary;
            $temp->id = $table->id;
            $temp->name = $table->name;
            $temp->slug = $table->slug;
            $temp->capacity = $table->capacity;
            $temp->branch_id = $table->branch_id;
            $branch = Branch::where('id', $table->branch_id)->first();
            if(!is_null($branch)){
                $temp->branch_name = $branch->name;
            }else{
                $temp->branch_name = null;
            }
            array_push($modifiedTables, $temp);
        }
        return [customPaginate($modifiedTables), $modifiedTables];
    }

    //save new table
    public function store(Request $request){
        $table = new Table;
        $table->name = $request->name;
        $table->capacity = $request->capacity;
        $table->branch_id = $request->branch_id;
        $table->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $table->save();
        //get all table
        return $this->index();
    }

    //update table
    public function update(Request $request){
        $table = Table::where('slug', $request->editSlug)->first();
        $table->name = $request->name;

        if(!is_null($request->capacity)){
            $table->capacity = $request->capacity;
        }else{
            $table->capacity = NULL;
        }

        if($request->branch_id){
            $table->branch_id = $request->branch_id;
        }

        $table->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $table->save();

        //get all table
        return $this->index();
    }

    //delete table
    public function destroy($slug){
        $table = Table::where('slug', $slug)->first();
        $table->delete();
        //get all table
        return $this->index();
    }
}
