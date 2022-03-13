<?php

namespace App\Http\Controllers\Api\RestaurantDetails;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Temporary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    //get all branch web
    public function indexWeb()
    {
        $branches = Branch::all()->toArray();
        return $branches;
    }

    //get all branch
    public function index()
    {
        $branches = Branch::all()->toArray();
        return [customPaginate($branches), $branches];
    }

    //save new branch
    public function store(Request $request)
    {
        $branch = new Branch;
        $branch->name = $request->name;
        $branch->phn_no = $request->phn_no;
        $branch->address = $request->address;
        $branch->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $branch->save();

        //get all branch
        return $this->index();
    }

    //update branch
    public function update(Request $request)
    {
        $branch = Branch::where('slug', $request->editSlug)->first();
        $branch->name = $request->name;
        $branch->phn_no = $request->phn_no;
        $branch->address = $request->address;
        $branch->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $branch->save();

        //get all branch
        return $this->index();
    }

    //delete branch
    public function destroy($slug)
    {
        $branch = Branch::where('slug', $slug)->first();
        $user = User::where('branch_id', $branch->id)->where('is_banned', 0)->first();
        if (is_null($user)) {
            $branch->delete();
            //get all branch
            return $this->index();
        } else {
            return "user";
        }
    }
}
