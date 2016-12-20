<?php

namespace App\Tweets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TweetRepository {

	protected static $paginate = 50;

	/**
	 * Get all tweets
	 *
	 * @return array Tweets
	 */
	public function getAll()
	{
		return Tweet::orderBy('time', 'desc')->paginate(self::$paginate);
	}

	/**
	 * Find tweet by id
	 *
	 * @param $id
	 * @return Tweet
	 */
	public function findById($id)
	{
		return Tweet::where('tweetid', $id)->get();
	}

	/**
	 * Get a random tweet
	 *
	 * @return Tweet
	 */
	public function getRandomTweet()
	{
		return Tweet::orderByRaw("RAND()")->where('type', '!=', '1')->first();
	}

	/**
	 * Get latest tweet
	 *
	 * @return Tweet
	 */
	public function getLatest()
	{
		return Tweet::orderBy('time', 'desc')->first();
	}

	/**
	 * Get latest tweet ID
	 *
	 * @return int
	 */
	public function getLatestId()
	{
		$latest = $this->getLatest();
		return $latest ? $latest->tweetid : 0;
	}

	/**
	 * Create tweet
	 *
	 * @param $tweet
	 * @return Tweet
	 */
	public function addTweet($tweet)
	{
		return Tweet::firstOrCreate($tweet);
	}

	/**
	 * Add tweets to DB
	 *
	 * @param array $tweets
	 */
	public function addTweets(array $tweets)
	{
		foreach ($tweets as $tweet) {
			$this->addTweet($tweet);
		}
	}

	/**
	 * Search tweets
	 *
	 * @param  String $search
	 * @return array
	 */
	public function search($search)
	{
		return Tweet::where('text', 'LIKE', '%'.$search.'%')
			->orWhere('extra', 'LIKE', '%'.$search.'%')
			->orderBy('time', 'desc')
			->paginate(self::$paginate);
	}

	public function stats()
	{
		$clients = $this->topClients();

		$totals = $this->typeCounts()->mapWithKeys(function($type) {
			return [TweetType::getTypeString($type['type']) => $type['count']];
		});

		$totals['all'] = $totals->sum();

		$average  = $this->average();

		return [$totals, $clients, $average];

	}

	public function topClients()
	{
		return Tweet::select(DB::raw('count(*) as count, source'))
			->groupBy('source')
			->orderBy('count', 'desc')
			->limit(10)
			->get();
	}

	public function typeCounts()
	{
		return Tweet::select(DB::raw('count(*) as count, type'))
			->groupBy('type')
			->get();
	}

	public function average()
	{
		$firstTweet = Tweet::first();

		$daysSince = $firstTweet->time->diffInDays(Carbon::now());

		return [
			'average' => number_format(Tweet::count() / $daysSince, 2),
			'daysSince' => $daysSince,
			'first' => $firstTweet->time
		];
	}

	/**
	 * Get tweets for specific date range
	 *
	 * @param  Int $year
	 * @param  Int $month
	 * @param  Int $day
	 * @return array Tweets for date range
	 */
	public function getForDate($year, $month, $day)
	{
		$date = Carbon::createFromDate($year, $month, $day);

		if ($day)
		{
			$start = strtotime($date->copy()->startOfDay());
			$end = strtotime($date->copy()->endOfDay());
		}
		elseif ($month)
		{
			$start = strtotime($date->copy()->startOfMonth());
			$end = strtotime($date->copy()->endOfMonth());
		}
		else
		{
			$start = strtotime($date->copy()->startOfYear());
			$end = strtotime($date->copy()->endOfYear());
		}

		return Tweet::where('time', '>=', $start)
			->where('time', '<=', $end)
			->orderBy('time', 'desc')
			->paginate(self::$paginate);
	}

	public function monthCount()
	{
		$counts = DB::select(DB::raw('select Year(FROM_UNIXTIME(time)) as year, Month(FROM_UNIXTIME(time)) as month, Count(*) as count
			FROM tweets
			GROUP BY Year(FROM_UNIXTIME(time)), Month(FROM_UNIXTIME(time))
			ORDER BY Year(FROM_UNIXTIME(time)) desc, Month(FROM_UNIXTIME(time)) desc'));

		return $this->calculatePercentagesAndTotal($counts);
	}

	public function monthCountForYear($year)
	{
		$counts = DB::select(DB::raw('select Year(FROM_UNIXTIME(time)) as year, Month(FROM_UNIXTIME(time)) as month, Count(*) as count
			FROM tweets
			where year(FROM_UNIXTIME(time)) = '.$year.'
			GROUP BY Year(FROM_UNIXTIME(time)), Month(FROM_UNIXTIME(time))
			ORDER BY Year(FROM_UNIXTIME(time)) desc, Month(FROM_UNIXTIME(time)) desc'));

		return $this->calculatePercentagesAndTotal($counts);
	}

	public function dayCountForMonth($year, $month)
	{
		$counts = DB::select(DB::raw('select Year(FROM_UNIXTIME(time)) as year, Month(FROM_UNIXTIME(time)) as month, Day(FROM_UNIXTIME(time)) as day, Count(*) as count
			FROM tweets
			where year(FROM_UNIXTIME(time)) = '.$year.'
			and month(FROM_UNIXTIME(time)) = '.$month.'
			GROUP BY Year(FROM_UNIXTIME(time)), Month(FROM_UNIXTIME(time)), Day(FROM_UNIXTIME(time))'));

		return $this->calculatePercentagesAndTotal($counts);
	}

	public function calculatePercentagesAndTotal($counts)
	{
		$max = 0;
		$yearCounts = [];

		foreach($counts as $count)
		{
			if($count->count > $max)
			{
				$max = $count->count;
			}
		}

		foreach($counts as $count)
		{
			$count->percentage = round(($count->count / $max) * 100);

			if ( ! isset($yearCounts[$count->year]))
				$yearCounts[$count->year] = 0;

			$yearCounts[$count->year] += $count->count;
		}

		$counts['tweet_count'] = $counts;

		$counts['year_counts'] = $yearCounts;

		return $counts;
	}

}