<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Temporary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    //get all permission group
    public function index()
    {
        $groups = PermissionGroup::where('name', '!=' ,'Customer')->get();
        $modifiedGroups = array();
        foreach ($groups as $group) {
            $temp = new Temporary;
            $temp->id = $group->id;
            $temp->name = $group->name;
            $temp->slug = $group->slug;
            $temp->permission_array_id = json_decode($group->permission_id_array);
            array_push($modifiedGroups, $temp);
        }
        $permissions = Permission::orderBy('name', 'asc')->get()->toArray();
        return [customPaginate($modifiedGroups), $modifiedGroups, $permissions];
    }


    //save new permission group
    public function store(Request $request)
    {
        $request->validate([
            'name'   => ['required', 'unique:permission_groups']
        ],
            [
                'name.unique'                => 'A group already exists with this name'
            ]
        );

        $newGroup = new PermissionGroup;
        $newGroup->name = $request->name;
        $newGroup->slug =  Str::slug($request->name);
        $new_array = array();
        foreach ($request->permissionIds as $permissionId) {
            array_push($new_array, $permissionId['id']);
        }
        $newGroup->permission_id_array =  json_encode($new_array);
        $newGroup->save();

        return $this->index();
    }

    //update permission group
    public function update(Request $request)
    {
        $newGroup = PermissionGroup::where('slug', $request->editSlug)->first();
        if($request->name != $newGroup->name){
            $request->validate([
                'name'   => ['required', 'unique:permission_groups']
            ],
                [
                    'name.unique'                => 'A group already exists with this name'
                ]
            );
        }
        $newGroup->name = $request->name;
        $newGroup->slug =  Str::slug($request->name);

        if(!is_null($request->permissionIds)){
            $new_array = array();
            foreach ($request->permissionIds as $permissionId) {
                array_push($new_array, $permissionId['id']);
            }
            $newGroup->permission_id_array =  json_encode($new_array);
        }
        $newGroup->save();

        return $this->index();
    }

    //delete group
    public function destroy($slug){
        $group = PermissionGroup::where('slug', $slug)->first();
        $user = User::where('permission_group_id',$group->id)->first();
        if(is_null($user)){
            if($group->name != "Admin" || $group->name != "Customer"){
                $group->delete();
                return $this->index();
            }else{
                return "This group can not be deleted";
            }
        }else{
            return "Please remove this group from users first";
        }
    }
}
