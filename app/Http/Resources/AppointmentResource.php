<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
	public function toArray($request): array
	{
		return [
			'id' => $this->id,
			'client' => [
				'id' => $this->client->id,
				'first_name' => $this->client->first_name,
				'last_name' => $this->client->last_name,
				'egn' => $this->client->egn,
			],
			'appointment_at' => $this->appointment_at
				->timezone(request()->attributes->get('timezone', 'Europe/Sofia'))
				->format('Y-m-d H:i:s'),
			'description' => $this->description,
			'notification_type' => $this->notification_type,
		];
	}
}