<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\Temporary;
use App\Models\FoodPurchase;
use App\Models\IngredientPurchase;
use App\Models\FoodPurchaseHistory;
use App\Models\IngredientPurchaseHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SupplierController extends Controller
{
    //get all suppliers
    public function index()
    {
        $suppliers = Supplier::all()->toArray();
        return [customPaginate($suppliers), $suppliers];
    }

    //save new waiter
    public function store(Request $request)
    {
        $request->validate(
            [
          'phn_no'   => ['required', 'unique:suppliers']
      ],
            [
              'phn_no.unique'                => 'A supplier exists with this phone number',
          ]
        );
        $supplier = new Supplier;
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phn_no = $request->phn_no;
        $supplier->due_balance = $request->due;
        $supplier->address = $request->address;
        $supplier->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);
        $supplier->save();
        //get all suppliers
        return $this->index();
    }

    //update waiter
    public function update(Request $request)
    {
        $supplier = Supplier::where('slug', $request->editSlug)->first();
        if ($request->phn_no != $supplier->phn_no) {
            $request->validate(
                [
              'phn_no' => ['required', 'unique:suppliers']
          ],
                [
                  'phn_no.unique' => 'A supplier exists with this phone number',
              ]
            );
        }
        $supplier->name = $request->name;
        $supplier->email = $request->email;
        $supplier->phn_no = $request->phn_no;
        $supplier->due_balance = $request->due;
        $supplier->address = $request->address;
        $supplier->save();
        //get all suppliers
        return $this->index();
    }

    //delete supplier
    public function destroy($slug, Request $request)
    {
        $supplier = Supplier::where('slug', $slug)->first();
        $supplier->delete();
        //get all suppliers
        return $this->index();
    }


    //supplier leadger
    public function supplierLeadger(Request $request)
    {
        $modifiedPurchaseGroups = array();
        if ($request->food == "food") {
            $purchases = FoodPurchase::where('supplier_id', $request->supplier['id'])
                                      ->where('created_at', '>=', Carbon::parse($request->fromDate))
                                      ->where('created_at', '<', Carbon::parse($request->toDate)->addDay(1))->get();

            foreach ($purchases as $submittedOrder) {
                $temp = new Temporary;
                $temp->id = $submittedOrder->id;
                $temp->branch_id = $submittedOrder->branch_id;
                $temp->supplier_id = $submittedOrder->supplier_id;
                $temp->supplier_name = $submittedOrder->supplier_name;
                $temp->invoice_number = $submittedOrder->invoice_number;
                $temp->purchase_date = $submittedOrder->purchase_date;
                $temp->desc = $submittedOrder->desc;
                $temp->payment_type = $submittedOrder->payment_type;
                $temp->total_bill = $submittedOrder->total_bill;
                $temp->paid_amount = $submittedOrder->paid_amount;
                $temp->credit_amount = $submittedOrder->credit_amount;
                $temp->created_at = $submittedOrder->created_at;
                //get order items here
                $theItems = FoodPurchaseHistory::where('food_purchase_id', $submittedOrder->id)->get();
                $temp->items = $theItems;
                array_push($modifiedPurchaseGroups, $temp);
            }
        } else {
            $purchases = IngredientPurchase::where('supplier_id', $request->supplier['id'])
                                    ->where('created_at', '>=', Carbon::parse($request->fromDate))
                                    ->where('created_at', '<', Carbon::parse($request->toDate)->addDay(1))->get();
            foreach ($purchases as $submittedOrder) {
                $temp = new Temporary;
                $temp->id = $submittedOrder->id;
                $temp->branch_id = $submittedOrder->branch_id;
                $temp->supplier_id = $submittedOrder->supplier_id;
                $temp->supplier_name = $submittedOrder->supplier_name;
                $temp->invoice_number = $submittedOrder->invoice_number;
                $temp->purchase_date = $submittedOrder->purchase_date;
                $temp->desc = $submittedOrder->desc;
                $temp->payment_type = $submittedOrder->payment_type;
                $temp->total_bill = $submittedOrder->total_bill;
                $temp->paid_amount = $submittedOrder->paid_amount;
                $temp->credit_amount = $submittedOrder->credit_amount;
                $temp->created_at = $submittedOrder->created_at;
                //get order items here
                $theItems = IngredientPurchaseHistory::where('ingredient_purchase_id', $submittedOrder->id)->get();
                $temp->items = $theItems;
                array_push($modifiedPurchaseGroups, $temp);
            }
        }
        return $modifiedPurchaseGroups;
    }
}
