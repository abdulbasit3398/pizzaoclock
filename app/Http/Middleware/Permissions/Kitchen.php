<?php

namespace App\Http\Middleware\Permissions;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Kitchen
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
        $permission = Permission::where('name','Kitchen')->first();
        if(in_array($permission->id,$permissionArray)){
            return $next($request);
        }
    }
}
