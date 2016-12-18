<?php

namespace App\Http\Controllers;

use App\Breadcrumbs\BreadcrumbInterface;
use App\Tweets\TweetRepository;
use Illuminate\Support\Facades\Input;

class TweetController {

	/**
	 * @var BreadcrumbInterface
	 */
	private $breadcrumbs;

	public function __construct(TweetRepository $tweets, BreadcrumbInterface $breadcrumbs)
	{
		$this->tweets = $tweets;
		$this->breadcrumbs = $breadcrumbs;

		$this->breadcrumbs->setCssClasses('breadcrumb');
		$this->breadcrumbs->setDivider('');
		$this->breadcrumbs->setListElement('ol');
		$this->breadcrumbs->addCrumb('All Tweets', '/');
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
		$this->breadcrumbs->addCrumb('Tweet ID: ' . $tweetId, $tweetId);

		$tweets = $this->tweets->findById($tweetId);
		$single = true;

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

		$this->breadcrumbs->addCrumb($year, $year);
		if ($month) $this->breadcrumbs->addCrumb(displayMonth($month), $month);
		if ($day) $this->breadcrumbs->addCrumb(displayDate($day), $day);

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

		$this->breadcrumbs->addCrumb($tweets->total() . ' found containing "' . $search . '"', 'search');

		return view('tweets.index', compact(
			'tweets',
			'search'
		));
	}

}