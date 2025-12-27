<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveCompany
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);

        $count = $user->companies()->count();

        if ($count === 0) {
            return redirect()->route('company.create');
        }

        if (!$user->active_company_id || !$user->companies()->whereKey($user->active_company_id)->exists()) {
            $firstCompanyId = $user->companies()->orderBy('companies.id')->value('companies.id');
            $user->active_company_id = $firstCompanyId;
            $user->save();
        }

        return $next($request);
    }
}