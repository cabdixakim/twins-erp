<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreDepotRequest;
use App\Http\Requests\Settings\UpdateDepotRequest;
use App\Models\Depot;
use Illuminate\Http\Request;

class DepotController extends Controller
{
    /**
     * Get active company id from the authenticated user.
     */
    protected function activeCompanyId(): int
    {
        return (int) (auth()->user()?->active_company_id ?? 0);
    }

    /**
     * Ensure the model belongs to the active company (multi-company safety).
     */
    protected function abortIfWrongCompany(Depot $depot): void
    {
        $companyId = $this->activeCompanyId();

        if (!$companyId || (int) $depot->company_id !== $companyId) {
            abort(404);
        }
    }

    /**
     * Show depots list + selected depot details.
     */
    public function index(Request $request)
    {
        $companyId = $this->activeCompanyId();

        // company-scoped list
        $depots = Depot::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        // Determine which depot is "current"
        $currentDepotId = (int) $request->query('depot', 0);
        $currentDepot   = null;

        if ($currentDepotId) {
            $currentDepot = $depots->firstWhere('id', $currentDepotId);
        }

        if (!$currentDepot && $depots->isNotEmpty()) {
            // Prefer first active depot; otherwise just first one
            $currentDepot = $depots->firstWhere('is_active', true) ?: $depots->first();
        }

        return view('settings.depots.index', [
            'depots'       => $depots,
            'currentDepot' => $currentDepot,
        ]);
    }

    /**
     * Store a new depot.
     */
    public function store(StoreDepotRequest $request)
    {
        $companyId = $this->activeCompanyId();

        $data = $request->validated();

        // Enforce company scope (do NOT trust client input)
        $data['company_id'] = $companyId;

        // New depots are active by default unless explicitly unchecked
        $data['is_active'] = $request->boolean('is_active', true);

        Depot::create($data);

        return redirect()
            ->route('settings.depots.index')
            ->with('status', 'Depot created.');
    }

    /**
     * Update an existing depot.
     */
    public function update(UpdateDepotRequest $request, Depot $depot)
    {
        $this->abortIfWrongCompany($depot);

        $data = $request->validated();

        // Never allow company_id to be changed from requests
        unset($data['company_id']);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $depot->update($data);

        return redirect()
            ->route('settings.depots.index', ['depot' => $depot->id])
            ->with('status', 'Depot updated.');
    }

    /**
     * Toggle active / inactive for a depot (soft "archive").
     */
    public function toggleActive(Request $request, Depot $depot)
    {
        $this->abortIfWrongCompany($depot);

        $depot->is_active = ! $depot->is_active;
        $depot->save();

        if ($request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'is_active' => $depot->is_active,
            ]);
        }

        return redirect()
            ->route('settings.depots.index', ['depot' => $depot->id])
            ->with('status', $depot->is_active ? 'Depot re-activated.' : 'Depot deactivated.');
    }
}