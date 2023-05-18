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
    private $sap_table_id;

    public function index()
    {   
        $sap_tables = SapTable::with('sap_table_fields')
                              ->with('sap_table_fields.sap_table_field_options')
                              ->get();
        
        $parent_tables = SapTable::where('type', '=', 'Header')->get();

        return response()->json(['sap_tables' => $sap_tables, 'parent_tables' => $parent_tables], 200);
    }

    public function store(Request $request)
    {          
    
        // START validate SAP Table

        //params (values, sap_table_id)
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

        //Validate if field_name has duplicates
        foreach ($sap_table_fields as $key => $value) 
        {   
            foreach($sap_table_fields as $i => $field)
            {   
                // exclude current row for validation of duplicate
                if($i !== $key)
                {
                    if($field['field_name'] === $value['field_name'])
                    {
                        return response()->json(['field_name' => 'Duplicate Field Name ' . $field['field_name'], 'index' => $i], 200);
                    }
                }

                $field_options = $field['sap_table_field_options'];

                //Validate if option value has duplicates
                foreach ($field_options as $j => $option) {
                    foreach ($field_options as $k => $opts) {
                        if($k !== $j)
                        {
                            if($option['value'] === $opts['value'])
                            {
                                return response()->json(['value' => 'Duplicate Option Value ' . $opts['value'], 'index' => $k], 200);
                            }
                        }
                    }
                }
            }
            
            //params (values, sap_table_field_id)
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

                    //params (values, sap_table_field type, sap_table_field_option_id)
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
            
            foreach ($field_options as $i => $option) {
                $field_option = new SapTableFieldOption();
                $this->save_option_data($field_option, $option, $sap_table_field->id);
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
        $sap_table->is_migrated = isset($item['is_migrated']) ? : false;
        $sap_table->save();

        return $sap_table;
    }

    //params (values, sap_table_id)
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
        //params (values, sap_table_field_id)
        $validator = $this->fields_validation($request->all(), null);
    
        // if validation fails
        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }
        
        $field_options = $request->get('sap_table_field_options');
        $field_type = $request->get('type');
        $has_options = $request->get('has_options');

        //Validate if option value has duplicates
        if($has_options)
        {
            foreach ($field_options as $j => $option) {
                foreach ($field_options as $k => $opts) {
                    if($k !== $j)
                    {
                        if($option['value'] === $opts['value'])
                        {
                            return response()->json(['value' => 'Duplicate Option Vaue ' . $opts['value'], 'index' => $k], 200);
                        }
                    }
                }
                
                //params (values, sap_table_field type, sap_table_field_option_id)
                $validator = $this->options_validation($option, $field_type, null);
                    
                if($validator->fails())
                {
                    return response()->json($validator->errors(), 200);
                }
            }
        }

        $sap_table_field = new SapTableField();
        $sap_table_field = $this->save_field_data($sap_table_field, $request, $request->get('sap_table_id'));
        
        if($has_options)
        {
            foreach ($field_options as $i => $option) {
                $field_option = new SapTableFieldOption();
                $field_option = $this->save_option_data($field_option, $option, $sap_table_field->id);
            }
        }
        
        $sap_table_field = SapTableField::with('sap_table_field_options')->where('id', '=', $sap_table_field->id)->first();

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

        !$sap_table_field->id ? $sap_table_field->is_migrated = false : ''; //if sap_table_id is null (add mode) then

        $sap_table_field->line_num = $line_num;
        $sap_table_field->save();

        return $sap_table_field;
    }

    //params (values, sap_table_field_id)
    public function fields_validation($item, $id)
    {
        $sap_table_id = isset($item['sap_table_id']) ? $item['sap_table_id'] : null;
        $field_name = $item['field_name'];

        $valid_fields = [
            // 'field_name' => 'required|max:64',
            'field_name' => [
                'required',
                'max:64',
                Rule::unique('sap_table_fields')->where(function ($query) use ($field_name, $sap_table_id, $id) {
                
                    return $query->where('id','!=', $id)
                                    ->where('field_name', $field_name)
                                    ->where('sap_table_id', $sap_table_id);

                }),
            ],
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

        $validator = Validator::make($item, $valid_fields, $rules);

        return $validator;
    }

    public function store_option(Request $request)
    {   
        $field_type = $request->get('field_type');

        //params (values, sap_table_field type, sap_table_field_option_id)
        $validator = $this->options_validation($request->all(), $field_type, null);

        // if validation fails
        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }
        $field_option = new SapTableFieldOption();
        $field_option = $this->save_option_data($field_option, $request, $request->get('sap_table_field_id'));

        return response()->json(['success' => 'Record has successfully added', 'sap_table_field_option' => $field_option], 200);
     
    }

    public function save_option_data($field_option, $item, $sap_table_field_id)
    {   
        $max_line_num = SapTableFieldOption::where('sap_table_field_id', '=', $sap_table_field_id)->max('line_num');
        $line_num = $max_line_num ? $max_line_num + 1 : 0;

        $field_option->sap_table_field_id = $sap_table_field_id; 
        $field_option->value = $item['value'];    
        $field_option->description = $item['description'];
        $field_option->line_num = $line_num;
        $field_option->save();

        return $field_option;
    }

    //params (values, sap_table_field type, sap_table_field_option_id)
    public function options_validation($item, $field_type, $id)
    {   
        $sap_table_field_id = isset($item['sap_table_field_id']) ? $item['sap_table_field_id'] : null;
        $value = isset($item['value']) ? $item['value'] : null;

        $valid_fields = [
            'required',
            Rule::unique('sap_table_field_options')->where(function ($query) use ($value, $sap_table_field_id, $id) {

                    return $query->where('id','!=', $id)
                                 ->where('value', $value)
                                 ->where('sap_table_field_id', $sap_table_field_id);
                
            }),
        ];

        $numeric_data_types = ['integer', 'decimal'];

        // if field type in ['integer', 'decimal'] then set validation into required|numeric
        if(in_array($field_type, $numeric_data_types))
        {
            $value_validation[] = 'numeric';
        }
        // if field type string required|max:255
        else if($field_type === 'string')
        {
            $value_validation[] = 'max:255';
        }
        // if field type date required|date_format:Y-m-d
        else
        {
            $value_validation[] = 'date_format:Y-m-d';
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

        $validator = Validator::make($item, $valid_fields, $rules);

        return $validator;
    }

    public function update(Request $request, $sap_table_id)
    {   
        // START validate SAP Table

        //params (values, sap_table_id)
        $validator = $this->sap_table_validation($request->all(), $sap_table_id);

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

        $sap_table = SapTable::find($sap_table_id);

        // check if table name has values or record
        $has_value = $this->has_value(['table_name' => $sap_table->table_name], 'Header');
    
        if($has_value)
        {
            return response()->json(["table_name" => "Cannot update table name. Table '".$sap_table->table_name."' has already used."], 200);
        }
         
        $sap_table = $this->save_table($sap_table, $request);

        return response()->json(['success' => 'Record has been updated', 'sap_table' => $sap_table], 200);
    }

    public function update_field(Request $request, $sap_table_field_id)
    {   
        $validator = $this->fields_validation($request->all(), $sap_table_field_id);
    
        // if validation fails
        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $field_options = $request->get('sap_table_field_options');
        $field_type = $request->get('type');

        //Validate if option value has duplicates
        foreach ($field_options as $j => $option) {
            foreach ($field_options as $k => $opts) {
                if($k !== $j)
                {
                    if($option['value'] === $opts['value'])
                    {
                        return response()->json(['value' => 'Duplicate Option Vaue ' . $opts['value'], 'index' => $k], 200);
                    }
                }
            }

            //params (values, sap_table_field type, sap_table_field_option_id)
            $validator = $this->options_validation($option, $field_type, null);
                
            if($validator->fails())
            {
                return response()->json($validator->errors(), 200);
            }
        }

        $sap_table_field = SapTableField::find($sap_table_field_id);
        $sap_table = SapTable::find($sap_table_field->sap_table_id);
        $has_options = $sap_table_field->has_options;

        // check if table name has values or record
        $has_value = $this->has_value([
            'table_name' => $sap_table->table_name, 
            'field_name' => $sap_table_field->field_name, 
          ], 'Row' );

        if($has_value)
        {
            return response()->json(["field_name" => "Cannot update field name. Field '".$sap_table_field->field_name."' has already used."], 200);
        }

        // if has_options field is for update from false to true or 0 to 1 value
        if(!$has_options && $request->get('has_options'))
        {
            foreach ($field_options as $i => $option) {
                $field_option = new SapTableFieldOption();
                $field_option = $this->save_option_data($field_option, $option, $sap_table_field->id);
            }
        }
        // else if has_options field is for update from true to false or 1 to 0 value
        else if($has_options && !$request->get('has_options'))
        {   
            foreach ($field_options as $key => $option) {
                // check if table name has values or record
                $has_value = $this->has_value([
                    'table_name' => $sap_table->table_name, 
                    'field_name' => $sap_table_field->field_name, 
                    'option_value' => $option['value'],
                ], 'Option');

                if($has_value)
                {
                    return response()->json(['value', 'Cannot remove option list. Option values has already used.'], 200);
                }
            }

            SapTableFieldOption::where('sap_table_field_id', '=', $sap_table_field->id)->delete();
        }

        $sap_table_field = $this->save_field_data($sap_table_field, $request, $request->get('sap_table_id'));

        $sap_table_field = SapTableField::with('sap_table_field_options')->where('id', '=', $sap_table_field->id)->first();

        return response()->json(['success' => 'Record has been updated', 'sap_table_field' => $sap_table_field], 200);
    }

    public function update_option(Request $request, $sap_table_field_option_id)
    {   
        //params (values, sap_table_field type, sap_table_field_option_id)
        $validator = $this->options_validation($request->all(), $request->get('field_type'), $sap_table_field_option_id);
    
        // if validation fails
        if($validator->fails())
        {
            return response()->json($validator->errors(), 200);
        }

        $field_option = SapTableFieldOption::find($sap_table_field_option_id);

        $sap_table_field = SapTableField::find($field_option->sap_table_field_id);
        $sap_table = SapTable::find($sap_table_field->sap_table_id);

        // check if table name has values or record
        $has_value = $this->has_value([
            'table_name' => $sap_table->table_name, 
            'field_name' => $sap_table_field->field_name, 
            'option_value' => $field_option->value,
          ], 'Option');

        if($has_value)
        {
            return response()->json(["value" => "Cannot update Option Value. Value '".$field_option->value."' has already used."], 200);
        }

        $field_option = $this->save_option_data($field_option, $request, $request->get('sap_table_field_id'));

        return response()->json(['success' => 'Record has been updated', 'sap_table_field_option' => $field_option], 200);
    }

    public function delete(Request $request)
    {   
        $sap_table_id = $request->get('sap_table_id');
        $sap_table = SapTable::find($sap_table_id);

        $table_name = $sap_table->table_name;
        
        //if record is empty then display error page
        if(empty($sap_table->id))
        {
            return abort(404, 'Not Found');
        }

        // check if table name has values or record
        $has_value = $this->has_value(['table_name' => $sap_table->table_name], 'Header');
        
        if($has_value)
        {
            return response()->json(["error" => "Cannot delete table. Table '".$sap_table->table_name."' has already used."], 200);
        }

        $sap_table->delete();

        // START validate table and fields if exists
        if (Schema::hasTable($table_name))
        {
            Schema::dropIfExists($table_name);
        }

        $sap_table_field =  SapTableField::where('sap_table_id', '=', $sap_table->id);
        $sap_table_field_id = $sap_table_field->first()->id;
        $sap_table_field->delete();
        
        SapTableFieldOption::where('sap_table_field_id', '=', $sap_table_field_id)->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }

    public function delete_field(Request $request)
    {   

        $sap_table_field = SapTableField::find($request->get('sap_table_field_id'));
        
        //if record is empty then display error page
        if(empty($sap_table_field->id))
        {
            return abort(404, 'Not Found');
        }

        $sap_table = SapTable::find($sap_table_field->sap_table_id);

        // check if table name has values or record
        $has_value = $this->has_value([
                                        'table_name' => $sap_table->table_name, 
                                        'field_name' => $sap_table_field->field_name, 
                                      ], 'Row' );
        
        if($has_value)
        {
            return response()->json(["error" => "Cannot delete Field. Field '".$sap_table_field->field_name."' has already used."], 200);
        }

        $table_name = $sap_table->table_name;
        $field_name = $sap_table_field->field_name;
        
        // delete column
        if(Schema::hasColumn($table_name, $field_name))
        {  
            Schema::table($table_name, function (Blueprint $table) use ($field_name) {
                $table->dropColumn($field_name);
            });
        }

        $sap_table_field->delete();
        SapTableFieldOption::where('sap_table_field_id', '=', $sap_table_field->id)->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }

    public function delete_option(Request $request)
    {   
        
        $field_option = SapTableFieldOption::find($request->get('sap_table_field_option_id'));
        
        //if record is empty then display error page
        if(empty($field_option->id))
        {
            return abort(404, 'Not Found');
        }

        $sap_table_field = SapTableField::find($field_option->sap_table_field_id);
        $sap_table = SapTable::find($sap_table_field->sap_table_id);

        // check if table name has values or record
        $has_value = $this->has_value([
                                        'table_name' => $sap_table->table_name, 
                                        'field_name' => $sap_table_field->field_name, 
                                        'option_value' => $field_option->value,
                                      ], 'Option');
        
        if($has_value)
        {
            return response()->json(["error" => "Cannot delete Option Value. Value '".$field_option->value."' has already used."], 200);
        }

        $field_option->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }


    public function has_value($item, $type)
    {   
        $table_name = $item['table_name'];
        $field_value = null;
        
        if($type === 'Header')
        {   
            if(Schema::hasTable($table_name)) // if table exists
            {
                $field_value = DB::table($table_name)->max('id');
            }
        }
        else if($type === 'Row')
        {
            if(Schema::hasColumn($table_name, $item['field_name'])) // if table exists
            {
                $field_value = DB::table($table_name)->max($item['field_name']);
            }   
        }
        else
        {
            if(Schema::hasColumn($table_name, $item['field_name'])) // if table exists
            {
                $field_value = DB::table($table_name)->where($item['field_name'], '=', $item['option_value'])->max($item['field_name']);
            }  
        }

        return $field_value ? true : false;
    }

    public function migrate(Request $request)
    {
        
        $type = $request->get('type');
        $table_type = $request->get('table_type');
        $table_name = $request->get('table_name');
        $id = $request->get('id');

        if($type === 'table')
        {   
            // START validate table and fields if exists
            if (Schema::hasTable($table_name))
            {
                return response()->json(['error' => 'Table name '. strtolower($table_name) . ' already exists.'], 200);
            }

            $fields = $request->get('fields');

            foreach ($fields as $key => $field) {
                if(Schema::hasColumn($table_name, $field['field_name']))
                {  
                    return response()->json(['error' => 'Field name '. strtolower($field['field_name']) . ' already exists.'], 200);
                }
            }

            // END validate table and fields if exists

            // START create table and fields
            Schema::create($table_name, function (Blueprint $table) use ($fields, $table_type) {
                $table->id();
                $table_type === 'Row' ? $table->integer('parent_id') : '';
                foreach ($fields as $field) {
                    $type = $field['type'];
                    $is_required = $field['is_required'];
                    $length = $field['length'];
                    $field_name = $field['field_name'];

                    if($type === 'string')
                    {
                        $table->string($field_name, $length)->nullable($is_required ? false : true); 
                    }
                    else if($type === 'decimal')
                    {
                        $table->decimal($field_name, 12, 4)->nullable($is_required ? false : true); 
                    }
                    else
                    {
                        $table->{$type}($field_name)->nullable($is_required ? false : true); 
                    }
                }
                $table->timestamps();
            });
            // END create table and fields

            // START validate table and fields if exists
            if (Schema::hasTable($table_name))
            {
                SapTable::where('id', $id)
                        ->update(['is_migrated' => true,]);;
            }

            // if field exists then update the 'is_migrated' column into true
            foreach ($fields as $field) {
                if(Schema::hasColumn($table_name, $field['field_name']))
                {  
                    SapTableField::where('id', $field['id'])->update(['is_migrated' => true,]);
                }  
            }
        }
        else
        {
            $sap_table_field = SapTableField::find($id);
            $sap_table = SapTable::find($sap_table_field->sap_table_id);
            $table_name = $sap_table->table_name;
            // if field exists then update the 'is_migrated' column into true
            if (Schema::hasTable($table_name))
            {   
                if(!Schema::hasColumn($table_name, $sap_table_field->field_name)) // if table exists
                {
                    Schema::table($table_name, function (Blueprint $table) use ($sap_table_field) {
                        $type = $sap_table_field->type;
                        $is_required = $sap_table_field->is_required;
                        $length = $sap_table_field->length;
                        $field_name = $sap_table_field->field_name;
                        
                        $is_required = $is_required ? false : true;

                        if($type === 'string')
                        {
                            $table->string($field_name, $length)->nullable($is_required); 
                        }
                        else if($type === 'decimal')
                        {
                            $table->decimal($field_name, 12, 4)->nullable($is_required); 
                        }
                        else if($type === 'integer')
                        {
                            $table->{$type}($field_name)->nullable($is_required); 
                        }
                        else //type date
                        {
                            $table->date($field_name)->nullable(); 
                        }
                    });

                    SapTableField::where('id', $id)->update(['is_migrated' => true,]);
                }
                else
                {
                    return response()->json(['error' => 'Field name '. strtolower($sap_table_field->field_name) . ' already exists.'], 200);
                }
            }
            else
            {
                return response()->json(['error' => 'No existing table '. $table_name.'. Table must be created first.'], 200);
            }



            

            // END validate table and fields if exists

            // START create table and fields
            
        
        }

        return response()->json(['success' => 'Table fields has been migrated'], 200);
    }
}
