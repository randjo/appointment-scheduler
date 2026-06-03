<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Services\AppointmentService;

class AppointmentController extends Controller
{
	public function __construct(private AppointmentService $service) {}

	public function index(Request $request): Factory|View
	{
		$this->service->setQueryParamsInSession($request->query());
		$filters = $this->service->setAppointmentFilters($request->query());
		$appointments = $this->service->list($filters);

		return view('appointments.index', compact('appointments', 'filters'));
	}

	public function edit(Appointment $appointment): Factory|View
	{
		return view('appointments.edit', compact('appointment'));
	}

	public function show(Appointment $appointment): Factory|View
	{
		$upcomingAppointments = $this->service->upcomingForClient($appointment);

		return view('appointments.show', compact('appointment', 'upcomingAppointments'));
	}

	public function destroy(Appointment $appointment): bool
	{
		if ($appointment->delete()) {
			session()->flash('success', 'Appointment deleted successfully');

			return true;
		}

		return false;
	}
}
