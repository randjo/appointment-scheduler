@extends('layouts.app')

@section('content')
	<div class="container">
		<h3 class="mt-3">Appointment Details</h3>

		<div class="card mt-3 p-3">
			<p><strong>Client:</strong> {{ $appointment->client->first_name }} {{ $appointment->client->last_name }}</p>
			<p><strong>EGN:</strong> {{ $appointment->client->egn }}</p>
			<p><strong>Date:</strong> {{ $appointment->appointment_at_local }}</p>
			<p><strong>Description:</strong> {{ $appointment->description }}</p>
			<p><strong>Notification:</strong> {{ $appointment->notification_type->value }}</p>
		</div>

		<h5 class="mt-5">Upcoming appointments for this client</h5>

		@include('appointments._table', [
			'appointments' => $upcomingAppointments
		])
	</div>
@endsection