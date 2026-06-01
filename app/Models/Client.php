<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
	protected $fillable = [
		'first_name',
		'last_name',
		'egn',
	];

	public function appointments(): HasMany
	{
		return $this->hasMany(Appointment::class);
	}

	public function upcomingAppointments(): HasMany
	{
		return $this->hasMany(Appointment::class)
			->where('appointment_at', '>', now())
			->orderBy('appointment_at');
	}
}
