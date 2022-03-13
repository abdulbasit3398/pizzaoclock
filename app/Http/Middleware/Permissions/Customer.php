<?php

namespace App\Http\Middleware\Permissions;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Customer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $permissionGroup = PermissionGroup::where('id', Auth::user()->permission_group_id)->first();
        $permissionArray = json_decode($permissionGroup->permission_id_array);
        $permission = Permission::where('name','Manage')->first();
        $permission2 = Permission::where('name','Customer')->first();
        $permission3 = Permission::where('name','POS')->first();
        $permission4= Permission::where('name','Report')->first();
        $permission5= Permission::where('name','Work period')->first();
        if(in_array($permission->id,$permissionArray) || in_array($permission2->id,$permissionArray) || in_array($permission3->id,$permissionArray) || in_array($permission4->id,$permissionArray)|| in_array($permission5->id,$permissionArray)){
            return $next($request);
        }else{
            return false;
        }
    }
}
