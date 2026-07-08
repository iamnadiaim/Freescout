<?php

namespace App\Http\Middleware;

use Closure;

class ResponseHeaders
{
    private $unwanted_headers = [
        'X-Powered-By',
        'Server',
    ];

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Secure headers
        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        // Disable caching
        $response->headers->set(
            'Pragma',
            'no-cache'
        );

        $response->headers->set(
            'Cache-Control',
            'no-cache, max-age=0, must-revalidate, no-store'
        );

        return $response;
    }
}
