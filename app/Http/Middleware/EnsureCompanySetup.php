<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Company;

class EnsureCompanySetup
{
    public function handle(Request $request, Closure $next): Response
    {
        // If system has no companies yet, force setup wizard.
        $noCompanies = Company::query()->count() === 0;

        if ($noCompanies) {
            // Allow ONLY the setup wizard endpoints during first run.
            // (Also allow basic static asset paths if you later add them.)
            $routeName = optional($request->route())->getName();

            $allowedRouteNames = [
                'company.create',
                'company.store',
            ];

            $allowedByName = $routeName && in_array($routeName, $allowedRouteNames, true);

            // Also allow direct path access (extra safety if route names change)
            $allowedByPath =
                $request->is('company/create') ||
                $request->is('company');

            if (!$allowedByName && !$allowedByPath) {
                return redirect()->route('company.create');
            }
        }

        return $next($request);
    }
}