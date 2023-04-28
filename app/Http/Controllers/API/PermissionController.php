<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Validator;
use Auth;

class PermissionController extends Controller
{
    public function index()
    {       
        $permissions = Permission::all();
        return response()->json(['permissions' => $permissions], 200);
    }

    public function create()
    {
        return view('pages.permissions.create');
    }


    public function store(Request $request)
    {   
        
        $rules = [
            'name.required' => 'Please enter permission',
            'name.unique' => 'Permission already exists'
        ];

        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:permissions,name',
        ], $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $permission = new Permission();
        $permission->name = $request->get('name');
        $permission->guard_name = 'web';
        $permission->save();

        // Administrator Role
        $role = Role::find(1);

        $permissions = Permission::pluck('id','id')->all();

        $role->syncPermissions($permissions);

        return response()->json(['success' => 'Record has successfully added', 'permission' => $permission], 200);
    }


    public function edit(Request $request)
    {   
        $permissionid = $request->get('permissionid');

        $permission = Permission::find($permissionid);

        //if record is empty then display error page
        if(empty($permission->id))
        {
            return abort(404, 'Not Found');
        }
        
        // return view('pages.service.edit', compact('service'));
        return response()->json($permission, 200);

    }


    public function update(Request $request, $permissionid)
    {   

        $rules = [
            'name.required' => 'Please enter permission',
            'name.unique' => 'Permission already exists'
        ];

        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:permissions,name,'.$permissionid
        ], $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $permission = Permission::find($permissionid);

        //if record is empty then display error page
        if(empty($permission->id))
        {
            return abort(404, 'Not Found');
        }

        $permission->name = $request->get('name');
        $permission->save();

        // Administrator Role
        $role = Role::find(1);

        $permissions = Permission::pluck('id','id')->all();

        $role->syncPermissions($permissions);

        $user_roles = Auth::user()->roles->pluck('name')->all();

        $user_permissions = Auth::user()->getAllPermissions()->pluck('name');

        return response()->json([
            'success' => 'Record has been updated',
            'user_roles' => $user_roles, 
            'user_permissions' => $user_permissions]
        );
    }


    public function delete(Request $request)
    {   
        $permissionid = $request->get('permissionid');
        $permission = Permission::find($permissionid);
        
        //if record is empty then display error page
        if(empty($permission->id))
        {
            return abort(404, 'Not Found');
        }

        $permission->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }
}
