<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Temporary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    //get all customer
    public function index()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $customers = Customer::all();
        } else {
            $customers = Customer::where('branch_id', $user->branch_id)->get();
        }
        $modifiedCustomer = array();
        foreach ($customers as $customer) {
            $temp = new Temporary;
            $temp->id = $customer->id;
            $temp->name = $customer->name;
            $temp->email = $customer->email;
            $temp->phn_no = $customer->phn_no;
            $temp->address = $customer->address;
            $temp->branch_id = $customer->branch_id;
            $branch = Branch::where('id', $customer->branch_id)->first();
            if (!is_null($branch)) {
                $temp->branch_name = $branch->name;
            } else {
                $temp->branch_name = null;
            }
            $temp->slug = $customer->slug;
            array_push($modifiedCustomer, $temp);
        }
        return [customPaginate($modifiedCustomer), $modifiedCustomer];
    }

    //get all customer online
    public function indexOnline()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $customers = User::where('user_type', 'customer')->get()->toArray();
        } else {
            $customers =  User::where('user_type', 'customer')->where('branch_id', $user->branch_id)->get()->toArray();
        }
        return [customPaginate($customers), $customers];
    }

    //save new Customer
    public function store(Request $request)
    {
        $request->validate(
            [
            'phn_no'   => ['unique:customers']
        ],
            [
                'phn_no.unique'                => 'A customer exists with this phone number',
            ]
        );
        $customer = new Customer;
        $customer->name = $request->name;
        $customer->email = Str::lower($request->email);
        $customer->phn_no = $request->phn_no;
        $customer->address = $request->address;
        $customer->branch_id = $request->branch_id;
        $customer->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $customer->save();

        //get all customers
        return $this->index();
    }

    //update customer
    public function update(Request $request)
    {
        $customer = Customer::where('slug', $request->editSlug)->first();
        if ($request->phn_no != $customer->phn_no) {
            $request->validate(
                [
                'phn_no' => ['unique:customers']
            ],
                [
                    'phn_no.unique' => 'A customer exists with this phone number',
                ]
            );
        }

        if ($request->name !== "null") {
            $customer->name = $request->name;
        }

        if ($request->email !== "null") {
            $customer->email = Str::lower($request->email);
        }

        if ($request->phn_no !== "null") {
            $customer->phn_no = $request->phn_no;
        }

        if ($request->address !== "null") {
            $customer->address = $request->address;
        }

        if ($request->branch_id) {
            $customer->branch_id = $request->branch_id;
        }

        $customer->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $customer->save();

        //get all customers
        return $this->index();
    }

    //delete customer
    public function destroy($slug)
    {
        $customer = Customer::where('slug', $slug)->first();
        $customer->delete();
        //get all customers
        return $this->index();
    }
}
