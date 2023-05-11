<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
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
                                ->first();

        $child_tables = SapTable::with('sap_table_fields')
                                  ->with('sap_table_fields.sap_table_field_options')
                                  ->where('parent_table', '=', $parent_table->table_name)
                                  ->get();
        
        return response()->json([
            'parent_table' => $parent_table, 
            'child_tables' => $child_tables
        ], 200);
    } 
    
    public function store(Request $request)
    {   
        $table_name = $request->get('table_name');

        $columns = Schema::getColumnListing($table_name);

        $field_names = array_keys($request->toArray());
        $data = [];
        foreach ($columns as $key => $col) {
            foreach ($field_names as $i => $field) {
                if($col === $field)
                {
                    $data[$col] = $request[$field];
                }
            }
        }
        $table_id = DB::table($table_name)->insertGetId($data);
        $table_data = DB::table($table_name)->where('id', '=', $table_id)->first()->toArray();

        return response()->json(['success' => 'Record has been added'], 200);
    }
}
