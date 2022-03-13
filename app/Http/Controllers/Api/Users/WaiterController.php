<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Temporary;
use App\Models\Waiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WaiterController extends Controller
{
    //get all waiter
    public function index(Request $request){
        $user = Auth::user();
        if($user->branch_id == null){
            $waiters = Waiter::all();
        }else{
            $waiters = Waiter::where('branch_id',$user->branch_id)->get();
        }
        $modifiedWaiter = array();
        foreach ($waiters as $waiter) {
            $temp = new Temporary;
            $temp->id = $waiter->id;
            $temp->name = $waiter->name;
            $temp->phn_no = $waiter->phn_no;
            $temp->branch_id = $waiter->branch_id;
            $branch = Branch::where('id', $waiter->branch_id)->first();
            if(!is_null($branch)){
                $temp->branch_name = $branch->name;
            }else{
                $temp->branch_name = null;
            }
            $temp->slug = $waiter->slug;
            if(!is_null($waiter->image)){
                if($request->ip()=="127.0.0.1" || $request->ip()=="::1" ){
                    $theImage = substr($waiter->image, 1);
                    $temp->image = asset('').$theImage;
                }else{
                    $temp->image = asset('').$waiter->image;
                }
            }else{
                $temp->image = null;
            }
            array_push($modifiedWaiter, $temp);
        }
        return [customPaginate($modifiedWaiter), $modifiedWaiter];
    }

    //save new waiter
    public function store(Request $request){
        $request->validate([
            'phn_no'   => ['required', 'unique:waiters']
        ],
            [
                'phn_no.unique'                => 'A waiter exists with this phone number',
            ]
        );
        $waiter = new Waiter;
        $waiter->name = $request->name;
        $waiter->phn_no = $request->phn_no;
        $waiter->branch_id = $request->branch_id;
        $waiter->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);

        $waiterImage = $request->file('image');
        if(!is_null($waiterImage)){
            $request->validate([
                'image'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
            ],
                [
                    'image.mimes'                => 'Please select a valid image file',
                    'image.max'                  => 'Please select a file less than 5MB'
                ]
            );
            //storing file to server
            $name = time()."-".Str::slug($waiterImage->getClientOriginalName()).".".$waiterImage->getClientOriginalExtension();
            $waiterImage->move(public_path().'/images/waiter/', $name);
            //updating db
            $waiter->image = '/images/waiter/'.$name;
        }
        $waiter->save();

        //get all waiters
        return $this->index($request);
    }

    //update waiter
    public function update(Request $request){
        $waiter = Waiter::where('slug', $request->editSlug)->first();
        if($request->phn_no != $waiter->phn_no) {
            $request->validate([
                'phn_no' => ['required', 'unique:waiters']
            ],
                [
                    'phn_no.unique' => 'A waiter exists with this phone number',
                ]
            );
        }
        $waiter->name = $request->name;
        $waiter->phn_no = $request->phn_no;

        if($request->branch_id){
            $waiter->branch_id = $request->branch_id;
        }

        $waiter->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);

        $waiterImage = $request->file('image');
        if(!is_null($waiterImage)){
            $request->validate([
                'image'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
            ],
                [
                    'image.mimes'                => 'Please select a valid image file',
                    'image.max'                  => 'Please select a file less than 5MB'
                ]
            );
            //delete old image
            if(!is_null($waiter->image)){
                if (file_exists(public_path($waiter->image))) {
                    unlink(public_path($waiter->image));
                }
            }

            //storing file to server
            $name = time()."-".Str::slug($waiterImage->getClientOriginalName()).".".$waiterImage->getClientOriginalExtension();
            $waiterImage->move(public_path().'/images/waiter/', $name);
            //updating db
            $waiter->image = '/images/waiter/'.$name;
        }
        $waiter->save();

        //get all waiters
        return $this->index($request);
    }

    //delete waiter
    public function destroy($slug, Request $request){
        $waiter = Waiter::where('slug', $slug)->first();
        if(!is_null($waiter->image)){
            //delete old image
            if (file_exists(public_path($waiter->image))) {
                unlink(public_path($waiter->image));
            }
        }
        $waiter->delete();
        //get all waiters
        return $this->index($request);
    }
}
