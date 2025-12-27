<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Transporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransporterController extends Controller
{
    protected function activeCompanyId(): int
    {
        return (int) (auth()->user()?->active_company_id ?? 0);
    }

    protected function abortIfWrongCompany(Transporter $transporter): void
    {
        $companyId = $this->activeCompanyId();

        if (!$companyId || (int) $transporter->company_id !== $companyId) {
            abort(404);
        }
    }

    public function index(Request $request): View
    {
        $companyId = $this->activeCompanyId();

        $transporters = Transporter::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $currentTransporter = null;

        if ($transporters->isNotEmpty()) {
            $currentTransporter = $transporters->first();

            if ($request->filled('transporter')) {
                $selected = $transporters->firstWhere('id', (int) $request->input('transporter'));
                if ($selected) {
                    $currentTransporter = $selected;
                }
            }
        }

        return view('settings.transporters.index', [
            'transporters'       => $transporters,
            'currentTransporter' => $currentTransporter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->activeCompanyId();

        $data = $this->validateData($request);

        // Enforce company scope (do NOT trust client input)
        $data['company_id'] = $companyId;

        $data['is_active'] = $request->boolean('is_active', true);
        $data['default_currency'] = $data['default_currency'] ?? 'USD';

        $transporter = Transporter::create($data);

        return redirect()
            ->route('settings.transporters.index', ['transporter' => $transporter->id])
            ->with('status', 'Transporter created.');
    }

    public function update(Request $request, Transporter $transporter): RedirectResponse
    {
        $this->abortIfWrongCompany($transporter);

        $data = $this->validateData($request);

        unset($data['company_id']);

        $data['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : $transporter->is_active;

        $data['default_currency'] = $data['default_currency'] ?? $transporter->default_currency ?? 'USD';

        $transporter->update($data);

        return redirect()
            ->route('settings.transporters.index', ['transporter' => $transporter->id])
            ->with('status', 'Transporter updated.');
    }

    public function toggleActive(Transporter $transporter): RedirectResponse
    {
        $this->abortIfWrongCompany($transporter);

        $transporter->is_active = ! $transporter->is_active;
        $transporter->save();

        return redirect()
            ->route('settings.transporters.index', ['transporter' => $transporter->id])
            ->with('status', $transporter->is_active ? 'Transporter re-activated.' : 'Transporter deactivated.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'type'                 => ['nullable', 'string', 'max:50'],
            'country'              => ['nullable', 'string', 'max:100'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'contact_person'       => ['nullable', 'string', 'max:150'],
            'email'                => ['nullable', 'email', 'max:150'],
            'phone'                => ['nullable', 'string', 'max:50'],
            'default_rate_per_1000_l' => ['nullable', 'numeric', 'min:0'],
            'default_currency'     => ['nullable', 'string', 'max:3'],
            'notes'                => ['nullable', 'string'],
            'is_active'            => ['sometimes', 'boolean'],
        ]);

        $stringFields = [
            'name', 'type', 'country', 'city',
            'contact_person', 'email', 'phone', 'default_currency',
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim(strip_tags($data[$field]));
            }
        }

        if (isset($data['notes'])) {
            $data['notes'] = trim($data['notes']);
        }

        return $data;
    }
}