<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // owner + admin bypass — full access
        if ($user->role && in_array($user->role->slug, ['owner', 'admin'], true)) {
            return $next($request);
        }

        if (! $user->hasAnyPermission($permissions)) {
            abort(403);
        }

        return $next($request);
    }
}