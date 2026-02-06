<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Depot;

class CompanySwitcherController extends Controller
{
    private function caps(): array
    {
        // IMPORTANT:
        // Use config() only (NOT env()) so it respects config caching.
        // Put these in config/twins.php and override via .env there.

        $multiEnabled = (bool) config('twins.multi_company_enabled', true);

        // Per-user cap (0 = unlimited)
        $maxPerUser = (int) config('twins.max_companies_per_user', 1);

        // Whole-app cap (0 = unlimited)
        $maxInApp = (int) config('twins.max_companies_app', 0);

        return [$multiEnabled, $maxPerUser, $maxInApp];
    }

    public function index()
    {
        $user = auth()->user();

        [$multiEnabled, $maxPerUser, $maxInApp] = $this->caps();

        $companies = method_exists($user, 'companies')
            ? $user->companies()->orderBy('name')->get()
            : collect();

        $companyCount = (int) $companies->count();
        $appCount     = (int) Company::query()->count();

        $roleSlug = $user?->role?->slug;
        $isOwner  = ($roleSlug === 'owner');

        $underUserCap = ($maxPerUser === 0) ? true : ($companyCount < $maxPerUser);
        $underAppCap  = ($maxInApp === 0)   ? true : ($appCount < $maxInApp);

        $canCreateCompany = $multiEnabled && $isOwner && $underUserCap && $underAppCap;

        return view('companies.switcher', [
            'companies'        => $companies,
            'activeId'         => (int) ($user->active_company_id ?? 0),

            'multiEnabled'     => $multiEnabled,
            'maxPerUser'       => $maxPerUser,
            'maxInApp'         => $maxInApp,

            'companyCount'     => $companyCount,
            'appCount'         => $appCount,

            'underUserCap'     => $underUserCap,
            'underAppCap'      => $underAppCap,

            'canCreateCompany' => $canCreateCompany,
            'isOwner'          => $isOwner,
        ]);
    }

    public function switch(Company $company)
    {
        $user = auth()->user();

        if (!method_exists($user, 'companies') || !$user->companies()->whereKey($company->id)->exists()) {
            abort(403);
        }

        $user->active_company_id = $company->id;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Company switched.');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        [$multiEnabled, $maxPerUser, $maxInApp] = $this->caps();

        if (!$multiEnabled) {
            return back()->with('error', 'Multi-company is disabled.');
        }

        if (($user?->role?->slug ?? null) !== 'owner') {
            return back()->with('error', 'Only owners can create companies.');
        }

        $companyCount = method_exists($user, 'companies') ? (int) $user->companies()->count() : 0;
        $appCount     = (int) Company::query()->count();

        if ($maxPerUser !== 0 && $companyCount >= $maxPerUser) {
            return back()->with('error', "Limit reached. Max {$maxPerUser} companies for your account.");
        }

        if ($maxInApp !== 0 && $appCount >= $maxInApp) {
            return back()->with('error', "App limit reached. Max {$maxInApp} companies in this system.");
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($user, $data) {
            // slug is required: generate unique
            $base = Str::slug($data['name']);
            $slug = $base ?: ('company-' . Str::random(6));

            $i = 2;
            while (Company::query()->where('slug', $slug)->exists()) {
                $slug = ($base ?: 'company') . '-' . $i;
                $i++;
            }

            $company = Company::create([
                'name' => $data['name'],
                'slug' => $slug,
            ]);

            // Create CROSS DOCK depot
            $this->ensureCrossDockDepot($company->id, auth()->id());

            if (method_exists($user, 'companies')) {
                $user->companies()->syncWithoutDetaching([$company->id]);
            }

            $user->active_company_id = $company->id;
            $user->save();
        });

        return redirect()->route('companies.switcher')->with('status', 'Company created.');
    }


private function ensureCrossDockDepot(int $companyId, ?int $userId = null): void
{
    Depot::query()->firstOrCreate(
        [
            'company_id' => $companyId,
            'name'       => 'CROSS DOCK',
        ],
        [
            'is_active'  => true,          // adjust if your column is `active`
            'is_system'  => true,          // only if you added the migration
            'created_by' => $userId,       // only if depots has created_by
        ]
    );
}

}