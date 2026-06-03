<?php

namespace App\Http\Requests;

use App\Enums\NotificationType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAppointmentRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'first_name' => 'required|string|max:100',
			'last_name'  => 'required|string|max:100',
			'egn' => [
				'required',
				'unique:clients,egn',
				'digits:10',
			],
			'description' => 'nullable|string',
			'notification_type' => ['required', new Enum(NotificationType::class)],
			'appointment_at' => [
				'required',
				'date_format:Y-m-d H:i',
				function ($attribute, $value, $fail) {
					$dateTimeLocal = Carbon::createFromFormat('Y-m-d H:i', $value, request()->attributes->get('timezone', 'Europe/Sofia'));
					$dateTime = $dateTimeLocal->copy()->utc();
					$minute = (int) $dateTime->format('i');
					$hour = (int) $dateTimeLocal->format('H');

					if ($minute !== 0) {
						$fail('Only full hours are allowed (e.g. 08:00, 09:00).');
					}

					if ($hour < 8 || $hour > 18) {
						$fail('Working hours are between 08:00 and 18:00.');
					}
				},
			],
		];
	}
}