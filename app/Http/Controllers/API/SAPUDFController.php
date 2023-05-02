<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\SapTable;
use App\SapTableField;
use App\SapTableFieldOption;
use Validator;
use Auth;
// use Rule;

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
        // START validate SAP Table

        $valid_fields = [
            'table_name' => 'required|max:64|unique:sap_tables,table_name',
            'description' => 'required|max:20',
            'type' => 'required',
            'sap_table_fields' => 'required'
        ];

        $rules = [
            'table_name.required' => 'Table Name is required',
            'table_name.max' => 'Table Name length must be less then 64 or equal',
            'table_name.unique' => 'Table Name already exists',
            'description.required' => 'Table Description is required',
            'description.max' => 'Table Description maximum length exceeds, must be 20 characters and less',
            'type.required' => 'Table Type is required',
            'sap_table_fields.required' => 'Sap Table Fields are required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        // END validate SAP Table

        // START validate SAP Table Fields

        $sap_table_fields = $request->get('sap_table_fields');
        $sap_table = SapTable::where('table_name', '=', $request->get('table_name'))->get();
        $sap_table_id = $sap_table->count() ? $sap_table->first()->id : '';

        //Validate of field_name has duplicates
        foreach ($sap_table_fields as $key => $value) 
        {   
            foreach($sap_table_fields as $i => $val)
            {   
                // exclude current row for validation of duplicate
                if($i !== $key)
                {
                    if($val['field_name'] === $value['field_name'])
                    {
                        return response()->json(['field_name' => 'Duplicate Field Name ' . $val['field_name'], 'index' => $i], 200);
                    }
                }
            }
            
            $field_name = $value['field_name'];
            $table_name = $request->get('table_name');
            $valid_fields = [
                'field_name' => 'required|max:64',
                // 'field_name' => [
                //     'required',
                //     'max:64',
                //     Rule::unique('sap_table_fields')->where(function ($query) use ($field_name, $sap_table_id) {
                //         return $query->where('field_name', $field_name)
                //                      ->where('sap_table_id', $sap_table_id);
                //     }),
                // ],
                'description' => 'required|max:20',
                'type' => 'required',
            ];

            $rules = [
                'field_name.required' => 'Field Name is required',
                'field_name.unique' => 'Field Name already exists',
                'field_name.max' => 'Field Name length must be less then 64 or equal',
                'description.required' => 'Field Description is required',
                'description.max' => 'Field Description maximum length exceeds, must be 20 characters and less',
                'type.required' => 'Field Type is required',
            ];
            
            if($value['type'] === 'string')
            {   
                $valid_fields['length'] = 'required|numeric|between:1,255';
                $rules['length.required'] = 'Field Length is required';
                $rules['length.numeric'] = 'Field Length must be numeric';
                $rules['length.digits_between'] = 'Field Length must be between 1 and 255';
            }

            $validator = Validator::make($value, $valid_fields, $rules);

            if($validator->fails())
            {
                return response()->json($validator->errors(), 200);
            }
            
        }

        // END validate SAP Table Fields

        // START validate SAP Table Field Options
        foreach ($sap_table_fields as $key => $value) {

            // if row has_options is true or has value then validate sap table field options
            if($value['has_options'])
            {
                
                $field_type = $value['type'];
                $numeric_data_types = ['integer', 'decimal'];
                $value_validation = '';
                $value_rules = [];

                // if field type in ['integer', 'decimal'] then set validation into required|numeric
                if(in_array($field_type, $numeric_data_types))
                {
                    $value_validation = 'required|numeric';
                }
                // if field type string required|max:255
                else if($field_type === 'string')
                {
                    $value_validation = 'required|max:255';
                }
                // if field type date required|date_format:Y-m-d
                else
                {
                    $value_validation = 'required|date_format:Y-m-d';
                }

                $valid_fields = [
                    '*.value' => $value_validation,
                    '*.description' => 'required|max:20',
                ];

                $rules = [
                    '*.value.required' => 'Field Name is required',
                    '*.description.required' => 'Description is required',
                    '*.description.max' => 'Option Description maximum length exceeds, must be 20 characters and less',
                ];
        
        
                $validator = Validator::make($value['sap_table_field_options'], $valid_fields, $rules);
        
                if($validator->fails())
                {
                    return response()->json($validator->errors(), 200);
                }
            }
        }
        // END validate SAP Table Field Options

        
        $sap_table = new SapTable();
        $sap_table->table_name = $request->get('table_name');    
        $sap_table->description = $request->get('description');
        $sap_table->type = $request->get('type');  
        // $sap_table->save();

        return response()->json(['success' => 'Record has successfully added', 'sap_table' => $sap_table], 200);
    }

    public function store_field(Request $request)
    {   
        
        $rules = [
            '*.field_name.required' => 'Field Name is required',
            '*.field_name.unique' => 'Field Name already exists',
            '*.description.required' => 'Description is required',
            '*.type.required' => 'Field Type is required',
        ];

        $valid_fields = [
            '*.field_name' => 'required|unique:permissions,name',
            '*.description' => 'required',
            '*.type' => 'required',
        ];


        if($request->get('type') === 'string')
        {   
            $valid_fields['*.length'] = 'required|numeric|digits_between:1,255';
            $rules['*.length.required'] = 'Field Length is required';
            $rules['*.length.numeric'] = 'Field Length must be numeric';
            $rules['*.length.digits_between'] = 'Field Length must be betwee 1 and 255';
        }


        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }
        
        $sap_table_field = new SapTableField();
        $sap_table_field->field_name = $request->get('field_name');    
        $sap_table_field->description = $request->get('description');
        $sap_table_field->type = $request->get('type');  
        $sap_table_field->length = $request->get('length'); 
        $sap_table_field->default_value = $request->get('default_value'); 
        $sap_table_field->has_options = $request->get('has_options'); 
        $sap_table_field->is_required = $request->get('is_required'); 
        // $sap_table->save();

        // return response()->json(['success' => 'Record has successfully added', 'sap_table_field' => $sap_table_field], 200);
    }

    public function store_option(Request $request)
    {   
        
        $rules = [
            '*.value.required' => 'Field Name is required',
            '*.description.unique' => 'Field Name already exists',
        ];

        $valid_fields = [
            '*.value' => 'required',
            '*.description' => 'required',
        ];


        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }
        
        $sap_table_field_option = new SapTableField();
        $sap_table_field_option->field_name = $request->get('value');    
        $sap_table_field_option->description = $request->get('description');

        // $sap_table->save();

        // return response()->json(['success' => 'Record has successfully added', 'sap_table_field' => $sap_table_field], 200);
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
        $sap_table_id = $request->get('sap_table_id');
        $sap_table = SapTable::find($sap_table_id);
        
        //if record is empty then display error page
        if(empty($sap_table->id))
        {
            return abort(404, 'Not Found');
        }

        $sap_table->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }

    public function delete_field(Request $request)
    {   
        $sap_table_field_id = $request->get('sap_table_id');
        $sap_table_field = SapTableField::find($sap_table_field_id);
        
        //if record is empty then display error page
        if(empty($sap_table_field->id))
        {
            return abort(404, 'Not Found');
        }

        $sap_table_field->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }

    public function delete_option(Request $request)
    {   
        $sap_table_field_option_id = $request->get('sap_table_id');
        $sap_table_field_option = SapTablefieldOption::find($sap_table_field_option_id);
        
        //if record is empty then display error page
        if(empty($sap_table_field_option->id))
        {
            return abort(404, 'Not Found');
        }

        $sap_table_field_option->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }

    public function migrate(){

    }
}
