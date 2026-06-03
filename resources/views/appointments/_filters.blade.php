<form method="GET" class="row mb-4">
	<div class="col">
		<input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}" placeholder="From">
	</div>

	<div class="col">
		<input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}" placeholder="To">
	</div>

	<div class="col">
		<input type="text" name="egn" class="form-control" value="{{ $filters['egn'] ?? '' }}" placeholder="EGN">
	</div>

	<div class="col">
		<button class="btn btn-primary w-100">Filter</button>
	</div>
</form>