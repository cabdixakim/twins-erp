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

    public function exportCsv()
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $rows     = Client::where('company_id', $cid)->orderBy('name')->get();
        $filename = 'clients-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Code', 'Type', 'Country', 'City', 'Contact Person', 'Phone', 'Email', 'Currency', 'Credit Limit', 'Status', 'Notes']);
            foreach ($rows as $c) {
                fputcsv($out, [
                    $c->name,
                    $c->code ?? '',
                    $c->type ?? '',
                    $c->country ?? '',
                    $c->city ?? '',
                    $c->contact_person ?? '',
                    $c->phone ?? '',
                    $c->email ?? '',
                    $c->currency ?? '',
                    $c->credit_limit ? number_format((float) $c->credit_limit, 2, '.', '') : '',
                    $c->is_active ? 'Active' : 'Inactive',
                    $c->notes ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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

        // Post opening balance to client AR ledger if provided
        // Positive = client owes us (receivable)
        $openingBalance = (float) $request->input('opening_balance', 0);
        if ($openingBalance > 0) {
            $openingDate = $request->input('opening_balance_date') ?: now()->format('Y-m-d');
            \App\Models\ClientLedgerEntry::create([
                'company_id'  => $cid,
                'client_id'   => $client->id,
                'type'        => 'adjustment',
                'amount'      => $openingBalance,
                'currency'    => $client->currency ?: 'USD',
                'description' => 'Opening balance',
                'entry_date'  => $openingDate,
                'ref_type'    => null,
                'ref_id'      => null,
                'created_by'  => $u?->id,
            ]);
        }

        \App\Models\AuditLog::record('created', "Client '{$client->name}' created.", $client, "Client {$client->name}", severity: 'info', module: 'Client');

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

        \App\Models\AuditLog::record('updated', "Client '{$client->name}' updated.", $client, "Client {$client->name}", severity: 'info', module: 'Client');

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
