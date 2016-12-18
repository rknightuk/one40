<?php

namespace App\Http\Controllers;

use App\Tweets\TweetRepository;
use Illuminate\Support\Facades\Input;

class TweetController {

	public function __construct(TweetRepository $tweets)
	{
		$this->tweets = $tweets;
	}

	public function index()
	{
		$tweets = $this->tweets->getAll();

		$monthCounts = $this->tweets->monthCount();

		return view('tweets.index', compact(
			'tweets',
			'monthCounts'
		));
	}

	public function show($tweetId)
	{
		$tweets = $this->tweets->findById($tweetId);
		$single = true;

		dd(compact('tweets', 'single'));

		return view('tweets.index', compact(
			'tweets',
			'single'
		));
	}

	public function random()
	{
		$tweet = $this->tweets->getRandomTweet();

		return $this->show($tweet->tweetid);
	}

	public function date($year, $month = null, $day = null)
	{
		$monthCounts = null;
		$dayCounts = null;

		$tweets = $this->tweets->getForDate($year, $month, $day);

		if ( ! $month)
		{
			$monthCounts = $this->tweets->monthCountForYear($year);
		}
		elseif ( ! $day)
		{
			$dayCounts = $this->tweets->dayCountForMonth($year, $month);
		}

		return view('tweets.index', compact(
			'tweets',
			'monthCounts',
			'dayCounts'
		));
	}

	public function search()
	{
		$search = Input::get('search');

		return redirect('search/'.$search);
	}

	public function searchResults($search)
	{
		$tweets = $this->tweets->search($search);

		return view('tweets.index', compact(
			'tweets',
			'search'
		));
	}

}