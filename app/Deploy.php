<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class Deploy extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function site()
    {
        return $this->belongsTo('App\Site');
    }
}
