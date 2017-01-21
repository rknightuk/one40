<?php

namespace App\Http\Controllers;

use App\Breadcrumbs\BreadcrumbInterface;
use App\Tweets\TweetQuery;
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

	public function index($year = null, $month = null, $date = null)
	{
		$query = new TweetQuery();
		$monthCounts = null;
		$dayCounts = null;

		if ($year) {
			$query->forYear($year);
			$this->breadcrumbs->addCrumb($year, $year);
		}
		if ($month) {
			$query->forMonth($month);
			$this->breadcrumbs->addCrumb(displayMonth($month), $month);
		}
		if ($date) {
			$query->forDate($date);
			$this->breadcrumbs->addCrumb(displayDate($date), $date);
		}

		list($ids, $tweets) = $this->tweets->all($query);

		if (! $month) $monthCounts = $this->tweets->monthCount($ids);
		if ($month && ! $date) $dayCounts = $this->tweets->dayCount($ids);

		return view('tweets.index', compact(
			'tweets',
			'monthCounts',
			'dayCounts'
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