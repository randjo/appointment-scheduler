<table class="table table-striped">
	<thead>
	<tr>
		<th>ID</th>
		<th>First Name</th>
		<th>Last Name</th>
		<th>EGN</th>
		<th>Date</th>
		<th>Notification</th>
		<th></th>
	</tr>
	</thead>

	<tbody>
	@foreach($appointments as $appointment)
		<tr>
			<td>{{ $appointment->id }}</td>
			<td>{{ $appointment->client->first_name }}</td>
			<td>{{ $appointment->client->last_name }}</td>
			<td>{{ $appointment->client->egn }}</td>
			<td>{{ $appointment->appointment_at_local }}</td>
			<td>{{ $appointment->notification_type }}</td>
			<td>
				<a
						href="{{ route('appointments.edit', $appointment->id) }}"
						class="btn btn-sm btn-outline-primary"
				>
					✏️
				</a>
				<a
						href="{{ route('appointments.show', $appointment->id) }}"
						class="btn btn-sm btn-outline-secondary"
				>
					👁️
				</a>
				<button
						class="btn btn-sm btn-outline-danger"
						onclick="confirmDelete({{ $appointment->id }})"
				>
					🗑️
				</button>
			</td>
		</tr>
	@endforeach
	</tbody>
</table>

<div>
	{{ $appointments->links() }}
</div>