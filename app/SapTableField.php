<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SapTableField extends Model
{
    protected $fillable = [
        'sap_table_id',
        'field_name',
        'description', 
        'type',
        'length',
        'default_value',
        'is_multiple',
        'is_required',
        'is_migrated',
        'line_num',
    ];

    public function sap_table()
    {   
        return $this->belongsTo('App\SapTable', 'sap_table_id','id');
        //                 ( <Model>, <id_of_this_model>, <id_of_specified_Model>  )
    }

    public function sap_table_field_options()
    {   
        return $this->hasMany('App\SapTableFieldOption', 'sap_table_field_id', 'id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
