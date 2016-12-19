<!DOCTYPE HTML>
<html>

	<head>
	
		<title>One40</title>
		
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width,initial-scale=1">

		<link href='http://fonts.googleapis.com/css?family=Titillium+Web:400,600' rel='stylesheet' type='text/css'>
		<link rel="shortcut icon" href="img/favicon.ico">
		<link rel="stylesheet" href="/assets/css/bootstrap.min.css">
		<link rel="stylesheet" href="/assets/css/style.css">
		
	</head>
	
	<body>

		@include('partials._nav')

		<div class="container" id="app-wrapper">

			@include('partials._flash')

			@include('partials._errors')

			@yield('content')

		</div>

		<script src="/assets/js/jquery.min.js"></script>
		<script src="/assets/js/bootstrap.min.js"></script>
		<script src="https://use.fontawesome.com/7fba236428.js"></script>
		<script src="/assets/js/archivey.js"></script>
		

	</body>
	
</html>