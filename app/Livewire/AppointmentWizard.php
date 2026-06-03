<?php

namespace App\Livewire;

use App\Enums\NotificationType;
use App\Models\Appointment;
use App\Models\Client;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

	public function mount(): void
	{
		$this->dates = app(AppointmentService::class)->getAvailableDates(now(), now()->addYear()->endOfDay());
	}

	public function setClientMode($mode): void
	{
		$this->client_mode = $mode;
	}

	public function openModal(): void
	{
		$this->resetWizard();
		$this->showModal = true;
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

	public function goToStepTime(AppointmentService $appointmentService): void
	{
		if (!$this->selectedDate) {
			return;
		}

		$this->times = $appointmentService->getSlotsForDate(Carbon::parse($this->selectedDate));
		$this->selectedTime = null;
		$this->step = 2;
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

	public function createAppointment(AppointmentService $appointmentService): false|Redirector
	{
		$validated = $this->validate();

		$appointmentAt = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime, $appointmentService->tz())->utc();

		if (!$appointmentService->isSlotFree($appointmentAt)) {
			$this->addError('selectedDate', 'This slot was just booked.');
			$this->step = 1;
			$this->selectedDate = null;
			$this->selectedTime = null;
			return false;
		}

		DB::transaction(function () use ($validated, $appointmentAt) {
			$clientId = $this->resolveClient($validated);

			Appointment::create([
				'client_id' => $clientId,
				'appointment_at' => $appointmentAt,
				'description' => $validated['description'] ?? null,
				'notification_type' => $validated['notification_type'],
			]);
		});

		session()->flash('success',
			"Успешно запазихте час! Клиентът ще бъде уведомен чрез " .
			NotificationType::from($this->notification_type)->value
		);

		$this->reset();

		return redirect()->route('appointments.index');
	}

	private function resolveClient(array $data): int
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