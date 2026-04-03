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

        $clients = $query->orderBy('name')->paginate(30)->withQueryString();

        return view('clients.index', compact('clients', 'q', 'status', 'type'));
    }

    public function create()
    {
        return view('clients.create', ['client' => null]);
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
            return back()->withErrors(['name' => 'A client with this name already exists.'])->withInput();
        }

        Client::create(array_merge($data, [
            'company_id'   => $cid,
            'currency'     => strtoupper($data['currency'] ?? 'USD'),
            'credit_limit' => $data['credit_limit'] ?? 0,
            'is_active'    => true,
        ]));

        return redirect()->route('clients.index')
            ->with('status', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load(['purchases' => function ($q) {
            $q->whereIn('status', ['dispatched'])->latest()->limit(20);
        }]);

        $dispatchCount = $client->purchases()->where('status', 'dispatched')->count();

        return view('clients.show', compact('client', 'dispatchCount'));
    }

    public function edit(Client $client)
    {
        return view('clients.create', compact('client'));
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
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $exists = Client::query()
            ->where('company_id', $cid)
            ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->where('id', '!=', $client->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Another client with this name already exists.'])->withInput();
        }

        $client->update(array_merge($data, [
            'currency'     => strtoupper($data['currency'] ?? $client->currency ?? 'USD'),
            'credit_limit' => $data['credit_limit'] ?? $client->credit_limit,
            'is_active'    => $request->boolean('is_active', $client->is_active),
        ]));

        return redirect()->route('clients.show', $client)
            ->with('status', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $hasPurchases = $client->purchases()->exists();
        if ($hasPurchases) {
            return back()->with('error', 'Cannot delete a client that has dispatches attached.');
        }

        $client->delete();

        return redirect()->route('clients.index')
            ->with('status', 'Client deleted.');
    }
}
