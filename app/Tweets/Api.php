<?php

namespace App\Tweets;

use Thujohn\Twitter\Facades\Twitter;

class Api {

	/**
	 * @param $username
	 * @param $sinceId
	 * @param int $maxId
	 * @return mixed
	 *
	 * statuses/user_timeline
	 */
	public function getUserTimeline($username, $sinceId, $maxId = 0)
	{
		$params = [
			'screen_name'      => $username,
			'include_rts'      => true,
			'include_entities' => true,
			'count'            => 200
		];

		if ($sinceId) $params['since_id'] = $sinceId;
		if ($maxId) $params['max_id'] = $maxId;

		return Twitter::getUserTimeline($params);
	}

}