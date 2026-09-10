<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ConfirmClientPublicExit
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('cliente')->check()) {
            return $next($request);
        }

        $destination = '/'.ltrim($request->getRequestUri(), '/');

        return redirect()->route('client.portal', 'productos')
            ->with('client_public_destination', $destination)
            ->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
