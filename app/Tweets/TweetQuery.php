<?php

namespace App\Tweets;

class TweetQuery {

	public $year;
	public $month;
	public $date;
	public $search;

	public function forYear($year)
	{
		$this->year = $year;
	}

	public function forMonth($month)
	{
		$this->month = $month;
	}

	public function forDate($date)
	{
		$this->date = $date;
	}

	public function search($search)
	{
		$this->search = $search;
	}

}