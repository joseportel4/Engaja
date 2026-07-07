<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSistemaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $isCartasRoute = $request->routeIs('cartas.*') || $request->is('cartas/*') || $request->is('cartas');

        if ($isCartasRoute && ! $user->isCartasUser()) {
            abort(403);
        }

        if (! $isCartasRoute && $user->isCartasUser() && ! $this->isSharedAuthRoute($request)) {
            abort(403);
        }

        return $next($request);
    }

    private function isSharedAuthRoute(Request $request): bool
    {
        return $request->routeIs(
            'logout',
            'verification.*',
            'password.*'
        );
    }
}
