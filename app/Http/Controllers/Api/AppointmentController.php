<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
	public function __construct(private AppointmentService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
	    $filters = $request->query();
	    $appointments = $this->service->list($filters);

	    return response()->json([
		    'data' => $appointments->items(),
	    ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
	    try {
		    $appointment = $this->service->create($request->validated());

		    return response()->json([
			    'message' => 'Appointment created successfully',
			    'data' => new AppointmentResource($appointment)
		    ], 201);

	    } catch (\DomainException $e) {
		    return response()->json([
			    'message' => $e->getMessage()
		    ], 422);
	    }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(int $id): JsonResponse
	{
		if (!$appointment = Appointment::find($id)) {
			return response()->json([
				'message' => 'Appointment not found',
			], 404);
		}

		$appointment->delete();

		return response()->json(null, 204);
	}
}
