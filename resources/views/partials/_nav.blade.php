<div class="navbar navbar-inverse navbar-fixed-top navbar-custom">
	<div class="container">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="/">One40</a>
		</div>
		<div class="navbar-collapse collapse">
			<ul class="nav navbar-nav">
			</ul>

			<form class="navbar-form navbar-right" action="/search" method="post" role="search">
				<div class="form-group">
					<input type="text" name="search" class="form-control" placeholder="Search">
					{!! Form::token() !!}
				</div>
			</form>
			<ul class="nav navbar-nav navbar-right">
			    @if (Auth::check())
			    	<li><a href="/auth/logout">Logout</a></li>
			   	@else
			   		<li><a href="/auth/login">Login</a></li>
			   	@endif
			</ul>
		</div>
	</div>
</div>