<?php

namespace App\Http\Controllers;

use App\Tweets\TweetRepository;

class AdminController extends Controller
{
	/**
	 * @var TweetRepository
	 */
	private $tweets;

	public function __construct(TweetRepository $tweets)
	{
		$this->tweets = $tweets;
	}

    public function stats()
    {
    	list($totals, $clients, $average) = $this->tweets->stats();

	    return view('admin.stats', compact(
		    'totals', 'clients', 'average'
	    ));
    }
}
