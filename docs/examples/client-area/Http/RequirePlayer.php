<?php

declare(strict_types=1);

namespace DavidBravo\ClientArea\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class RequirePlayer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('client_area')->guest()) {
            return redirect()->route('client-area.login');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
