<?php

namespace App\Http\Controllers\Api\RestaurantDetails;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentTypeController extends Controller
{
    //get all payment type
    public function index(){
        $paymentTpyes = PaymentType::all()->toArray();
        return [customPaginate($paymentTpyes), $paymentTpyes];
    }

    //save new payment type
    public function store(Request $request){
        $checkPaymentTypeExist = PaymentType::where('input_key', Str::slug($request->input_key))->first();
        if(is_null($checkPaymentTypeExist)){
            $paymentTpye = new PaymentType;
            $paymentTpye->name = $request->name;
            $paymentTpye->input_key = Str::slug($request->input_key);
            $paymentTpye->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
            $paymentTpye->save();

            //get all payment type
            return $this->index();
        }else{
            return "A payment type already exists with this key";
        }
    }

    //update payment type
    public function update(Request $request){
        $paymentTpye = PaymentType::where('slug', $request->editSlug)->first();
        $paymentTpye->name = $request->name;
        $paymentTpye->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $paymentTpye->save();
        //get all branch
        return $this->index();
    }

    //delete payment type
    public function destroy($slug){
        $paymentType = PaymentType::where('slug', $slug)->first();
        $paymentType->delete();
        //get all branch
        return $this->index();
    }
}
