<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //

    protected $fillable = [
        'username' , 'password' , 'code_id'];

    protected $hidden = [
        'password'];

    public function codes(){
        return $this->hasMany('App\Code' , 'code_id');
    }
}
