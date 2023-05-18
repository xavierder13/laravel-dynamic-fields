<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\SapTable;
use App\SapTableField;
use App\SapTableFieldOption;
use DB;

class SAPModuleController extends Controller
{   
    public function get_parent_tables()
    {
        $modules = SapTable::where('type', '=', 'Header')
                           ->where('is_migrated', '=', true)
                           ->get();

        return response()->json(['modules' => $modules], 200);
    }

    public function get_table_fields($sap_table_id)
    {
        $parent_table = SapTable::with('sap_table_fields')
                                ->with('sap_table_fields.sap_table_field_options')
                                ->where('id', '=', $sap_table_id)
                                ->where('is_migrated', '=', true)
                                ->first();
        
        $parent_table_fields = SapTableField::with('sap_table')
                                            ->with('sap_table_field_options')
                                            ->where('sap_table_id', '=', $sap_table_id)
                                            ->where('is_migrated', '=', true)
                                            ->whereHas('sap_table', function($query) {
                                                $query->where('is_migrated', '=', true);
                                            })
                                            ->get();

        $child_tables = SapTable::with('sap_table_fields')
                                  ->with('sap_table_fields.sap_table_field_options')
                                  ->where('parent_table', '=', $parent_table->table_name)
                                  ->where('is_migrated', '=', true)
                                  ->get();
                                 
        $child_table_fields = SapTableField::with('sap_table')
                                            ->with('sap_table_field_options')
                                            ->where('is_migrated', '=', true)
                                            ->whereIn('sap_table_id', $child_tables->pluck('id'))
                                            ->get();
        
        return response()->json([
            'parent_table' => $parent_table, 
            'parent_table_fields' => $parent_table_fields,
            'child_tables' => $child_tables,
            'child_table_fields' => $child_table_fields,

        ], 200);
    } 
    
    public function store(Request $request)
    {   
        $header = $request->get('header');
        $rows = $request->get('row');

        // START INSERT HEADER TABLE DATA
        $columns = Schema::getColumnListing($header['table_name']);
        $data = [];
        $date_val = "";
        
        foreach ($header['data'] as $field) {
            $value = $field['value'];
            
            if($value)
            {   
                if($field['type'] === 'date')
                {
  
                    $exploded =  explode('/', $value);
                    $MM = $exploded[0];
                    $DD = $exploded[1];
                    $YYYY = $exploded[2];

                    $value = $YYYY.'-'.$MM.'-'.$DD;
                    
                } 
            }

            $data[$field['field_name']] = $value;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
       
        $header_id = DB::table($header['table_name'])->insertGetId($data);
        // $table_data = DB::table($header['table_name'])->where('id', '=', $header_id)->first();
        // END INSERT HEADER TABLE DATA

        // START INSERT ROW TABLE DATA

        foreach ($rows as $row) {
            $data = [];
            foreach ($row['data'] as $fields) {
                foreach ($fields as $field) {
                    $value = $field['value'];

                    if($value)
                    {   
                        if($field['type'] === 'date')
                        {
                            $exploded =  explode('/', $value);
                            $MM = $exploded[0];
                            $DD = $exploded[1];
                            $YYYY = $exploded[2];
        
                            $value = $YYYY.'-'.$MM.'-'.$DD;
                        } 
                    }
        
                    $data[$field['field_name']] = $value;
                }
                $data['parent_id'] = $header_id;
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                DB::table($row['table_name'])->insert($data);
            }
            
            
        }

        // END INSERT ROW TABLE DATA
        return response()->json(['success' => 'Record has been added'], 200);

        // $columns = Schema::getColumnListing($table_name);

        // $field_names = array_keys($request->toArray());
        // $data = [];
        // foreach ($columns as $key => $col) {
        //     foreach ($field_names as $i => $field) {
        //         if($col === $field)
        //         {
        //             $data[$col] = $request[$field];
        //         }
        //     }
        // }

        // $table_id = DB::table($table_name)->insertGetId($data);
        // $table_data = DB::table($table_name)->where('id', '=', $table_id)->first()->toArray();

        // return response()->json(['success' => 'Record has been added'], 200);
    }
    
    public function find(Request $request, $sap_table_id)
    {   
        $table_name = $request->get('table_name');
        $fields = $request->get('data'); 
        $columns = Schema::getColumnListing($table_name);
        $sap_table_id = SapTable::where('table_name', '=', $table_name)->first()->id;
        
        $cols = [];
        foreach ($columns as $col) {

            if(!in_array( $col, ['created_at', 'updated_at']))
            {
                $cols[] = $col;
            }
        }

        $table_fields = SapTableField::where('sap_table_id', '=', $sap_table_id)
                                         ->whereIn('field_name', $cols)
                                         ->get();

        // $child_tables = SapTable::where('parent_table', '=', $table_name)->get();
        
        // $field_conditions_arr = [];
        // foreach ($fields as $field) {
        //     $field_conditions[] = $field['field_name']. " like '%" . $field['value'] . "'";
        // }

        // $field_conditions = join(" and ", $field_conditions);

        // return $data = DB::select("select * from ".$table_name." where ". $field_conditions);
            
        $data = DB::table($table_name)
                 ->select($cols)
                 ->where(function($query) use ($fields){
                    foreach ($fields as $field) {
                        if(!$field['value'])
                        {
                            $query->where(function($q) use ($field){
                                $q->where($field['field_name'], 'like', '%' . $field['value'] . '%')
                                  ->orWhereNull($field['field_name']);
                            });
                        }
                        else
                        {
                            $query->where($field['field_name'], 'like', '%' . $field['value'] . '%');
                        }
                    }
                 })
                 ->get();
        return response()->json(['data' => $data, 'table_fields' => $table_fields], 200);
    }

    public function get_data(Request $request)
    {   
        $sap_table_id = $request->get('sap_table_id');
        $sap_table = SapTable::find($sap_table_id);
        $table_name = $sap_table->table_name;
        $columns = Schema::getColumnListing($table_name);
        $child_tables = SapTable::where('parent_table', '=', $table_name)->get();
        
        $cols = [];
        foreach ($columns as $col) {

            if(!in_array( $col, ['created_at', 'updated_at']))
            {
                $cols[] = $col;
            }
        }

        
        $header_data = DB::table($table_name)
                        ->select($cols)
                        ->where('id', '=', $request->get('id'))
                        ->first();
        
        $row_data = [];

        foreach ($child_tables as $key => $value) {
            
            $row_data[$key] = [
                'table_name' => $value->table_name,
                'data' => '',
            ];

            $row_data[$key]['data'] = DB::table($value->table_name)
                                        ->where('parent_id', '=', $request->get('id'))
                                        ->get();
        }

        return response()->json(['header_data' => $header_data, 'row_data' => $row_data], 200);
    }
}
