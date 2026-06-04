<?php

namespace App\Livewire;

use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\Client;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class AppointmentWizard extends Component
{
	public bool $showModal = false;
	public int $step = 1;
	public $client_id;
	public string $client_mode = 'existing';
	public ?string $selectedDate = null;
	public ?string $selectedTime = null;
	public string $first_name = '';
	public string $last_name = '';
	public string $egn = '';
	public string $description = '';
	public string $notification_type = NotificationType::SMS->value;
	public array $dates = [];
	public $times = [];
	public Collection $clients;
	public ?Appointment $appointment = null;
	public bool $isEditMode = false;
	protected AppointmentService $appointmentService;

	public function boot(AppointmentService $appointmentService): void
	{
		$this->appointmentService = $appointmentService;
	}

	public function mount(?Appointment $appointment = null): void
	{
		if ($appointment) {
			$this->appointment = $appointment;
			$this->isEditMode = true;

			$this->fillFromAppointment();
		}

		$this->dates = $this->appointmentService->getAvailableDates(now(), now()->addYear()->endOfDay(), $this->appointment?->id);
	}

	public function setClientMode($mode): void
	{
		$this->client_mode = $mode;

		if ($this->isEditMode) {
			return;
		}

		if ($this->client_mode === 'existing') {
			$this->reset(['first_name', 'last_name', 'egn']);
			return;
		}

		$this->client_id = null;
	}

	public function openModal(): void
	{
		if (!$this->isEditMode) {
			$this->resetWizard();
		}

		$this->showModal = true;
	}

	protected function fillFromAppointment(): void
	{
		$localDateTime = $this->appointment->appointment_at->timezone(request()->attributes->get('timezone', 'Europe/Sofia'));
		$this->selectedDate = $localDateTime->format('Y-m-d');
		$this->selectedTime = $localDateTime->format('H:i');
		$this->client_id = $this->appointment->client->id;
		$this->first_name = $this->appointment->client->first_name;
		$this->last_name = $this->appointment->client->last_name;
		$this->egn = $this->appointment->client->egn;
		$this->description = $this->appointment->description;
		$this->notification_type = $this->appointment->notification_type->value;
	}

	public function resetWizard(): void
	{
		$this->reset([
			'step',
			'selectedDate',
			'selectedTime',
			'first_name',
			'last_name',
			'egn',
			'description',
			'notification_type',
		]);

		$this->step = 1;
	}

	public function updatedSelectedDate(): void
	{
		$this->resetErrorBag('selectedDate');
	}

	public function goToStepTime(): void
	{
		if (!$this->selectedDate) {
			return;
		}

		$this->times = $this->appointmentService->getSlotsForDate(Carbon::parse($this->selectedDate), $this->appointment?->id);
		$this->step = 2;

		if (!$this->isEditMode) {
			$this->selectedTime = null;
		}
	}

	public function goToStepAppointmentData(): void
	{
		if (!$this->selectedTime) {
			return;
		}

		$this->clients = Client::orderBy('first_name')->orderBy('last_name')->get();
		$this->step = 3;
	}

	protected function rules(): array
	{
		return [
			'selectedDate' => ['required'],
			'selectedTime' => ['required'],
			'client_mode' => ['required', Rule::in(['existing', 'new'])],
			'client_id' => [
				'required_if:client_mode,existing',
				'nullable',
				'exists:clients,id',
			],
			'first_name' => [
				'required_if:client_mode,new',
				'nullable',
				'string',
				'max:100',
			],
			'last_name' => [
				'required_if:client_mode,new',
				'nullable',
				'string',
				'max:100',
			],
			'egn' => [
				'required_if:client_mode,new',
				'nullable',
				'digits:10',
			],
			'description' => ['nullable', 'string'],
			'notification_type' => ['required', new Enum(NotificationType::class)],
		];
	}

	public function messages(): array
	{
		return [
			'selectedDate.required' => 'Please select a date.',
			'selectedTime.required' => 'Please select a time.',
			'selectedTime.date_format' => 'Invalid time format.',
			'client_id.required_if' => 'Please select an existing client.',
			'first_name.required_if' => 'First name is required.',
			'last_name.required_if' => 'Last name is required.',
			'egn.required_if' => 'EGN is required.',
			'egn.digits' => 'EGN must be exactly 10 digits.',
			'notification_type.required' => 'Please choose notification type.',
		];
	}

	public function save(): false|Redirector
	{
		$validated = $this->validate();
		$appointmentAt = $this->appointmentService->parseToUtc("$this->selectedDate $this->selectedTime");

		if ($appointmentAt->isPast()) {
			$this->addError('selectedDate', 'You cannot book a past time.');
			$this->step = 1;
			$this->selectedDate = null;
			$this->selectedTime = null;
			return false;
		}
		if (!$this->appointmentService->isSlotFree($appointmentAt, $this->appointment?->id)) {
			$this->addError('selectedDate', 'This slot was just booked.');
			$this->step = 1;
			$this->selectedDate = null;
			$this->selectedTime = null;
			return false;
		}

		DB::transaction(function () use ($validated, $appointmentAt) {
			$clientId = $this->appointmentService->resolveClient($validated);

			if ($this->isEditMode) {
				$this->appointment->update([
					'client_id' => $clientId,
					'appointment_at' => $appointmentAt,
					'description' => $validated['description'] ?? null,
					'notification_type' => $validated['notification_type'],
				]);
			} else {
				Appointment::create([
					'client_id' => $clientId,
					'appointment_at' => $appointmentAt,
					'description' => $validated['description'] ?? null,
					'notification_type' => $validated['notification_type'],
				]);
			}
		});

		session()->flash('success',
			'Успешно '
			. ($this->isEditMode ? 'редактирахте' : 'запазихте')
			. ' час! Клиентът ще бъде уведомен чрез '
			. NotificationType::from($this->notification_type)->value
		);

		$this->reset();

		$queryParams = session('appointments.filters', []);
		session(['appointments.filters' => '']);

		return redirect()->route('appointments.list', $queryParams);
	}
}