<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
	public $timestamps = false;

	protected $table = 'tweets';

	protected $fillable = ['userid', 'tweetid', 'type', 'time', 'text', 'source', 'extra', 'coordinates', 'geo', 'place', 'contributors'];
}
