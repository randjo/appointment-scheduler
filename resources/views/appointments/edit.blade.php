@extends('layouts.app')

@section('content')
	<div class="grid grid-cols-3 gap-4">
		<div class="flex justify-start">
			<livewire:appointment-wizard :appointment="$appointment" />
		</div>
	</div>
@endsection