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
			->paginate(10)
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
	public function upcomingForClient(Appointment $appointment): LengthAwarePaginator
	{
		$appointment->load('client');
		return Appointment::query()
			->where('client_id', $appointment->client_id)
			->where('appointment_at', '>', now())
			->where('id', '!=', $appointment->id)
			->orderBy('appointment_at')
			->paginate(10);
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
		$appointmentAt = $this->parseToUtc("{$validated['date']} {$validated['time']}");

		if ($appointmentAt->isPast()) {
			throw new \DomainException('You cannot book a past time.');
		}

		if (!$this->isSlotFree($appointmentAt)) {
			throw new \DomainException('This time slot is already taken.');
		}

		return DB::transaction(function () use ($validated, $appointmentAt) {
			$client = $this->getClient($validated);

			return Appointment::create([
				'client_id' => $client->id,
				'appointment_at' => $appointmentAt,
				'description' => $validated['description'] ?? null,
				'notification_type' => $validated['notification_type'],
			]);
		});
	}

	private function getClient(array $data)
	{
		return Client::updateOrCreate(
			['egn' => $data['egn']],
			[
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
			]
		);
	}

	public function resolveClient(array $data): int
	{
		if ($data['client_mode'] === 'existing') {
			return $data['client_id'];
		}

		$client = Client::create([
			'first_name' => $data['first_name'],
			'last_name' => $data['last_name'],
			'egn' => $data['egn'],
		]);

		return $client->id;
	}
}