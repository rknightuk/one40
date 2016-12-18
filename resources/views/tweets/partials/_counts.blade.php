@if (isset($monthCounts) && $monthCounts)

	<?php $years = []; ?>

	@foreach ($monthCounts['tweet_count'] as $month)

		@if ( ! in_array($month->year, $years))

			<?php $years[] = $month->year; ?>
			@if (count($years))
				</ul>
			@endif

			<ul class="list-group">

			<a href="/{!!$month->year!!}" class="list-group-item year-heading">
				<span class="badge">{!!$monthCounts['year_counts'][$month->year]!!}</span>
				{!! $month->year !!}
			</a>

		@endif

		<a href="/{!!$month->year!!}/{!!$month->month!!}" class="list-group-item month-item">
			<span class="badge">{!!$month->count!!}</span>
			{!! displayMonth($month->month) !!} {!! $month->year !!}
			<div class="tweet-percentage" style="width:{!!$month->percentage!!}%;"></div>
		</a>

	@endforeach
	
@endif

@if (isset($dayCounts) && $dayCounts)

	<ul class="list-group">

		@foreach ($dayCounts['tweet_count'] as $day)
			<a href="{!!$day->month!!}/{!!$day->day!!}" class="list-group-item month-item">
				<span class="badge">{!!$day->count!!}</span>
				{!! displayMonth($day->month) !!} {!! displayDate($day->day) !!} {!! $day->year !!}
				<div class="tweet-percentage" style="width:{!!$day->percentage!!}%;"></div>
			</a>
		@endforeach

	</ul>

@endif