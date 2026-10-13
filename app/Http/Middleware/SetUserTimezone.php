<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $timezone = $request->header('X-Timezone')
            ?? optional(auth()->user())->timezone
            ?? config('app.timezone'); // fallback
    
        // Validate timezone
        if (in_array($timezone, timezone_identifiers_list())) {
            // config(['app.timezone' => $timezone]); // removed this because from now we will store the records as per UTC
            // date_default_timezone_set($timezone); // removed this because from now we will store the records as per UTC
    
            // If user is logged in and timezone is different, update it
            if (auth()->check() && auth()->user()->timezone !== $timezone) {
                auth()->user()->update(['timezone' => $timezone]);
            }
        }
        // dd($timezone);
        return $next($request);
    }
    
}
