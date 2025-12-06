<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Transporter;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class TransporterController extends Controller
{
    /**
     * List all transporters, optionally show selected transporter’s details.
     */
    public function index(Request $request)
    {
        $transporters   = Transporter::orderBy('name')->get();
        $currentId      = $request->query('transporter');
        $current        = $currentId ? Transporter::find($currentId) : null;

        return view('settings.transporters.index', [
            'transporters'    => $transporters,
            'currentTransporter' => $current,
        ]);
    }

    /**
     * Create a new transporter.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        // Create transporter
        $transporter = Transporter::create($data);

        return redirect()
            ->route('settings.transporters.index', ['transporter' => $transporter->id])
            ->with('status', 'Transporter created.');
    }

    /**
     * Update transporter.
     */
    public function update(Request $request, Transporter $transporter): RedirectResponse
    {
        $data = $this->validateData($request);

        $transporter->update($data);

        return redirect()
            ->route('settings.transporters.index', ['transporter' => $transporter->id])
            ->with('status', 'Transporter updated.');
    }

    /**
     * Activate / deactivate transporter.
     */
    public function toggleActive(Transporter $transporter): RedirectResponse
    {
        $transporter->is_active = !$transporter->is_active;
        $transporter->save();

        return redirect()
            ->route('settings.transporters.index', ['transporter' => $transporter->id])
            ->with(
                'status',
                $transporter->is_active ? 'Transporter re-activated.' : 'Transporter deactivated.'
            );
    }

    /**
     * Shared validation + normalisation logic.
     */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:255'],
            'type'                     => ['nullable', 'string', 'max:255'],
            'country'                  => ['nullable', 'string', 'max:255'],
            'city'                     => ['nullable', 'string', 'max:255'],
            'contact_person'           => ['nullable', 'string', 'max:255'],
            'email'                    => ['nullable', 'email', 'max:255'],
            'phone'                    => ['nullable', 'string', 'max:255'],

            // DECIMALS
            'default_rate_per_1000_l'     => ['nullable', 'numeric'],
            'default_short_allowance_pct' => ['nullable', 'numeric'],

            'default_currency'        => ['nullable', 'string', 'max:10'],
            'notes'                   => ['nullable', 'string'],
            'is_active'               => ['sometimes', 'boolean'],
        ]);

        // --- Normalise decimals to avoid MySQL "" error ---
        foreach (['default_rate_per_1000_l', 'default_short_allowance_pct'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $data[$field] = 0;
            }
        }

        // Boolean clean-up
        $data['is_active'] = $request->boolean('is_active', false);

        // Default currency
        $data['default_currency'] = $data['default_currency'] ?? 'USD';

        return $data;
    }
}