<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::orderBy('name')->get();

        $currentSupplier = null;
        if ($suppliers->count() > 0) {
            $currentSupplier = $suppliers->first();

            if ($request->filled('supplier')) {
                $selected = $suppliers->firstWhere('id', (int) $request->input('supplier'));
                if ($selected) {
                    $currentSupplier = $selected;
                }
            }
        }

        return view('settings.suppliers.index', [
            'suppliers'       => $suppliers,
            'currentSupplier' => $currentSupplier,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        // On create, default active = true unless explicitly turned off
        $data['is_active'] = $request->boolean('is_active', true);
        $data['default_currency'] = $data['default_currency'] ?? 'USD';

        $supplier = Supplier::create($data);

        return redirect()
            ->route('settings.suppliers.index', ['supplier' => $supplier->id])
            ->with('status', 'Supplier created.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->validateData($request);

        // If checkbox present, honour it. If missing (e.g. old form), keep current state.
        $data['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : $supplier->is_active;

        $data['default_currency'] = $data['default_currency'] ?? $supplier->default_currency ?? 'USD';

        $supplier->update($data);

        return redirect()
            ->route('settings.suppliers.index', ['supplier' => $supplier->id])
            ->with('status', 'Supplier updated.');
    }

    public function toggleActive(Supplier $supplier): RedirectResponse
    {
        $supplier->is_active = ! $supplier->is_active;
        $supplier->save();

        return redirect()
            ->route('settings.suppliers.index', ['supplier' => $supplier->id])
            ->with(
                'status',
                $supplier->is_active
                    ? 'Supplier re-activated.'
                    : 'Supplier deactivated.'
            );
    }

    /**
     * Central validation + light sanitisation.
     */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'type'             => ['nullable', 'string', 'max:50'],
            'country'          => ['nullable', 'string', 'max:100'],
            'city'             => ['nullable', 'string', 'max:100'],
            'contact_person'   => ['nullable', 'string', 'max:150'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'email'            => ['nullable', 'email', 'max:150'],
            'default_currency' => ['nullable', 'string', 'max:3'],
            'notes'            => ['nullable', 'string'],
            'is_active'        => ['sometimes', 'boolean'],
        ]);

        // Basic hardening: trim + strip tags on text fields (defensive)
        $stringFields = [
            'name', 'type', 'country', 'city',
            'contact_person', 'phone', 'default_currency',
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }

        // For notes we allow more content, but at least trim whitespace
        if (isset($data['notes'])) {
            $data['notes'] = trim($data['notes']);
        }

        return $data;
    }
}