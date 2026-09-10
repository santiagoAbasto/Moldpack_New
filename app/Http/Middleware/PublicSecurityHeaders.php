<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline headers for the public site and the client portal. The admin keeps
 * its stricter policy in AdminSecurityHeaders. No full CSP here: the public
 * pages rely on inline scripts, Google Fonts, YouTube and Google Maps embeds.
 */
class PublicSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('admin*')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), payment=(), usb=(), geolocation=(self)');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; object-src 'none'");

        if ($request->is('area-clientes*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
