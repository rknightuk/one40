<?php

namespace App\Tweets;

class TweetRepository {

	public function getLatest()
	{
		return Tweet::orderBy('time', 'desc')->first();
	}

	public function getLatestId()
	{
		$latest = $this->getLatest();
		return $latest ? $latest->tweetid : 0;
	}

}