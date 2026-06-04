<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
	/**
	 * Get a list with optional filters (date range, egn)
	 */
	public function list(array $filters = []): LengthAwarePaginator
	{
		return Appointment::query()
			->with('client')
			->when($filters['egn'] ?? null, function($query, $egn) {
				$query->whereHas('client', function($query) use ($egn) {
					$query->where('egn', $egn);
				});
			})
			->when($filters['from'] ?? null, function($query, $from) {
				$query->where('appointment_at', '>=', Carbon::parse($from)
					->timezone($this->tz())
					->startOfDay()
					->utc());
			})
			->when($filters['to'] ?? null, function ($query, $to) {
				$query->where('appointment_at', '<=', Carbon::parse($to)
					->timezone($this->tz())
					->endOfDay()
					->utc());
			})
			->orderBy('appointment_at')
			->paginate($filters['per_page'] ?? 10)
			->withQueryString();
	}

	/**
	 * Get a single appointment
	 */
	public function find(int $id): Appointment
	{
		return Appointment::with('client')->findOrFail($id);
	}

	/**
	 * Upcoming appointments for a client
	 */
	public function upcomingForClient(Appointment $appointment, int $perPage = null): LengthAwarePaginator
	{
		$appointment->load('client');
		return Appointment::query()
			->where('client_id', $appointment->client_id)
			->where('appointment_at', '>', now())
			->where('id', '!=', $appointment->id)
			->orderBy('appointment_at')
			->paginate($perPage ?? 10);
	}

	public function parseToUtc($datetime): Carbon
	{
		return Carbon::parse($datetime, $this->tz())->utc();
	}

	public function tz(): string
	{
		return request()->attributes->get('timezone', 'Europe/Sofia');
	}

	public function getAvailableDates(Carbon $from, Carbon $to, $appointmentId = null): array
	{
		$from = $from->copy()->timezone($this->tz())->startOfDay();
		$to   = $to->copy()->timezone($this->tz())->endOfDay();
		$dates = [];

		$appointments = Appointment::query()
			->where('appointment_at', '>=', $from->copy()->utc())
			->where('appointment_at', '<=', $to->copy()->utc())
			->get()
			->groupBy(fn ($a) =>
			$a->appointment_at->timezone($this->tz())->toDateString()
			);

		$date = $from->copy();

		while ($date->lte($to)) {
			$day = $date->toDateString();

			if (!isset($appointments[$day])) {
				$dates[] = $day;
				$date->addDay();
				continue;
			}

			$slots = $this->getSlotsForDate($date, $appointmentId);

			if (count($slots) > 0) {
				$dates[] = $day;
			}

			$date->addDay();
		}

		return $dates;
	}

	public function getSlotsForDate(Carbon $date, $appointmentId = null): array
	{
		$date = $date->copy()->timezone($this->tz())->startOfDay();

		$startOfDay = $date->copy()->setTime(8, 0);
		$endOfDay   = $date->copy()->setTime(19, 0);

		$slotDuration = 60;
		$slots = [];

		$now = now()->timezone($this->tz());

		if ($date->isToday()) {
			$nextSlot = $now->copy()->addHour()->startOfHour();
			$startOfDay = $nextSlot->max($startOfDay);
		}

		$start = $startOfDay;

		while ($start->lt($endOfDay)) {
			$slotEnd = $start->copy()->addMinutes($slotDuration);

			if ($slotEnd->gt($endOfDay)) {
				break;
			}

			if ($this->isSlotFree($start, $appointmentId)) {
				$slots[] = $start->format('H:i');
			}

			$start->addMinutes($slotDuration);
		}

		return $slots;
	}

	public function isSlotFree(Carbon $slot, ?int $ignoreAppointmentId = null): bool
	{
		return !Appointment::query()
			->when($ignoreAppointmentId, fn ($q) => $q->whereKeyNot($ignoreAppointmentId))
			->where('appointment_at', $slot->copy()->utc())
			->exists();
	}

	public function setQueryParamsInSession($requestQuery): void
	{
		if ($requestQuery) {
			session(['appointments.filters' => $requestQuery]);
		} else {
			session(['appointments.filters' => []]);
		}
	}

	public function setAppointmentFilters($requestQuery): array
	{
		return [
			'from' => $requestQuery['from'] ?? null,
			'to' => $requestQuery['to'] ?? null,
			'egn' => $requestQuery['egn'] ?? null,
			'page' => $requestQuery['from'] ?? null,
		];
	}

	public function create(array $validated)
	{
		$this->validateDateTimeSlot($validated['appointment_at']);

		return DB::transaction(function () use ($validated) {
			$client = $this->updateOrCreateClient($validated);

			return Appointment::create([
				'client_id' => $client->id,
				'appointment_at' => $validated['appointment_at'],
				'description' => $validated['description'] ?? null,
				'notification_type' => $validated['notification_type'],
			]);
		});
	}

	public function update(Appointment $appointment, array $data): Appointment
	{
		$this->validateDateTimeSlot($data['appointment_at'], $appointment);

		DB::transaction(function () use ($appointment, $data) {
			$client = $this->updateOrCreateClient($data);
			$appointment->update([
				'client_id' => $client->id,
				'appointment_at' => $data['appointment_at'],
				'description' => $data['description'] ?? null,
				'notification_type' => $data['notification_type'],
			]);
		});

		return $appointment->fresh(['client']);
	}

	public function validateDateTimeSlot(&$appointmentAt, ?Appointment $appointment = null): void
	{
		$appointmentAt = $this->parseToUtc($appointmentAt);

		if ($appointmentAt->isPast()) {
			throw new \DomainException('You cannot book a past time.');
		}

		if (!$this->isSlotFree($appointmentAt, $appointment?->id)) {
			throw new \DomainException('This time slot is already taken.');
		}
	}

	public function resolveClient(array $data): int
	{
		if ($data['client_mode'] === 'existing') {
			return $data['client_id'];
		}

		return $this->updateOrCreateClient($data)->id;
	}

	private function updateOrCreateClient(array $data)
	{
		return Client::updateOrCreate(
			['egn' => $data['egn']],
			[
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
			]
		);
	}
}