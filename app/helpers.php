<?php

function displayMonth($month)
{
	$months = [
		1  => 'January',
		2  => 'February',
		3  => 'March',
		4  => 'April',
		5  => 'May',
		6  => 'June',
		7  => 'July',
		8  => 'August',
		9  => 'September',
		10 => 'October',
		11 => 'November',
		12 => 'December'
	];

	return $months[$month];
}

function displayDate($date)
{
	$ends = array('th','st','nd','rd','th','th','th','th','th','th');
	if (($date %100) >= 11 && ($date%100) <= 13)
		$abbreviation = $date. 'th';
	else
		$abbreviation = $date. $ends[$date % 10];

	return $abbreviation;
}