<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\SapTable;
use App\SapTableField;
use App\SapTableFieldOption;

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
}
