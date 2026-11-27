<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every web page.
 *
 * There is no script Content-Security-Policy: Livewire and Alpine evaluate expressions from the HTML, which needs
 * 'unsafe-eval' and would make such a policy of little use. Only framing is limited, against clickjacking.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        // Only over https, a browser ignores it otherwise and it would lock out a local http setup
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }

        foreach ($headers as $name => $value) {
            if (!$response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
