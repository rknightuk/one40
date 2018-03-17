@extends('layouts.main')

@section('content')

	<div class="row">

		<div class="col-md-9">

			<h1>Tweet Fetch Log</h1>

			<form method="post" action="/logs/purge">
				{{ csrf_field() }}

				<input type="submit" value="Purge Logs">
			</form>

			<div class="activity-feed">
				@forelse ($logs as $log)
					<div class="feed-item">
						@if ($log->count)
							<div class="date">{!! $log->created_at !!}</div>
							<div class="text">Imported {!! $log->count !!} @if ($log->count == 1) tweet @else tweets @endif</div>
						@else
							<div class="date">{!! $log->created_at !!} - No tweets found</div>
						@endif
					</div>
				@empty
					NO LOGS
				@endforelse
			</div>

			{!! $logs->render() !!}

		</div>

		<div class="col-md-3"></div>

	</div>

@endsection