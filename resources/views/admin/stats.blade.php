@extends('layouts.main')

@section('content')

	<div class="row">

		<div class="col-md-9">

			<h1>Tweet Stats</h1>

			Total of <strong>{{ $totals['all'] }}</strong> tweets, with an average of <strong>{{ $average['average'] }}</strong> tweets per day since {{ $average['first']->format('jS F Y') }}, <strong>{{ $average['daysSince'] }}</strong> days ago.

			You've replied <strong>{{ $totals['reply'] }}</strong> times, and retweeted <strong>{{ $totals['retweet'] }}</strong> tweets.

			<h2>Types</h2>

			<ul>
				@forelse ($totals as $key => $type)
					<li>{!! $type !!} {!! $key !!}</li>
				@empty
					NO STATS FOUND
				@endforelse
			</ul>

			<h2>Top Clients</h2>

			<ul>
				@forelse ($clients as $client)
					<li>{!! $client['count'] !!} tweets - {!! $client['source'] !!}</li>
				@empty
					NO STATS FOUND
				@endforelse
			</ul>

		</div>

		<div class="col-md-3"></div>

	</div>

@endsection