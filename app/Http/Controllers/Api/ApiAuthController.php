<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use App\Notifications\PasswordReset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiAuthController extends Controller
{
    //Login user (create the token)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = $request->user();
        if ($user->is_banned == 0) {
            if (is_null($user->remember_token)) {
                $user->remember_token = time().Str::random(30);
            }
            $user->save();

            //getting permissions
            $permissionGroup = PermissionGroup::where('id', $user->permission_group_id)->first();
            if (!is_null($permissionGroup)) {
                $allPermissions = json_decode($permissionGroup->permission_id_array);
            } else {
                $allPermissions = [];
            }
            $userPermissions = array();
            foreach ($allPermissions as $singlePermission) {
                $permission = Permission::where('id', $singlePermission)->first();
                array_push($userPermissions, $permission->name);
            }

            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            if ($request->remember_me == true) {
                $token->expires_at = Carbon::parse($tokenResult->token->expires_at)->toDateTimeString();
            }
            $token->save();

            //returning token and user as response
            return response()->json([[
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
            ],$user, $user->is_banned,$userPermissions]);
        } else {
            $user->tokens->each(function ($token, $key) {
                $token->delete();
            });
            return response()->json(["No access","No access",$user->is_banned]);
        }
    }

    //Logout user (Revoke the token)
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    //Get the authenticated User
    public function user(Request $request)
    {
        $user = $request->user();
        //getting permissions
        $permissionGroup = PermissionGroup::where('id', $user->permission_group_id)->first();
        if (!is_null($permissionGroup)) {
            $allPermissions = json_decode($permissionGroup->permission_id_array);
        } else {
            $allPermissions = [];
        }
        $userPermissions = array();
        foreach ($allPermissions as $singlePermission) {
            $permission = Permission::where('id', $singlePermission)->first();
            array_push($userPermissions, $permission->name);
        }
        return response()->json([$user,$userPermissions]);
    }

    //send token to email
    public function sendEmailToken(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!is_null($user)) {
            if ($user->is_banned == 0) {
                if (is_null($user->remember_token)) {
                    $user->remember_token = time().Str::random(30);
                }
                $user->save();
                $token = $user->remember_token;
                try {
                    $user->notify(new PasswordReset($token));
                    return "ok";
                } catch (\Exception $e) {
                    return "smtp";
                }
            } else {
                return "banned";
            }
        } else {
            return "noUser";
        }
    }

    //save new password
    public function setNewPassword(Request $request)
    {
        $request->validate([
            'password'          => 'min:6|confirmed'
        ]);
        $user = User::where('email', $request->email)->where('remember_token', $request->token)->first();
        if (!is_null($user)) {
            if ($request->password) {
                if ($request->password !=null || $request->password != "") {
                    $user->password = Hash::make($request->password);
                    $user->remember_token = time().Str::random(30);
                    $user->save();
                    return "ok";
                }
            }
        } else {
            return "noUser";
        }
    }
}
