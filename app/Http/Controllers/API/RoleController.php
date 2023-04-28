<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Validator;
use DB;
use Auth;
use App\User;

class RoleController extends Controller
{
    public function index()
    {   
        // $user_permissions =  Auth::user()->with('roles', 'roles.permissions')->get();
        // return view('pages.diagnosis.create', compact('patient_service', 'ps_item_id', 'file_no', 'user_permissions'));
        $roles = Role::with('permissions')->orderBy('id', 'Asc')->get();
        $permissions = Permission::all();
        return response()->json(['roles' => $roles, 'permissions' => $permissions], 200);
    }

    public function create()
    {   
        $permissions = Permission::all();
        return response()->json(['permissions' => $permissions], 200);
    }


    public function store(Request $request)
    {   
        // return response()->json($request->all());
        
        $rules = [
            'name.required' => 'Please enter role',
            'name.unique' => 'Role already exists'
        ];

        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:roles,name'
        ], $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $role = new Role();
        $role->name = $request->get('name');
        $role->guard_name = 'web';
        $role->save();
        $role->syncPermissions($request->get('permission'));

        return response()->json(['success' => 'Record has successfully added', 'role' => $role], 200);
    }


    public function edit(Request $request)
    {   
        $roleid = $request->get('roleid');

        $role = Role::find($roleid);

        //if record is empty then display error page
        if(empty($role->id))
        {
            return abort(404, 'Not Found');
        }

        $permissions = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_id", $roleid)
                             ->pluck('permission_id')
                             ->all();
        // return view('pages.service.edit', compact('service'));
        return response()->json(['role' => $role, 'permissions' => $permissions, 'rolePermissions' => $rolePermissions], 200);

    }


    public function update(Request $request, $roleid)
    {   

        $rules = [
            'name.required' => 'Please enter role',
            'name.unique' => 'Role already exists'
        ];

        $validator = Validator::make($request->all(),[
            'name' => 'required|unique:roles,name,'.$roleid
        ], $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        // Administrator Role
        if($roleid == 1)
        {   
            return abort(403, 'Forbidden');
            // return response()->json(['error' => "You can't update role Admin"], 200);
        }

        $role = Role::find($roleid);

        //if record is empty then display error page
        if(empty($role->id))
        {
            return abort(404, 'Not Found');
        }

        $role->name = $request->get('name');
        $role->save();

        $role->syncPermissions($request->get('permission'));

        $user_roles = Auth::user()->roles->pluck('name')->all();

        $user_permissions = Auth::user()->getAllPermissions()->pluck('name');

        return response()->json([
            'success' => 'Record has been updated', 
            'user_roles' => $user_roles, 
            'user_permissions' => $user_permissions,
            'role' => $role
        ], 200);
    }


    public function delete(Request $request)
    {   
        $roleid = $request->get('roleid');

        // Administrator Role
        if($roleid == 1)
        {   
            return abort(403, 'Forbidden');
            // return response()->json(['error' => "You can't update role Admin"], 200);
        }

        $role = Role::find($roleid);

        //if record is empty then display error page
        if(empty($role->id))
        {
            return abort(404, 'Not Found');
        }

        $role->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }
}
