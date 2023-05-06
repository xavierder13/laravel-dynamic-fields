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
    public function get_ar_invoice_fields()
    {
        $sap_oinv_table = SapTable::with('sap_table_fields')
                                  ->with('sap_table_fields.sap_table_field_options')
                                  ->where('table_name', '=', 'oinv')
                                  ->get()->first();

        $sap_inv1_table = SapTable::with('sap_table_fields')
                                  ->with('sap_table_fields.sap_table_field_options')
                                  ->where('table_name', '=', 'inv1')
                                  ->get()->first();
        
        return response()->json([
            'sap_oinv_table' => $sap_oinv_table, 
            'sap_inv1_table' => $sap_inv1_table
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
