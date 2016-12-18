<?php

namespace App\Tweets;

use Thujohn\Twitter\Facades\Twitter;

class Api {

	/**
	 * @param $username
	 * @param $sinceId
	 * @param int $maxId
	 * @return mixed
	 * @throws \Exception
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

		$data = Twitter::getUserTimeline($params);

		if (is_array($data) && isset($data[0]) && $data[0] === false) {
			throw new \Exception('Error fetching timeline');
		}

		return $data;
	}

}