<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    //

    protected $fillable = ['answer' , 'code_id'];

    public function code(){
        return $this->belongsTo('App\Code' , 'code_id');
    }
}
