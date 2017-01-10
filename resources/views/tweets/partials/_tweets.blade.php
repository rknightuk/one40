<ul class="tweet-list">

	@forelse($tweets as $tweet)

		<div class="media tweet-list__tweet-body">
			<div class="media-body">
				<p>{!! nl2br(html_entity_decode($tweet->present()->tweet)) !!}</p>
				<p class="tweet-list__meta">
					{!! $tweet->present()->metadata !!}
					<br>
					{!! link_to_route('tweet.single', 'permalink', ['id' => $tweet->tweetid]) !!}
				</p>
		  	</div>

		  	@if ($tweet->photos())
			  	@foreach ($tweet->photos() as $photo)

					<a class="pull-left" href="{!! $photo['url'] !!}">
						<img class="media-object" src="{!!$photo['thumb'] !!}" width="75" height="75">
					</a>

				@endforeach
			@endif

		</div>

	@empty

	    <p>No tweets found</p>

	@endforelse

<ul>

@if ($tweets && ! isset($single))
	<div class="text-center">
		{!! $tweets->render() !!}
	</div>
@endif