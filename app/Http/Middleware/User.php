<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\GooglePlay\ExternalOfferLink;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class User
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Google Play external offer token (Android Manage button): keep it in the session before
        // the login redirect, so it is still there after the user logs in on the web.
        ExternalOfferLink::captureFromRequest($request, substr($request->path(), 0, 32));

        if (!Auth::guard('user')->check()) {
            return redirect()->route('user-login');
        }
        return $next($request);
    }
}
