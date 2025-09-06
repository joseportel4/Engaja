<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies extends Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->trustProxies(at: config('proxies.trusted_ips'));

        return $next($request);
    }
}
