<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Code;

class Execute extends Model
{
    //
    protected $fillable = ['code_id' , 'exe' , 'memoryusage' , 'registerusage' , 'code'];

    public function code()
    {
        $this->belongsTo(Code::class);
    }
}
