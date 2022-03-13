<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Customer;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Temporary;
use App\Models\WorkPeriod;
use App\Models\FoodStockBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    //submit order
    public function submit(Request $request)
    {
        //check if the work period is not ended
        $workPeriod = WorkPeriod::where('id', $request->workPeriod['id'])->first();
        if ($workPeriod->ended_at == null) {
            //create order group
            $newGroup = new OrderGroup;
            $newGroup->work_period_id = $workPeriod->id;
            $newGroup->user_id = Auth::user()->id;
            $newGroup->user_name = Auth::user()->name;

            //branch
            $newGroup->branch_id = $request->branch['id'];
            $newGroup->branch_name = $request->branch['name'];

            //customer
            if ($request->newCustomer == 0) {
                //existing customer
                if (!is_null($request->customer)) {
                    $newGroup->customer_id = $request->customer['id'];
                    $newGroup->customer_name = $request->customer['name'];
                } else {
                    $newGroup->customer_name = "-";
                }
            } else {
                //re-check if customer exists with this phn no
                if (!is_null($request->newCustomerInfo['number'])) {
                    $checkCustomer = Customer::where('phn_no', $request->newCustomerInfo['number'])->first();
                    if (is_null($checkCustomer)) {
                        //new customer
                        $submittedOrder = new Customer;
                        $submittedOrder->name = $request->newCustomerInfo['name'];
                        $submittedOrder->slug =  Str::random(3).'-'.time().'-'.Str::slug($submittedOrder->name);
                        $submittedOrder->branch_id = $newGroup->branch_id;
                        $submittedOrder->phn_no = $request->newCustomerInfo['number'];
                        $submittedOrder->save();
                        //assign to order group
                        $newGroup->customer_id = $submittedOrder->id;
                        $newGroup->customer_name = $submittedOrder->name;
                    } else {
                        //assign to order group
                        $newGroup->customer_id = $checkCustomer->id;
                        $newGroup->customer_name = $checkCustomer->name;
                    }
                } else {
                    //if no phn number given, new customer
                    $submittedOrder = new Customer;
                    $submittedOrder->name = $request->newCustomerInfo['name'];
                    $submittedOrder->slug =  Str::random(3).'-'.time().'-'.Str::slug($submittedOrder->name);
                    $submittedOrder->branch_id = $newGroup->branch_id;
                    $submittedOrder->phn_no = $request->newCustomerInfo['number'];
                    $submittedOrder->save();
                    //assign to order group
                    $newGroup->customer_id = $submittedOrder->id;
                    $newGroup->customer_name = $submittedOrder->name;
                }
            }

            //table
            if (!is_null($request->table)) {
                $newGroup->table_id = $request->table['id'];
                $newGroup->table_name = $request->table['name'];
            } else {
                $newGroup->table_name = "-";
            }

            //waiter
            if (!is_null($request->waiter)) {
                $newGroup->waiter_id = $request->waiter['id'];
                $newGroup->waiter_name = $request->waiter['name'];
            } else {
                $newGroup->waiter_name = "-";
            }

            //dept tag
            if (!is_null($request->dept_tag)) {
                $newGroup->dept_tag_id = $request->dept_tag['id'];
                $newGroup->dept_tag_name = $request->dept_tag['name'];
            } else {
                $newGroup->dept_tag_name = "-";
            }

            //token
            $newGroup->token = json_encode($request->token);

            //total_guest
            $newGroup->total_guest = $request->total_guest;

            //service charge and discount
            $localCurrency = Currency::where('id', $request->localCurrency['id'])->first();
            $theSettings = Setting::where('name', "sDiscount")->first();
            //flat money
            if ($theSettings->value == "flat") {
                $newGroup->service_charge = ($request->serviceCharge / $localCurrency->rate);
                $newGroup->discount = ($request->discount / $localCurrency->rate);
            }
            //percentage
            if ($theSettings->value == "percentage") {
                $newGroup->service_charge = $request->serviceCharge ;
                $newGroup->discount = $request->discount;
            }

            //order bill == subtotal
            $newGroup->order_bill = $request->subTotal;
            $newGroup->total_payable = round($request->totalPayable, 2);
            $newGroup->vat = $request->theVat;

            $newGroup->vat_system = getSystemSettings('vat_system');
            if (getSystemSettings('vat_system') != "igst") {
                $newGroup->cgst =  $request->subTotal*((float)getSystemSettings('cgst')/100);
                $newGroup->sgst = $request->subTotal*((float)getSystemSettings('sgst')/100);
            }

            $newGroup->dept_commission = $request->dept_commission;
            //set paid as not
            $newGroup->is_paid = 0;
            //return and paid amount
            $newGroup->paid_amount = 0;

            $newGroup->is_accepted = 0; //from kitchen
            $newGroup->is_cancelled = 0; //order receiver decides
            $newGroup->is_settled = 0; //order receiver decides
            $newGroup->is_ready = 0; //decided from kitchen

            //save the group here
            $newGroup->save();

            //save each order items
            $theOrderItems = $request->orderItems;
            foreach ($theOrderItems as $item) {
                $newItem = new OrderItem;
                $newItem->order_group_id = $newGroup->id;

                $newItem->food_item = $item['item']['name'];
                $newItem->food_group = $item['item']['food_group'];

                if ($item['item']['has_variation'] == "1") {
                    $newItem->price = $item['variation']['food_with_variation_price'] * $item['quantity'];
                } else {
                    $newItem->price = $item['item']['price'] * $item['quantity'];
                }

                if (isset($item['variation'])) {
                    $newItem->variation = $item['variation']['variation_name'];
                }

                if (isset($item['properties'])) {
                    $propertyArray = array();
                    foreach ($item['properties'] as $property) {
                        $text = new Temporary;
                        $text->property = $property['item']['name'];
                        $text->quantity = $property['quantity'];
                        $text->price_per_qty = (float)$property['item']['extra_price'];
                        $newItem->price = $newItem->price + (float)(($text->price_per_qty * $text->quantity) *$item['quantity']);
                        array_push($propertyArray, $text);
                    }
                    $newItem->properties = json_encode($propertyArray);
                }
                $newItem->quantity = $item['quantity'];
                $newItem->is_cooking = 0;
                $newItem->is_ready = 0;
                $newItem->save();
                $stock = FoodStockBranch::where('branch_id', $request->branch['id'])->where('food_id', $item['item']['id'])->first();
                if ($stock->qty>0) {
                    $stock->qty = $stock->qty - $item['quantity'];
                }
                $stock->save();
            }
            $workPeriod->token = $workPeriod->token + 1;
            $workPeriod->save();
            return [app('App\Http\Controllers\Api\Users\CustomerController')->index(), app('App\Http\Controllers\Api\Dashboard\WorkPeriodController')->index()];
        } else {
            //if work period is ended
            return "ended";
        }
    }

    //settle order
    public function settle(Request $request)
    {
        //check if the work period is not ended
        $workPeriod = WorkPeriod::where('id', $request->workPeriod['id'])->first();
        if ($workPeriod->ended_at == null) {
            if (!is_null($request->payment_type) && !is_null($request->payment_amount && $request->paidMoney >= $request->totalPayable)) {
                //create order group
                $newGroup = new OrderGroup;
                $newGroup->work_period_id = $workPeriod->id;
                $newGroup->user_id = Auth::user()->id;
                $newGroup->user_name = Auth::user()->name;

                //branch
                $newGroup->branch_id = $request->branch['id'];
                $newGroup->branch_name = $request->branch['name'];

                //customer
                if ($request->newCustomer == 0) {
                    //existing customer
                    if (!is_null($request->customer)) {
                        $newGroup->customer_id = $request->customer['id'];
                        $newGroup->customer_name = $request->customer['name'];
                    } else {
                        $newGroup->customer_name = "-";
                    }
                } else {
                    //re-check if customer exists with this phn no
                    if (!is_null($request->newCustomerInfo['number'])) {
                        $checkCustomer = Customer::where('phn_no', $request->newCustomerInfo['number'])->first();
                        if (is_null($checkCustomer)) {
                            //new customer
                            $submittedOrder = new Customer;
                            $submittedOrder->name = $request->newCustomerInfo['name'];
                            $submittedOrder->slug =  Str::random(3).'-'.time().'-'.Str::slug($submittedOrder->name);
                            $submittedOrder->branch_id = $newGroup->branch_id;
                            $submittedOrder->phn_no = $request->newCustomerInfo['number'];
                            $submittedOrder->save();
                            //assign to order group
                            $newGroup->customer_id = $submittedOrder->id;
                            $newGroup->customer_name = $submittedOrder->name;
                        } else {
                            //assign to order group
                            $newGroup->customer_id = $checkCustomer->id;
                            $newGroup->customer_name = $checkCustomer->name;
                        }
                    } else {
                        //if no phn number given, new customer
                        $submittedOrder = new Customer;
                        $submittedOrder->name = $request->newCustomerInfo['name'];
                        $submittedOrder->slug =  Str::random(3).'-'.time().'-'.Str::slug($submittedOrder->name);
                        $submittedOrder->branch_id = $newGroup->branch_id;
                        $submittedOrder->phn_no = $request->newCustomerInfo['number'];
                        $submittedOrder->save();
                        //assign to order group
                        $newGroup->customer_id = $submittedOrder->id;
                        $newGroup->customer_name = $submittedOrder->name;
                    }
                }

                //table
                if (!is_null($request->table)) {
                    $newGroup->table_id = $request->table['id'];
                    $newGroup->table_name = $request->table['name'];
                } else {
                    $newGroup->table_name = "-";
                }

                //waiter
                if (!is_null($request->waiter)) {
                    $newGroup->waiter_id = $request->waiter['id'];
                    $newGroup->waiter_name = $request->waiter['name'];
                } else {
                    $newGroup->waiter_name = "-";
                }

                //dept tag
                if (!is_null($request->dept_tag)) {
                    $newGroup->dept_tag_id = $request->dept_tag['id'];
                    $newGroup->dept_tag_name = $request->dept_tag['name'];
                } else {
                    $newGroup->dept_tag_name = "-";
                }

                //token
                $newGroup->token = json_encode($request->token);

                //total_guest
                $newGroup->total_guest = $request->total_guest;

                //service charge and discount
                $localCurrency = Currency::where('id', $request->localCurrency['id'])->first();
                $theSettings = Setting::where('name', "sDiscount")->first();
                //flat money
                if ($theSettings->value == "flat") {
                    $newGroup->service_charge = ($request->serviceCharge / $localCurrency->rate);
                    $newGroup->discount = ($request->discount / $localCurrency->rate);
                }
                //percentage
                if ($theSettings->value == "percentage") {
                    $newGroup->service_charge = $request->serviceCharge ;
                    $newGroup->discount = $request->discount;
                }

                //order bill == subtotal
                $newGroup->order_bill = $request->subTotal;
                $newGroup->total_payable = round($request->totalPayable, 2);
                $newGroup->vat = $request->theVat;
                $newGroup->vat_system = getSystemSettings('vat_system');
                if (getSystemSettings('vat_system') != "igst") {
                    $newGroup->cgst =  $request->subTotal*((float)getSystemSettings('cgst')/100);
                    $newGroup->sgst = $request->subTotal*((float)getSystemSettings('sgst')/100);
                }
                $newGroup->dept_commission = $request->dept_commission;


                //set paid as not
                $newGroup->is_paid = 1;

                //return and paid amount
                $newGroup->paid_amount = $request->paidMoney;
                $newGroup->return_amount = $request->paidMoney - $request->totalPayable;

                //partial payment
                $tempPartialBillArray = array();
                foreach ($request->payment_type as $payment) {
                    //todo::settings pos_screen
                    $pos_screen = Setting::where('name', "pos_screen")->first();
                    if ($pos_screen->value == "0") {
                        //default pos
                        if (isset($request->payment_amount[$payment['id']]) && !is_null($request->payment_amount[$payment['id']])) {
                            $value = new Temporary;
                            $value->payment_type = $payment['name'];
                            $value->payment_type_slug = $payment['slug'];
                            $value->amount = $request->payment_amount[$payment['id']] / $localCurrency->rate;
                            array_push($tempPartialBillArray, $value);
                        }
                    }

                    if ($pos_screen->value == "1") {
                        //2nd POS
                        $value = new Temporary;
                        $value->payment_type = $payment['name'];
                        $value->payment_type_slug = $payment['slug'];
                        $value->amount = $request->paidMoney;
                        array_push($tempPartialBillArray, $value);
                    }
                }
                $newGroup->bill_distribution = json_encode($tempPartialBillArray);

                $newGroup->is_accepted = 0; //from kitchen
                $newGroup->is_ready = 0; //decided from kitchen

                $newGroup->is_cancelled = 0; //order receiver decides
                $newGroup->is_settled = 1; //order receiver decides

                //save the group here
                $newGroup->save();

                //save each order items
                $theOrderItems = $request->orderItems;
                foreach ($theOrderItems as $item) {
                    $newItem = new OrderItem;
                    $newItem->order_group_id = $newGroup->id;

                    //name and group name
                    $newItem->food_item = $item['item']['name'];
                    $newItem->food_group = $item['item']['food_group'];

                    //price
                    if ($item['item']['has_variation'] == "1") {
                        $newItem->price = $item['variation']['food_with_variation_price'] * $item['quantity'];
                    } else {
                        $newItem->price = $item['item']['price'] * $item['quantity'];
                    }

                    //variation
                    if (isset($item['variation'])) {
                        $newItem->variation = $item['variation']['variation_name'];
                    }

                    //properties
                    if (isset($item['properties'])) {
                        $propertyArray = array();
                        foreach ($item['properties'] as $property) {
                            $text = new Temporary;
                            $text->property = $property['item']['name'];
                            $text->quantity = $property['quantity'];
                            $text->price_per_qty = (float)$property['item']['extra_price'];
                            $newItem->price = $newItem->price + (float)(($text->price_per_qty * $text->quantity) *$item['quantity']);
                            array_push($propertyArray, $text);
                        }
                        $newItem->properties = json_encode($propertyArray);
                    }

                    $newItem->quantity = $item['quantity'];
                    $newItem->is_cooking = 0;
                    $newItem->is_ready = 0;
                    $newItem->save();
                    $stock = FoodStockBranch::where('branch_id', $request->branch['id'])->where('food_id', $item['item']['id'])->first();
                    if ($stock->qty>0) {
                        $stock->qty = $stock->qty - $item['quantity'];
                    }
                    $stock->save();
                }
                $workPeriod->token = $workPeriod->token + 1;
                $workPeriod->save();

                return [app('App\Http\Controllers\Api\Users\CustomerController')->index(), app('App\Http\Controllers\Api\Dashboard\WorkPeriodController')->index()];
            } else {
                //if payment amount not as expected
                return "paymentIssue";
            }
        } else {
            //if work period is ended
            return "ended";
        }
    }


    //get submitted orders
    public function getSubmitted()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups= collect();
            $tempSubmittedOrderGroups = OrderGroup::where('is_settled', 0)->orderBy('id', 'desc')->get();
            foreach ($tempSubmittedOrderGroups as $tempGroup) {
                $workPeriod = WorkPeriod::where('id', $tempGroup->work_period_id)->first();
                if ($workPeriod->ended_at == null) {
                    $submittedOrderGroups->push($tempGroup);
                }
            }
        } else {
            //get not ended work period of user's branch
            $workPeriod = WorkPeriod::where('branch_id', $user->branch_id)->where('ended_at', null)->first();
            if (!is_null($workPeriod)) {
                $submittedOrderGroups = OrderGroup::where('work_period_id', $workPeriod->id)->where('user_id', $user->id)->where('is_settled', 0)->orderBy('id', 'desc')->get();
            }
        }
        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $theBranch = Branch::where('id', $temp->branch_id)->first();
            $temp->theBranch = $theBranch;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->customer_id = $submittedOrder->customer_id;
            $temp->customer_name = $submittedOrder->customer_name;
            $temp->table_id = $submittedOrder->table_id;
            $temp->table_name = $submittedOrder->table_name;
            $temp->waiter_id = $submittedOrder->waiter_id;
            $temp->waiter_name = $submittedOrder->waiter_name;
            $temp->dept_tag_id = $submittedOrder->dept_tag_id;
            $temp->dept_tag_name = $submittedOrder->dept_tag_name;
            $temp->token = json_decode($submittedOrder->token);
            $temp->total_guest = $submittedOrder->total_guest;
            $temp->service_charge = $submittedOrder->service_charge != null ?$submittedOrder->service_charge:0 ;
            $temp->discount = $submittedOrder->discount != null ?$submittedOrder->discount:0;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->vat_system = $submittedOrder->vat_system;
            $temp->cgst = $submittedOrder->cgst;
            $temp->sgst = $submittedOrder->sgst;
            $temp->dept_commission = $submittedOrder->dept_commission;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->bill_distribution = json_decode($submittedOrder->bill_distribution);
            $temp->paid_amount = $submittedOrder->paid_amount;
            $temp->return_amount = $submittedOrder->return_amount;
            $temp->is_paid = $submittedOrder->is_paid;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_settled = $submittedOrder->is_settled;
            $temp->is_ready = $submittedOrder->is_ready;
            //get order items here
            $orderedItems = OrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [customPaginate($modifiedOrderGroups), $modifiedOrderGroups];
    }


    //get settled orders
    public function getSettled()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups= collect();
            $tempSubmittedOrderGroups = OrderGroup::where('is_settled', 1)->orderBy('updated_at', 'desc')->get();
            foreach ($tempSubmittedOrderGroups as $tempGroup) {
                $workPeriod = WorkPeriod::where('id', $tempGroup->work_period_id)->first();
                if ($workPeriod->ended_at == null) {
                    $submittedOrderGroups->push($tempGroup);
                }
            }
        } else {
            //get not ended work period of user's branch
            $workPeriod = WorkPeriod::where('branch_id', $user->branch_id)->where('ended_at', null)->first();
            if (!is_null($workPeriod)) {
                $submittedOrderGroups = OrderGroup::where('work_period_id', $workPeriod->id)->where('user_id', $user->id)->where('is_settled', 1)->orderBy('updated_at', 'desc')->get();
            }
        }
        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $theBranch = Branch::where('id', $temp->branch_id)->first();
            $temp->theBranch = $theBranch;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->customer_id = $submittedOrder->customer_id;
            $temp->customer_name = $submittedOrder->customer_name;
            $temp->table_id = $submittedOrder->table_id;
            $temp->table_name = $submittedOrder->table_name;
            $temp->waiter_id = $submittedOrder->waiter_id;
            $temp->waiter_name = $submittedOrder->waiter_name;
            $temp->dept_tag_id = $submittedOrder->dept_tag_id;
            $temp->dept_tag_name = $submittedOrder->dept_tag_name;
            $temp->token = json_decode($submittedOrder->token);
            $temp->total_guest = $submittedOrder->total_guest;
            $temp->service_charge = $submittedOrder->service_charge != null ?$submittedOrder->service_charge:0 ;
            $temp->discount = $submittedOrder->discount != null ?$submittedOrder->discount:0;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->vat_system = $submittedOrder->vat_system;
            $temp->cgst = $submittedOrder->cgst;
            $temp->sgst = $submittedOrder->sgst;
            $temp->dept_commission = $submittedOrder->dept_commission;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->bill_distribution = json_decode($submittedOrder->bill_distribution);
            $temp->paid_amount = $submittedOrder->paid_amount;
            $temp->return_amount = $submittedOrder->return_amount;
            $temp->is_paid = $submittedOrder->is_paid;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_settled = $submittedOrder->is_settled;
            $temp->is_ready = $submittedOrder->is_ready;
            //get order items here
            $orderedItems = OrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [customPaginate($modifiedOrderGroups), $modifiedOrderGroups];
    }

    //from submit orders to settle orders
    public function submitToSettle(Request $request)
    {
        $orderGroup = OrderGroup::where('id', $request->order_group_id)->first();
        $pos_screen = Setting::where('name', 'pos_screen')->first();
        $totalDue = $orderGroup->total_payable - $orderGroup->paid_amount;
        if (!is_null($request->payment_type) && !is_null($request->payment_amount && $request->paidMoney >= $totalDue)) {
            $localCurrency = Currency::where('id', $request->localCurrency['id'])->first();
            //set paid as true
            $orderGroup->is_paid = 1;
            //calc return and paid amount
            $orderGroup->paid_amount = $request->paidMoney + $orderGroup->paid_amount;
            if ($orderGroup->paid_amount >= $orderGroup->total_payable) {
                $orderGroup->return_amount = $orderGroup->paid_amount - $orderGroup->total_payable;
            }

            //partial payment
            // TODO: old multiple payment check
            $tempPartialBillArray = array();
            if ($pos_screen->value == "0") {
                foreach ($request->payment_type as $payment) {
                    if (isset($request->payment_amount[$payment['id']]) && !is_null($request->payment_amount[$payment['id']])) {
                        $value = new Temporary;
                        $value->payment_type = $payment['name'];
                        $value->payment_type_slug = $payment['slug'];
                        $value->amount = $request->payment_amount[$payment['id']] / $localCurrency->rate;
                        array_push($tempPartialBillArray, $value);
                    }
                }
            } else {
                foreach ($request->payment_type as $payment) {
                    $value = new Temporary;
                    $value->payment_type = $payment['name'];
                    $value->payment_type_slug = $payment['slug'];
                    $value->amount = $request->paidMoney;
                    array_push($tempPartialBillArray, $value);
                }
            }

            $orderGroup->bill_distribution = json_encode($tempPartialBillArray);

            $orderGroup->is_accepted = 1; //true if order served
            $orderGroup->is_ready = 1; //true if order served
            $orderGroup->is_cancelled = 0; //order receiver decides
            $orderGroup->is_settled = 1; // true in this function
            $orderGroup->save();

            //return submitted orders here
            $user = Auth::user();
            if ($user->branch_id == null) {
                $submittedOrderGroups= collect();
                $tempSubmittedOrderGroups = OrderGroup::where('is_settled', 1)->orderBy('updated_at', 'desc')->get();
                foreach ($tempSubmittedOrderGroups as $tempGroup) {
                    $workPeriod = WorkPeriod::where('id', $tempGroup->work_period_id)->first();
                    if ($workPeriod->ended_at == null) {
                        $submittedOrderGroups->push($tempGroup);
                    }
                }
            } else {
                //get not ended work period of user's branch
                $workPeriod = WorkPeriod::where('branch_id', $user->branch_id)->where('ended_at', null)->first();
                if (!is_null($workPeriod)) {
                    $submittedOrderGroups = OrderGroup::where('work_period_id', $workPeriod->id)->where('user_id', $user->id)->where('is_settled', 1)->orderBy('updated_at', 'desc')->get();
                }
            }
            $modifiedOrderGroups = array();
            foreach ($submittedOrderGroups as $submittedOrder) {
                $temp = new Temporary;
                $temp->id = $submittedOrder->id;
                $temp->work_period_id = $submittedOrder->work_period_id;
                $temp->user_id = $submittedOrder->user_id;
                $temp->user_name = $submittedOrder->user_name;
                $temp->branch_id = $submittedOrder->branch_id;
                $theBranch = Branch::where('id', $temp->branch_id)->first();
                $temp->theBranch = $theBranch;
                $temp->branch_name = $submittedOrder->branch_name;
                $temp->customer_id = $submittedOrder->customer_id;
                $temp->customer_name = $submittedOrder->customer_name;
                $temp->table_id = $submittedOrder->table_id;
                $temp->table_name = $submittedOrder->table_name;
                $temp->waiter_id = $submittedOrder->waiter_id;
                $temp->waiter_name = $submittedOrder->waiter_name;
                $temp->dept_tag_id = $submittedOrder->dept_tag_id;
                $temp->dept_tag_name = $submittedOrder->dept_tag_name;
                $temp->token = json_decode($submittedOrder->token);
                $temp->total_guest = $submittedOrder->total_guest;
                $temp->service_charge = $submittedOrder->service_charge != null ?$submittedOrder->service_charge:0 ;
                $temp->discount = $submittedOrder->discount != null ?$submittedOrder->discount:0;
                $temp->order_bill = $submittedOrder->order_bill;
                $temp->vat = $submittedOrder->vat;
                $temp->vat_system = $submittedOrder->vat_system;
                $temp->cgst = $submittedOrder->cgst;
                $temp->sgst = $submittedOrder->sgst;
                $temp->dept_commission = $submittedOrder->dept_commission;
                $temp->total_payable = $submittedOrder->total_payable;
                $temp->bill_distribution = json_decode($submittedOrder->bill_distribution);
                $temp->paid_amount = $submittedOrder->paid_amount;
                $temp->return_amount = $submittedOrder->return_amount;
                $temp->is_paid = $submittedOrder->is_paid;
                $temp->is_accepted = $submittedOrder->is_accepted;
                $temp->is_cancelled = $submittedOrder->is_cancelled;
                $temp->is_settled = $submittedOrder->is_settled;
                $temp->is_ready = $submittedOrder->is_ready;
                //get order items here
                $orderedItems = OrderItem::where('order_group_id', $submittedOrder->id)->get();
                $temp->orderedItems = $orderedItems;
                array_push($modifiedOrderGroups, $temp);
            }

            //the updated order group with items
            $temp = new Temporary;
            $temp->id = $orderGroup->id;
            $temp->work_period_id = $orderGroup->work_period_id;
            $temp->user_id = $orderGroup->user_id;
            $temp->user_name = $orderGroup->user_name;
            $temp->branch_id = $orderGroup->branch_id;

            $theBranch = Branch::where('id', $orderGroup->branch_id)->first();
            $temp->theBranch = $theBranch;

            $temp->branch_name = $orderGroup->branch_name;
            $temp->customer_id = $orderGroup->customer_id;
            $temp->customer_name = $orderGroup->customer_name;
            $temp->table_id = $orderGroup->table_id;
            $temp->table_name = $orderGroup->table_name;
            $temp->waiter_id = $orderGroup->waiter_id;
            $temp->waiter_name = $orderGroup->waiter_name;
            $temp->dept_tag_id = $orderGroup->dept_tag_id;
            $temp->dept_tag_name = $orderGroup->dept_tag_name;
            $temp->token = json_decode($orderGroup->token);
            $temp->total_guest = $orderGroup->total_guest;
            $temp->service_charge = $orderGroup->service_charge != null ?$orderGroup->service_charge:0 ;
            $temp->discount = $orderGroup->discount != null ?$orderGroup->discount:0;
            $temp->order_bill = $orderGroup->order_bill;
            $temp->vat = $orderGroup->vat;
            $temp->dept_commission = $orderGroup->dept_commission;
            $temp->total_payable = $orderGroup->total_payable;
            $temp->bill_distribution = $tempPartialBillArray;
            $temp->paid_amount = $orderGroup->paid_amount;
            $temp->return_amount = $orderGroup->return_amount;
            $temp->is_paid = $orderGroup->is_paid;
            $temp->is_accepted = $orderGroup->is_accepted;
            $temp->is_cancelled = $orderGroup->is_cancelled;
            $temp->is_settled = $orderGroup->is_settled;
            $temp->is_ready = $orderGroup->is_ready;
            //get order items here
            $orderedItems = OrderItem::where('order_group_id', $orderGroup->id)->get();
            $tempOrderItems = array();
            //set order item status to true
            foreach ($orderedItems as $item) {
                $item->is_cooking = 1;
                $item->is_ready = 1;
                $item->save();
                array_push($tempOrderItems, $item);
            }
            $temp->orderedItems = $tempOrderItems;
            return [customPaginate($modifiedOrderGroups), $modifiedOrderGroups,$temp];
        } else {
            return "paymentIssue";
        }
    }

    //cancel submitted order here
    public function cancelSubmitted(Request $request)
    {
        $orderGroup = OrderGroup::where('id', $request->id)->first();
        if ($orderGroup->is_accepted == 0) {
            $orderGroup->is_cancelled = 1;
            $orderGroup->save();
        } else {
            return "accepted";
        }
    }

    //mark order and all items ready
    public function settleOrderReady($id)
    {
        $orderGroup = OrderGroup::where('id', $id)->first();
        if ($orderGroup->is_cancelled != 1) {
            $items = OrderItem::where('order_group_id', $orderGroup->id)->get();
            foreach ($items as $item) {
                $item->is_cooking = 1;
                $item->is_ready = 1;
                $item->save();
            }
            $orderGroup->is_accepted = 1;
            $orderGroup->is_ready = 1;
            $orderGroup->save();
        } else {
            abort(404);
        }
    }
}
