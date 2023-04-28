<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\SapTable;
use App\SapTableField;
use App\SapTableFieldOption;
use Validator;
use Auth;

class SAPUDFController extends Controller
{
    public function index()
    {   


        $sap_tables = SapTable::with('sap_table_fields')
                              ->with('sap_table_fields.sap_table_field_options')
                              ->get();
        
        // foreach ($sap_tables as $key => $value) {

        //     $table_name = $value->table_name;
        //     if (!Schema::hasTable($table_name)){

        //         Schema::create($table_name, function (Blueprint $table) use ($value) {
        //             $table->id();
        //             foreach ($value->sap_table_fields as $field) {

        //                 $table->{$field->type}($field->field_name, $field->length)->nullable($field->is_require ? false : true);
        //             }
        //             $table->timestamps();
        //         });
        //     }
        //     else
        //     {  
        //         return response()->json(['error' => 'Table name '. strtolower($table_name) . ' already exists.'], 200);
        //     }

        // }

        $table_name = 'oinv';

        // if (Schema::hasTable($table_name)){
            
        //     $field_arr = [
        //         'field_type' => 'string',
        //         'field_name' => 'sub_cat_2',
        //         'field_length' => 200,
        //         'field_is_required' => true,
        //     ];

        //     if(!Schema::hasColumn($table_name, $field_arr['field_name']))
        //     {   
        //         Schema::table($table_name, function (Blueprint $table) use ($field_arr) {   

        //             if($field_arr['field_type'] === 'integer')
        //             {
        //                 $table->{ $field_arr['field_type'] }( $field_arr['field_name'] )->nullable( $field_arr['field_is_required'] ? false : true );
        //             }
        //             else
        //             {
        //                 $table->{ $field_arr['field_type'] }( $field_arr['field_name'], $field_arr['field_length'] )->nullable( $field_arr['field_is_required'] ? false : true );
        //             }

        //         });
        //     }
        //     else
        //     {
        //         return response()->json(['error' => 'Columun ' . $field_arr['field_name'] . ' already exists.'], 200);
        //     }
                
        // }
        
        return response()->json(['sap_tables' => $sap_tables], 200);
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

    public function migrate(){

    }
}
