<!DOCTYPE html>
<html lang="bg">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>Appointment Scheduler System</title>

	@vite(['resources/css/app.css'])
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	@livewireStyles
</head>

<body class="bg-gray-100">
	<div class="min-h-screen">
		<div class="d-flex justify-content-between mb-3">
			<h1 class="mb-4">
				<a href="{{ route('appointments.index') }}" class="text-decoration-none text-dark">
					Appointments Scheduler
				</a>
			</h1>
		</div>

		<meta name="csrf-token" content="{{ csrf_token() }}">

		@yield('content')

		@if (session()->has('success'))
			<div id="flash-data"
			     data-message="{{ session('success') }}"
			     data-type="success">
			</div>
		@endif
	</div>

	@livewireScripts
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	@vite(['resources/js/app.js'])
</body>
</html>