<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SapTable extends Model
{
    protected $fillable = [
        'table_name', 
        'description', 
        'type',
        'is_migrated',
    ];

    public function sap_table_fields()
    {   
        return $this->hasMany('App\SapTableField', 'sap_table_id', 'id');
        //                 ( <Model>, <id_of_specified_Model>, <id_of_this_model> )
    }
}
