<?php

Auth::routes();

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('/', 'TweetController@index');

Route::get('/tweet/{id}', [
	'as' => 'tweet.single',
	'uses' => 'TweetController@show'
]);

Route::get('/tweet', function() { return redirect('/random'); });
Route::get('/random', 'TweetController@random');

Route::get('{year}/{month?}/{day?}', 'TweetController@date')->where([
	'year' => '[0-9]+',
	'month' => '[0-9]+',
	'day' => '[0-9]+'
]);

Route::get('/search', function() { return redirect('/'); });
Route::post('/search', 'TweetController@search');

Route::get('/search/{search?}', [
	'as' => 'search',
	'uses' => 'TweetController@searchResults'
]);
