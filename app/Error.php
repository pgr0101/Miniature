<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Error extends Model
{
    //

    protected $fillable = ['error', 'code_id'];

    public function code(){
        return $this->belongsTo('App\Code' , 'code_id');
    }
}
