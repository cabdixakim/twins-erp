<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $q      = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $type   = trim((string) $request->query('type', ''));

        $query = Client::query()->where('company_id', $cid);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', '%' . $q . '%')
                   ->orWhere('code', 'like', '%' . $q . '%')
                   ->orWhere('city', 'like', '%' . $q . '%')
                   ->orWhere('contact_person', 'like', '%' . $q . '%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        $clients = $query->orderBy('name')->get();

        $clientId      = (int) $request->query('client', 0);
        $currentClient = $clientId
            ? Client::where('company_id', $cid)->find($clientId)
            : null;

        if (!$currentClient && $clients->isNotEmpty()) {
            $currentClient = $clients->first();
        }

        return view('settings.clients.index', compact('clients', 'currentClient', 'q', 'status', 'type'));
    }

    public function store(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $data = $request->validate([
            'name'           => 'required|string|max:120',
            'code'           => 'nullable|string|max:50',
            'type'           => 'nullable|string|max:50',
            'country'        => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:150',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:150',
            'currency'       => 'nullable|string|size:3',
            'credit_limit'   => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $exists = Client::query()
            ->where('company_id', $cid)
            ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->exists();

        if ($exists) {
            return back()->with('error', 'A client with this name already exists.');
        }

        $client = Client::create(array_merge($data, [
            'company_id'   => $cid,
            'currency'     => strtoupper($data['currency'] ?? 'USD'),
            'credit_limit' => $data['credit_limit'] ?? 0,
            'is_active'    => true,
        ]));

        return redirect()->route('settings.clients.index', ['client' => $client->id])
            ->with('status', 'Client "' . $client->name . '" created.');
    }

    public function update(Request $request, Client $client)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $data = $request->validate([
            'name'           => 'required|string|max:120',
            'code'           => 'nullable|string|max:50',
            'type'           => 'nullable|string|max:50',
            'country'        => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'contact_person' => 'nullable|string|max:150',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:150',
            'currency'       => 'nullable|string|size:3',
            'credit_limit'   => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $exists = Client::query()
            ->where('company_id', $cid)
            ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->where('id', '!=', $client->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Another client with this name already exists.');
        }

        $client->update(array_merge($data, [
            'currency'     => strtoupper($data['currency'] ?? $client->currency ?? 'USD'),
            'credit_limit' => $data['credit_limit'] ?? $client->credit_limit,
        ]));

        return redirect()->route('settings.clients.index', ['client' => $client->id])
            ->with('status', 'Client updated.');
    }

    public function toggleActive(Request $request, Client $client)
    {
        $newState = !$client->is_active;
        $client->update(['is_active' => $newState]);

        return redirect()->route('settings.clients.index', ['client' => $client->id])
            ->with('status', $newState ? 'Client activated.' : 'Client deactivated.');
    }
}
