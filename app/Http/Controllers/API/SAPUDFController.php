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
use DB;
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

        $parent_tables = SapTable::where('type', '=', 'Header')->get();
        
        return response()->json(['sap_tables' => $sap_tables, 'parent_tables' => $parent_tables], 200);
    }

    public function create()
    {
        return view('pages.permissions.create');
    }


    public function store(Request $request)
    {          
    
        // START validate SAP Table

        $validator = $this->sap_table_validation($request->all(), null);

        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        // validate table_name value
        $string = $request->get('table_name');
        
        $startsNumeric = is_numeric($string[0]);
        $hasSpclChars = preg_match('/[\'^£$%&*()}{@#~?><,|=+¬-]/', $string); //except ( _ )

        if($startsNumeric || $hasSpclChars)
        {
            return response()->json(['table_name' => 'Table Name must be alphanumeric only and starts with letter'], 200);
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
            
            $validator = $this->fields_validation($value, null);

            if($validator->fails())
            { 
                return response()->json($validator->errors(), 200);
            }
            
        }

        // END validate SAP Table Fields

        // START validate SAP Table Field Options
        foreach ($sap_table_fields as $key => $value) {
            $field_options = $value['sap_table_field_options'];
            // if row has_options is true or has value then validate sap table field options
            if($value['has_options'])
            {   
                $field_type = $value['type'];

                foreach ($field_options as $i => $val) {

                    $validator = $this->options_validation($val, $field_type, null);
                
                    if($validator->fails())
                    {
                        return response()->json($validator->errors(), 200);
                    }
                }
            }
        }
        // END validate SAP Table Field Options

        $sap_table = new SapTable();
        $sap_table = $this->save_table($sap_table, $request);

        foreach ($sap_table_fields as $key => $value) {
            $sap_table_field = new SapTableField();
            $sap_table_field = $this->save_field_data($sap_table_field, $value, $sap_table->id);
            
            $field_options = $value['sap_table_field_options'];
            
            foreach ($field_options as $i => $val) {
                $this->save_option_data($val, $sap_table_field->id, $i);
            }
        }
        
        $sap_table = SapTable::with('sap_table_fields')
                             ->with('sap_table_fields.sap_table_field_options')
                             ->where('id', '=', $sap_table->id)
                             ->first();
        
        return response()->json(['success' => 'Record has successfully added', 'sap_table' => $sap_table], 200);
    }

    public function save_table($sap_table, $item)
    {
        $sap_table->table_name = $item['table_name'];
        $sap_table->description = $item['description'];
        $sap_table->type = $item['type'];
        $sap_table->parent_table = $item['parent_table'];
        $sap_table->is_migrated = false;
        $sap_table->save();

        return $sap_table;
    }

    public function sap_table_validation($item, $id)
    {
        $valid_fields = [
            'table_name' => 'required|max:64|unique:sap_tables,table_name',
            'description' => 'required|max:30',
            'type' => 'required',
            'sap_table_fields' => 'required'
        ];

        $rules = [
            'table_name.required' => 'Table Name is required',
            'table_name.max' => 'Table Name length must be less then 64 or equal',
            'table_name.unique' => 'Table Name already exists',
            'description.required' => 'Table Description is required',
            'description.max' => 'Table Description maximum length exceeds, must be 30 characters and less',
            'type.required' => 'Table Type is required',
            'sap_table_fields.required' => 'Sap Table Fields are required',
        ];

        // if id is not null (update mode)
        if($id)
        {
            $valid_fields['table_name'] = $valid_fields['table_name'] . ',' . $id;
        }
 
        // if table type is row then add validation for parent_table column
        if($item['type'] === 'Row')
        {
            $valid_fields['parent_table'] = 'required|max:64';
            $rules['parent_table.required'] = 'Parent Table is required';
            $rules['parent_table.max'] = 'Table Name length must be less then 64 or equal';
        }

        $validator = Validator::make($item, $valid_fields, $rules);

        return $validator;
    }

    public function store_field(Request $request)
    {   
        
        $validator = $this->fields_validation($request, null);

        // if validation fails
        if($validator>fails());
        {
            return response()->json($validator->errors(), 200);
        }
        $sap_table_field = new SapTableField();
        $sap_table_field = $this->save_field_data($sap_table_field, $request, $request->get('sap_table_id'));

        return response()->json(['success' => 'Record has successfully added', 'sap_table_field' => $sap_table_field], 200);
    }

    public function save_field_data($sap_table_field, $item, $sap_table_id) 
    {
        $max_line_num = SapTableField::where('sap_table_id', '=', $sap_table_id)->max('line_num');
        $line_num = $max_line_num ? $max_line_num + 1 : 0;

        $sap_table_field->sap_table_id = $sap_table_id;
        $sap_table_field->field_name = $item['field_name'];    
        $sap_table_field->description = $item['description'];
        $sap_table_field->type = $item['type'];  
        $sap_table_field->length = $item['length']; 
        $sap_table_field->default_value = $item['default_value']; 
        $sap_table_field->has_options = $item['has_options']; 
        $sap_table_field->is_required = $item['is_required']; 
        $sap_table_field->is_multiple = false;
        $sap_table_field->is_migrated = false;
        $sap_table_field->line_num = $line_num;
        $sap_table_field->save();

        return $sap_table_field;
    }

    public function fields_validation($item, $id)
    {

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
            'description' => 'required|max:30',
            'type' => 'required',
        ];

        $rules = [
            'field_name.required' => 'Field Name is required',
            'field_name.unique' => 'Field Name already exists',
            'field_name.max' => 'Field Name length must be less then 64 or equal',
            'description.required' => 'Field Description is required',
            'description.max' => 'Field Description maximum length exceeds, must be 30 characters and less',
            'type.required' => 'Field Type is required',
        ];
        
        if($item['type'] === 'string')
        {   
            $valid_fields['length'] = 'required|numeric|between:1,255';
            $rules['length.required'] = 'Field Length is required';
            $rules['length.numeric'] = 'Field Length must be numeric';
            $rules['length.digits_between'] = 'Field Length must be between 1 and 255';
        }

        // if id is not null (update mode)
        if($id)
        {
            $valid_fields['field_name'] = $valid_fields['field_name'] . ',' . $id;
        }

        $validator = Validator::make($item, $valid_fields, $rules);

        return $validator;
    }

    public function store_option(Request $request)
    {   
      
        $field_type = $request->get('field_type');

        $validator = $this->options_validation($request, $field_type, null);

        // if validation fails
        if($validator>fails());
        {
            return response()->json($validator->errors(), 200);
        }
        $sap_table_field_option = new SapTableFieldOption();
        $sap_table_field_option = $this->save_option_data($request, $request->get('sap_table_field_id'));

        return response()->json(['success' => 'Record has successfully added', 'sap_table_field_option' => $sap_table_field_option], 200);
     
    }

    public function save_option_data($sap_table_field_option, $item, $sap_table_field_id)
    {   
        $max_line_num = SapTableFieldOption::where('sap_table_field_id', '=', $sap_table_field_id)->max('line_num');
        $line_num = $max_line_num ? $max_line_num + 1 : 0;

        $sap_table_field_option->sap_table_field_id = $sap_table_field_id; 
        $sap_table_field_option->value = $item['value'];    
        $sap_table_field_option->description = $item['description'];
        $sap_table_field_option->line_num = $line_num;
        $sap_table_field_option->save();

        return $sap_table_field_option;
    }

    public function options_validation($item, $field_type, $id)
    {   
        $numeric_data_types = ['integer', 'decimal'];
        $value_validation = '';

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
            'value' => $value_validation,
            'description' => 'required|max:30',
        ];

        $rules = [
            'value.required' => 'Field Name is required',
            'description.required' => 'Description is required',
            'description.max' => 'Option Description maximum length exceeds, must be 30 characters and less',
        ];

        // if id is not null (update mode)
        if($id)
        {
            $valid_fields['value'] = $valid_fields['value'] . ',' . $id;
        }

        $validator = Validator::make($item, $valid_fields, $rules);

        return $validator;
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


    public function update(Request $request, $sap_table_id)
    {   
        $validator = $this->sap_table_validation($request->all(), $sap_table_id);
        
        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $sap_table = SapTable::find($sap_table_id);
        $sap_table = $this->save_table($sap_table, $request);

        return response()->json(['success' => 'Record has been updated', 'sap_table' => $sap_table], 200);
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
