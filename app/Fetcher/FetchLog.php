<?php

namespace App\Fetcher;

use Illuminate\Database\Eloquent\Model;

class FetchLog extends Model
{
    protected $table = 'fetch_log';

    protected $fillable = ['count'];
}
