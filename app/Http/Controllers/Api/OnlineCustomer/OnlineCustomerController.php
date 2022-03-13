<?php

namespace App\Http\Controllers\Api\OnlineCustomer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FoodGroup;
use App\Models\Branch;
use App\Models\FoodItem;
use App\Models\OnlineOrderItem;
use App\Models\FoodStockBranch;
use App\Models\OnlineOrderGroup;
use App\Models\FoodWithVariation;
use App\Models\PropertyItem;
use App\Models\PropertyGroup;
use App\Models\Temporary;
use App\Models\Variation;
use App\Models\WorkPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Auth;

class OnlineCustomerController extends Controller
{
    //homepage
    public function index()
    {
        $foodGroupsAll = FoodGroup::all();
        $workPeriods = WorkPeriod::where('ended_at', null)->get();
        $propertiesGroupsAll = PropertyGroup::all();
        $foods = FoodItem::all();
        $foodStock = FoodStockBranch::all();
        $modifiedFoods = array();
        foreach ($foods as $food) {
            $temp = new Temporary;
            $temp->id = $food->id;

            $temp->food_group_id = $food->food_group_id;
            $foodGroup = FoodGroup::where('id', $food->food_group_id)->first();
            $temp->food_group = $foodGroup->name;

            $temp->name = $food->name;
            $temp->slug = $food->slug;

            if (!is_null($food->image)) {
                $temp->image = asset('').$food->image;
            } else {
                $temp->image = null;
            }

            //variations
            $temp->has_variation = $food->has_variation;
            if ($food->has_variation == 1) {
                $variations = FoodWithVariation::where('food_item_id', $food->id)->get();
                $modifiedVariations = array();
                foreach ($variations as $variation) {
                    $variationItem = Variation::where('id', $variation->variation_id)->first();
                    $tempVariation = new Temporary;

                    //food with variation table
                    $tempVariation->food_with_variation_id = $variation->id;
                    $tempVariation->food_with_variation_price = $variation->price;

                    //variation table
                    $tempVariation->variation_name = $variationItem->name;
                    array_push($modifiedVariations, $tempVariation);
                }
                $temp->variations = $modifiedVariations;
            } else {
                $temp->price = $food->price;
            }

            //isSpecial
            $temp->is_special = $food->is_special;

            //properties
            $temp->has_property = $food->has_property;
            if ($food->has_property == 1) {
                $property_groups = json_decode($food->property_group_ids);
                $modifiedPropertyGroups = array();
                foreach ($property_groups as $property_group) {
                    $property_items = PropertyItem::where('property_group_id', $property_group)->get();
                    if (!is_null($property_items)) {
                        array_push($modifiedPropertyGroups, $property_items);
                    }
                }
                $temp->properties = $modifiedPropertyGroups;
                $temp->property_groups = $property_groups;
            }

            //end $foods foreach after pushing
            array_push($modifiedFoods, $temp);
        }
        return [$modifiedFoods, $foodGroupsAll, $propertiesGroupsAll, $workPeriods,$foodStock];
    }

    //register new user as customer
    public function store(Request $request)
    {
        $request->validate(
            [
          'phn_no'   => ['unique:users'],
          'email'    => ['required', 'unique:users'],
          'password'    => ['confirmed'],
      ],
            [
              'phn_no.unique'               => 'An user exists with this phone number',
              'email.unique'                => 'An user exists with this email',
              'password.confirmed'          => 'Password confirmation does not match',
          ]
        );
        $user = new User;
        $user->name = $request->name;
        $user->email = Str::lower($request->email);
        $user->phn_no = $request->phn_no;
        $user->user_type = "customer";
        $user->is_active = true;
        $user->is_banned = false;
        $user->permission_group_id = 0;
        $user->branch_id = null;
        $user->password = Hash::make($request->password);
        $user->slug =  Str::random(3).'-'.time().'-'.Str::slug($request->name);

        $userImage = $request->file('image');
        if (!is_null($userImage)) {
            $request->validate(
                [
              'image'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
          ],
                [
                  'image.mimes'                => 'Please select a valid image file',
                  'image.max'                  => 'Please select a file less than 5MB'
              ]
            );
            //storing file to server
            $name = time()."-".Str::slug($userImage->getClientOriginalName()).".".$userImage->getClientOriginalExtension();
            $userImage->move(public_path().'/images/user/', $name);
            //updating db
            $user->image = '/images/user/'.$name;
        }
        $user->save();
    }

    //store order
    public function storeOrder(Request $request)
    {
        //check if the work period is not ended
        $workPeriod = WorkPeriod::where('id', $request->workPeriod['id'])->first();
        if ($workPeriod->ended_at == null) {
            //create order group
            $newGroup = new OnlineOrderGroup;
            $newGroup->work_period_id = $workPeriod->id;
            $newGroup->user_id = Auth::user()->id;
            $newGroup->user_name = Auth::user()->name;
            //token
            $newGroup->token = rand(2, 9999);

            //branch
            $theBranch = Branch::where('id', $request->branch)->first();
            $newGroup->branch_id = $request->branch;
            $newGroup->branch_name = $theBranch->name;

            //order bill == subtotal
            $newGroup->order_bill = $request->subTotal;
            $newGroup->vat = $request->vat;
            $newGroup->vat_system = getSystemSettings('vat_system');
            if (getSystemSettings('vat_system') != "igst") {
                $newGroup->cgst =  $request->subTotal*((float)getSystemSettings('cgst')/100);
                $newGroup->sgst = $request->subTotal*((float)getSystemSettings('sgst')/100);
            }
            $newGroup->total_payable = round($request->subTotal+$request->vat, 2);
            //payment method
            $newGroup->payment_method = "COD";


            $newGroup->is_settled = 0; //order receiver decides
            $newGroup->is_ready = 0; //decided from kitchen

            $newGroup->is_accepted = 0;
            $newGroup->is_accepted_by_kitchen = 0;
            $newGroup->is_cancelled = 0;
            $newGroup->note_to_rider = $request->note;
            $newGroup->delivery_phn_no = $request->phn_no;
            $newGroup->delivery_address = $request->address;
            $newGroup->is_delivered = 0;

            //save the group here
            $newGroup->save();


            //save each order items
            $theOrderItems = $request->items;
            foreach ($theOrderItems as $item) {
                $newItem = new OnlineOrderItem;
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
                        $text->property = $property['name'];
                        $text->quantity = $property['quantity'];
                        $text->price_per_qty = (float)$property['extra_price'];
                        $newItem->price = $newItem->price + (float)(($text->price_per_qty * $text->quantity) *$item['quantity']);
                        array_push($propertyArray, $text);
                    }
                    $newItem->properties = json_encode($propertyArray);
                }

                $newItem->quantity = $item['quantity'];

                $newItem->is_cooking = 0;
                $newItem->is_ready = 0;
                $newItem->save();
                $stock = FoodStockBranch::where('branch_id', $request->branch)->where('food_id', $item['item']['id'])->first();
                $stock->qty = $stock->qty - $item['quantity'];
                $stock->save();
            }
            $allStock = FoodStockBranch::all();
            return $allStock;
        } else {
            //if work period is ended
            return "ended";
        }
    }

    //get pending orders counting in pos page
    public function pending()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups= collect();
            $tempSubmittedOrderGroups = OnlineOrderGroup::where('is_accepted', 0)->where('is_cancelled', 0)->get();
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
                $submittedOrderGroups = OnlineOrderGroup::where('work_period_id', $workPeriod->id)->where('is_accepted', 0)->where('is_cancelled', 0)->get();
            }
        }
        return count($submittedOrderGroups);
    }

    //get online orders in pos page
    public function onlineOrders()
    {
        $user = Auth::user();
        if ($user->branch_id == null) {
            $submittedOrderGroups= collect();
            $tempSubmittedOrderGroups = OnlineOrderGroup::latest()->get();
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
                $submittedOrderGroups = OnlineOrderGroup::where('work_period_id', $workPeriod->id)->latest()->get();
            }
        }

        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->token = json_decode($submittedOrder->token);
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $theBranch = Branch::where('id', $temp->branch_id)->first();
            $temp->theBranch = $theBranch;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->vat_system = $submittedOrder->vat_system;
            $temp->cgst = $submittedOrder->cgst;
            $temp->sgst = $submittedOrder->sgst;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_accepted_by_kitchen = $submittedOrder->is_accepted_by_kitchen;

            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_delivered = $submittedOrder->is_delivered;
            $temp->payment_method = $submittedOrder->payment_method;
            $temp->note_to_rider = $submittedOrder->note_to_rider;
            $temp->delivery_phn_no = $submittedOrder->delivery_phn_no;
            $temp->delivery_address = $submittedOrder->delivery_address;
            $temp->time_to_deliver = $submittedOrder->time_to_deliver;
            $temp->pos_user_id =$submittedOrder->pos_user_id;
            $temp->reason_of_cancel =$submittedOrder->reason_of_cancel;
            $temp->created_at = $submittedOrder->created_at;
            //delivery boy
            $temp->delivery_boy_name = $submittedOrder->delivery_boy_name;
            $temp->delivery_boy_id = $submittedOrder->delivery_boy_id;
            $temp->delivery_status = $submittedOrder->delivery_status;
            //get order items here
            $orderedItems = OnlineOrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [customPaginate($modifiedOrderGroups), $modifiedOrderGroups];
    }


    //accept online orders by pos users
    public function acceptOnlineOrders(Request $request)
    {
        $orderGroup = OnlineOrderGroup::where('id', $request->id)->first();
        if (!is_null($orderGroup)) {
            $deliveryMan = User::where('id', $request->delivery_man_id)->first();
            $orderGroup->pos_user_id = Auth::user()->id;
            $orderGroup->is_accepted = 1;
            $orderGroup->time_to_deliver = $request->time_to_deliver;
            $orderGroup->delivery_boy_id = $request->delivery_man_id;
            $orderGroup->delivery_boy_name = $deliveryMan->name;
            $orderGroup->delivery_status = "pending";
            $orderGroup->save();
        }
    }

    //cancel online orders by pos users
    public function cancelOnlineOrders(Request $request)
    {
        $orderGroup = OnlineOrderGroup::where('id', $request->id)->first();

        if (!is_null($orderGroup)) {
            $orderItems = OnlineOrderItem::where('order_group_id', $orderGroup->id)->get();
            foreach ($orderItems as $item) {
                $food = FoodItem::where('name', $item->food_item)->first();
                if (!is_null($food)) {
                    $stock = FoodStockBranch::where('branch_id', $orderGroup->branch_id)->where('food_id', $food->id)->first();
                    $stock->qty = $stock->qty + $item->quantity;
                    $stock->save();
                }
            }

            $orderGroup->pos_user_id = Auth::user()->id;
            $orderGroup->is_cancelled = 1;
            $orderGroup->reason_of_cancel = $request->reason_of_cancel;
            $orderGroup->save();
        }
    }


    //update online customer profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if ($request->onlyPassword == "no") {
            if ($user->user_type == "customer") {
                $user->name = $request->name;
                $user->address = $request->address;
                //email validation
                if ($request->email != $user->email) {
                    $request->validate(
                        [
                    'email'    => ['unique:users'],
                ],
                        [
                        'email.unique'                => 'An user exists with this email',
                    ]
                    );
                    $user->email = Str::lower($request->email);
                }
                //phn_no validadtion
                if ($request->phn_no) {
                    if ($request->phn_no != $user->phn_no) {
                        $request->validate(
                            [
                        'phn_no' => ['unique:users']
                    ],
                            [
                            'phn_no.unique' => 'An user exists with this phone number',
                        ]
                        );
                        $user->phn_no = $request->phn_no;
                    }
                }
                //image
                $userImage = $request->file('image');
                if (!is_null($userImage)) {
                    $request->validate(
                        [
                    'image'  => ['nullable','file','mimes:jpg,jpeg,png,gif','max:5000']
                ],
                        [
                        'image.mimes'                => 'Please select a valid image file',
                        'image.max'                  => 'Please select a file less than 5MB'
                    ]
                    );
                    //delete old image
                    if (!is_null($user->image)) {
                        if (file_exists(public_path($user->image))) {
                            unlink(public_path($user->image));
                        }
                    }

                    //storing file to server
                    $name = time()."-".Str::slug($userImage->getClientOriginalName()).".".$userImage->getClientOriginalExtension();
                    $userImage->move(public_path().'/images/user/', $name);
                    //updating db
                    $user->image = '/images/user/'.$name;
                }
            }
        } else {
            //password validation
            if ($request->password) {
                $request->validate(
                    [
                'password'    => ['confirmed'],
            ],
                    [
                    'password.confirmed'          => 'Password confirmation does not match',
                ]
                );
                $user->password = Hash::make($request->password);
            }
        }
        $user->save();
        return $user;
    }

    //get online orders customer
    public function onlineOrdersCustomer()
    {
        $user = Auth::user();
        $submittedOrderGroups = OnlineOrderGroup::where('user_id', $user->id)->latest()->get();
        $modifiedOrderGroups = array();
        foreach ($submittedOrderGroups as $submittedOrder) {
            $temp = new Temporary;
            $temp->id = $submittedOrder->id;
            $temp->token = json_decode($submittedOrder->token);
            $temp->work_period_id = $submittedOrder->work_period_id;
            $temp->user_id = $submittedOrder->user_id;
            $temp->user_name = $submittedOrder->user_name;
            $temp->branch_id = $submittedOrder->branch_id;
            $theBranch = Branch::where('id', $temp->branch_id)->first();
            $temp->theBranch = $theBranch;
            $temp->branch_name = $submittedOrder->branch_name;
            $temp->order_bill = $submittedOrder->order_bill;
            $temp->vat = $submittedOrder->vat;
            $temp->vat_system = $submittedOrder->vat_system;
            $temp->cgst = $submittedOrder->cgst;
            $temp->sgst = $submittedOrder->sgst;
            $temp->total_payable = $submittedOrder->total_payable;
            $temp->is_accepted = $submittedOrder->is_accepted;
            $temp->is_accepted_by_kitchen = $submittedOrder->is_accepted_by_kitchen;

            $temp->is_cancelled = $submittedOrder->is_cancelled;
            $temp->is_delivered = $submittedOrder->is_delivered;
            $temp->payment_method = $submittedOrder->payment_method;
            $temp->note_to_rider = $submittedOrder->note_to_rider;
            $temp->delivery_phn_no = $submittedOrder->delivery_phn_no;
            $temp->delivery_address = $submittedOrder->delivery_address;
            $temp->time_to_deliver = $submittedOrder->time_to_deliver;
            $temp->pos_user_id =$submittedOrder->pos_user_id;
            $temp->reason_of_cancel =$submittedOrder->reason_of_cancel;
            $temp->created_at =$submittedOrder->created_at;
            //get order items here
            $orderedItems = OnlineOrderItem::where('order_group_id', $submittedOrder->id)->get();
            $temp->orderedItems = $orderedItems;
            array_push($modifiedOrderGroups, $temp);
        }
        return [customPaginate($modifiedOrderGroups), $modifiedOrderGroups];
    }
}
