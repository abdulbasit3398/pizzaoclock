<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Temporary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminStaffController extends Controller
{
    //get all users
    public function index(Request $request)
    {
        $users = User::where('user_type', '!=', 'customer')->where('user_type', '!=', 'deliveryMan')->get();
        $modifiedUsers = array();
        foreach ($users as $user) {
            $temp = new Temporary;
            $temp->id = $user->id;
            $temp->name = $user->name;
            $temp->email = $user->email;
            $temp->phn_no = $user->phn_no;
            $temp->branch_id = $user->branch_id;
            $temp->is_banned = $user->is_banned;
            $temp->is_active = $user->is_active;
            $branch = Branch::where('id', $user->branch_id)->first();
            if (!is_null($branch)) {
                $temp->branch_name = $branch->name;
            } else {
                $temp->branch_name = null;
            }
            $temp->slug = $user->slug;
            if (!is_null($user->image)) {
                if ($request->ip()=="127.0.0.1" || $request->ip()=="::1") {
                    $theImage = substr($user->image, 1);
                    $temp->image = asset('').$theImage;
                } else {
                    $temp->image = asset('').$user->image;
                }
            } else {
                $temp->image = null;
            }
            $temp->user_type = $user->user_type;
            $temp->permission_group_id = $user->permission_group_id;
            array_push($modifiedUsers, $temp);
        }
        return [customPaginate($modifiedUsers), $modifiedUsers];
    }

    //save new User
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
        $user->user_type = $request->user_type;
        $user->is_active = true;
        $user->is_banned = false;
        $user->permission_group_id = $request->permission_group_id;
        if ($request->user_type == "admin") {
            $user->branch_id = null;
        } else {
            $user->branch_id = $request->branch_id;
        }
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

        //get all users
        return $this->index($request);
    }

    //update user
    public function update(Request $request)
    {
        $user = User::where('slug', $request->editSlug)->first();
        $user->name = $request->name;
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
        $user->user_type = $request->user_type;

        if ($request->permission_group_id) {
            $user->permission_group_id = $request->permission_group_id;
        }

        if ($request->user_type == "admin") {
            $user->branch_id = null;
        } else {
            if ($request->branch_id) {
                $user->branch_id = $request->branch_id;
            }
        }

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
        $user->save();

        //get all users
        return $this->index($request);
    }

    //update auth user profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
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
        $user->save();
    }

    //change status of user
    public function destroy($slug, Request $request)
    {
        $user = User::where('slug', $slug)->first();
        if ($user->is_banned == true) {
            if ($user->branch_id == null) {
                $user->is_banned = false;
                $user->save();
            } else {
                $branch = Branch::where('id', $user->branch_id)->first();
                if (!is_null($branch)) {
                    $user->is_banned = false;
                    $user->save();
                    //get all users
                    return $this->index($request);
                } else {
                    return "noBranch";
                }
            }
        } else {
            $user->is_banned = true;
            $user->tokens->each(function ($token, $key) {
                $token->delete();
            });
            $user->save();
            //get all users
            return $this->index($request);
        }
    }
}
