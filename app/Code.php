<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    //

    protected $fillable = ['code' , 'title' , 'code_id' , 'user_id', 'error_id'];

    public function user(){
        return $this->belongsTo('App\User' , 'user_id');
    }

    public function answer(){
        return $this->belongsTo('App\Answer' , 'answer_id');
    }

    public function error(){
        return $this->belongsTo('App\Error' , 'error_id');
    }
}
