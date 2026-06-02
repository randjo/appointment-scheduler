<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

class DetectTimezone
{
	public function handle($request, Closure $next)
	{
		$ip = $request->ip();

		$timezone = Cache::remember("timezone_$ip", 86400, static function () use ($ip) {
			$position = Location::get($ip);

			return $position?->timezone ?? 'Europe/Sofia';
		});

		$request->attributes->set('timezone', $timezone);

		return $next($request);
	}
}