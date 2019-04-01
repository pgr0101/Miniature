<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    //

    protected $fillable = [
        'label' , 'line' , 'code_id' , 'value'
    ];

    public function user()
    {
        return $this->belongsTo('App\Code', 'code_id');
    }

}
