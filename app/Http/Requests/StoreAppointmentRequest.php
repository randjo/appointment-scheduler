<?php

namespace App\Http\Requests;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAppointmentRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'first_name' => 'required|string|max:100',
			'last_name'  => 'required|string|max:100',
			'egn'        => 'required|digits:10',
			'date' => 'required',
			'time' => [
				'required',
				'date_format:H:i',
				function ($attribute, $value, $fail) {
					$hour = (int) explode(':', $value)[0];
					$minute = (int) explode(':', $value)[1];

					if ($minute !== 0) {
						$fail('Only full hours are allowed (e.g. 08:00, 09:00).');
					}

					if ($hour < 8 || $hour > 18) {
						$fail('Working hours are between 08:00 and 18:00.');
					}
				},
			],

			'description' => 'nullable|string',
			'notification_type' => ['required', new Enum(NotificationType::class)],
		];
	}
}