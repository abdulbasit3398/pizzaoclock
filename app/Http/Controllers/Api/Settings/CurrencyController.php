<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Temporary;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CurrencyController extends Controller
{
    //get all currencies
    public function index()
    {
        $currencies = Currency::all();
        $modifiedCurrencies = array();
        foreach ($currencies as $currency) {
            $temp = new Temporary;
            $temp->id = $currency->id;
            $temp->name = $currency->name;
            $temp->code = $currency->code;
            $temp->rate = $currency->rate;
            $temp->symbol = $currency->symbol;
            $temp->alignment = $currency->alignment;
            $temp->is_default = $currency->is_default == 0 ? false: true;
            array_push($modifiedCurrencies, $temp);
        }
        return [customPaginate($modifiedCurrencies), $modifiedCurrencies];
    }

    //store a new currency
    public function store(Request $request)
    {
        $request->validate([
            'code'   => ['required', 'unique:currencies'],
        ],
            [
                'code.unique'                => 'A currency already exists with this code'
            ]
        );
        $tempCurrency = new Currency;
        $tempCurrency->code =Str::lower(str_replace(' ','_',$request->code));
        $tempCurrency->name = $request->name;
        $tempCurrency->rate = $request->rate;
        $tempCurrency->symbol = $request->symbol;
        $tempCurrency->alignment = $request->alignment;
        $tempCurrency->is_default = false;
        $tempCurrency->save();

        //get all currency
        return $this->index();
    }


    //Update a  new currencies
    public function update(Request $request)
    {
        $tempCurrency = Currency::where('code', $request->editCode)->first();
        $tempCurrency->name = $request->name;
        $tempCurrency->rate = $request->rate;
        $tempCurrency->symbol = $request->symbol;
        $tempCurrency->alignment = $request->alignment;
        $tempCurrency->save();
        //get all currency
        return $this->index();
    }

    //change default currency
    public function setDefault(Request $request)
    {
        $currency = Currency::where('code', $request->code)->first();
        $default = Currency::where('is_default', true)->first();
        $default->is_default = false;
        $currency->is_default = true;
        $default->save();
        $currency->save();
        //get all currency
        return $this->index();
    }

    //delete currency
    public function destroy($code)
    {
        if( $code!= "usd"){
            $currency = Currency::where('code',$code)->first();
            if($currency->is_default == true){
                $usdCurrency = Currency::where('code','usd')->first();
                $usdCurrency->is_default = true;
                $usdCurrency->save();
            }
            $currency->delete();

            //get all currency
            return $this->index();
        }
    }
}
