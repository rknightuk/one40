@extends('layouts.main')

@section('content')

<div class="row">

	<div class="col-md-9">

		{!! Breadcrumbs::render() !!}

		@include('tweets.partials._tweets')

	</div>

	<div class="col-md-3">
		<ul class="list-group">
			<a href="/random" class="list-group-item">
				<i class="fa fa-random"></i> Random tweet
			</a>
		</ul>

		@include('tweets.partials._counts')

	</div>

</div>

<footer>
	This is a backup of all of <a href="http://twitter.com/{{ env('TWITTER_USERNAME') }}">{{ env('TWITTER_USERNAME') }}</a>'s tweets, powered by <a href="https://github.com/rmlewisuk/one40">One40</a>.
</footer>

@endsection