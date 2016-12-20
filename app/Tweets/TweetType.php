<?php

namespace App\Tweets;

class TweetType {

	const TYPE_TWEET = 0;
	const TYPE_REPLY = 1;
	const TYPE_RETWEET = 2;

	protected static $types = [
		self::TYPE_TWEET => 'tweet',
		self::TYPE_REPLY => 'reply',
		self::TYPE_RETWEET => 'retweet',
	];

	public static function getTypeString($type, $plural = false)
	{
		$typeString = self::$types[$type];

		if (! $plural) return $typeString;

		if ($type == self::TYPE_REPLY)
		{
			$typeString = 'replies';
		}
		else {
			$typeString = $typeString . 's';
		}

		return $typeString;
	}

}