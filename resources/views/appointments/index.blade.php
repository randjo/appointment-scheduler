@extends('layouts.app')
@section('content')
	<div class="d-flex justify-content-between mb-3">
		<livewire:appointment-wizard />
	</div>

	@include('appointments._filters')
	@include('appointments._table')
@endsection