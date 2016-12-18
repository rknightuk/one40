<?php

namespace App\Tweets;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Laracodes\Presenter\Traits\Presentable;

class Tweet extends Model
{
	use Presentable;

	protected $presenter = 'App\Tweets\TweetPresenter';

	public $timestamps = false;

	protected $table = 'tweets';

	protected $fillable = ['userid', 'tweetid', 'type', 'time', 'text', 'source', 'extra', 'coordinates', 'geo', 'place', 'contributors'];

	public function photos()
	{
		if ( ! isset($this->extra['entities']->media)) return false;

		$photos = [];

		foreach( $this->extra['entities']->media as $url)
		{
			$photos[] = [
				'thumb' => $url->media_url . ':thumb',
				'url'   => $url->expanded_url
			];
		}

		return $photos;
	}

	public function getTimeAttribute($time)
	{
		return Carbon::createFromTimeStamp($time);
	}

	public function getExtraAttribute($extra)
	{
		// Fix serialization errors, see here: http://stackoverflow.com/questions/10152904/unserialize-function-unserialize-error-at-offset
		$extra = preg_replace_callback ( '!s:(\d+):"(.*?)";!', function($match) {
			return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
		},$extra);

		return @unserialize($extra);
	}

	public function getPlaceAttribute($place)
	{
		return unserialize(str_replace("O:16:\"SimpleXMLElement\"", "O:8:\"stdClass\"", $place));
	}
}
