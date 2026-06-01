<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
	protected $fillable = [
		'client_id',
		'appointment_at',
		'description',
		'notification_type',
	];

	protected $casts = [
		'appointment_at' => 'datetime',
	];

	public function client(): BelongsTo
	{
		return $this->belongsTo(Client::class);
	}

	public function scopeUpcoming($query)
	{
		return $query->where('appointment_at', '>', now());
	}

	public function scopeForClient($query, int $clientId)
	{
		return $query->where('client_id', $clientId);
	}
}