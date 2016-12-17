<?php

namespace App\Archive;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'archive_log';

    protected $fillable = ['filename'];
}
