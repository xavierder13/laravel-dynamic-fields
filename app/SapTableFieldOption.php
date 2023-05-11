<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SapTableFieldOption extends Model
{
    protected $fillable = [
        'sap_table_field_id',
        'line_num',
        'value', 
        'description',
    ];

    public function tactical_row()
    {   
        return $this->belongsTo('App\SapTableField', 'sap_table_field_id','id');
        //                 ( <Model>, <id_of_this_model>, <id_of_specified_Model>  )
    }
}
