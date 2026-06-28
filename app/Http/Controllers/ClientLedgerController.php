<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientLedgerEntry;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\CashPostingService;

class ClientLedgerController extends Controller
{
    // ── Static helper — called from SalesController ──────────────────────────

    public static function postInvoice(int $clientId, int $companyId, float $amount, string $currency, string $refType, int $refId, string $description, string $date): void
    {
        $exists = ClientLedgerEntry::where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->where('type', 'invoice')
            ->exists();

        if ($exists) return;

        ClientLedgerEntry::create([
            'company_id'  => $companyId,
            'client_id'   => $clientId,
            'type'        => 'invoice',
            'amount'      => round($amount, 2),
            'currency'    => $currency,
            'description' => $description,
            'ref_type'    => $refType,
            'ref_id'      => $refId,
            'entry_date'  => $date,
            'created_by'  => auth()->id(),
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $clients = Client::where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Balance per client per currency — keyed [client_id][currency] = balance
        $balanceRows = ClientLedgerEntry::where('company_id', $cid)
            ->selectRaw('client_id, currency, SUM(amount) as balance')
            ->groupBy('client_id', 'currency')
            ->get();
        $balances = [];
        foreach ($balanceRows as $row) {
            $balances[$row->client_id][$row->currency] = (float) $row->balance;
        }

        // Invoiced total per client per currency
        $invoicedRows = ClientLedgerEntry::where('company_id', $cid)
            ->where('type', 'invoice')
            ->selectRaw('client_id, currency, SUM(amount) as total')
            ->groupBy('client_id', 'currency')
            ->get();
        $invoicedTotals = [];
        foreach ($invoicedRows as $row) {
            $invoicedTotals[$row->client_id][$row->currency] = (float) $row->total;
        }

        // Total AR per currency (only clients with a net positive balance)
        $totalARByCurrency = ClientLedgerEntry::where('company_id', $cid)
            ->selectRaw('currency, SUM(amount) as t')
            ->groupBy('currency')
            ->havingRaw('SUM(amount) > 0')
            ->pluck('t', 'currency')
            ->map(fn($v) => (float) $v);
        // Keep a single $totalAR for backward compatibility (sum of first/primary currency)
        $totalAR = $totalARByCurrency->sum();

        // AR Aging buckets — based on open invoices
        $today = now()->toDateString();
        $d30   = now()->subDays(30)->toDateString();
        $d60   = now()->subDays(60)->toDateString();
        $aging = Invoice::where('company_id', $cid)
            ->whereIn('status', ['sent', 'overdue'])
            ->selectRaw(
                "client_id,
                COALESCE(SUM(CASE WHEN due_date >= ? THEN total - paid_amount ELSE 0 END), 0) AS bucket_current,
                COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN total - paid_amount ELSE 0 END), 0) AS bucket_1_30,
                COALESCE(SUM(CASE WHEN due_date < ? AND due_date >= ? THEN total - paid_amount ELSE 0 END), 0) AS bucket_31_60,
                COALESCE(SUM(CASE WHEN due_date < ? THEN total - paid_amount ELSE 0 END), 0) AS bucket_60_plus",
                [$today, $today, $d30, $d30, $d60, $d60]
            )
            ->groupBy('client_id')
            ->get()
            ->keyBy('client_id');

        return view('clients.index', compact('clients', 'balances', 'invoicedTotals', 'totalAR', 'aging'));
    }

    public function show(Client $client)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $client->company_id !== $cid, 403);

        $entries = ClientLedgerEntry::where('company_id', $cid)
            ->where('client_id', $client->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30);

        $breakdown = ClientLedgerEntry::where('company_id', $cid)
            ->where('client_id', $client->id)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $invoicedTotal = (float) ($breakdown['invoice']      ?? 0);
        $paymentTotal  = abs((float) ($breakdown['payment']    ?? 0));
        $creditTotal   = abs((float) ($breakdown['credit_note'] ?? 0));
        $netAR         = (float) $breakdown->sum();

        $currency = $client->currency ?: 'USD';

        // Build ref links (sales)
        $saleEntries = $entries->getCollection()->where('ref_type', \App\Models\Sale::class);
        $saleIds     = $saleEntries->pluck('ref_id')->unique();
        $refLinks    = [];
        if ($saleIds->isNotEmpty()) {
            foreach ($saleIds as $sid) {
                $refLinks[\App\Models\Sale::class . ':' . $sid] = route('sales.index', ['sale' => $sid]);
            }
        }

        ['bankAccounts' => $bankAccounts, 'pettyCashAccounts' => $pettyCashAccounts]
            = CashPostingService::accountsForCompany($cid);

        return view('clients.show', compact(
            'client', 'entries', 'refLinks',
            'invoicedTotal', 'paymentTotal', 'creditTotal', 'netAR', 'currency',
            'bankAccounts', 'pettyCashAccounts'
        ));
    }

    public function recordPayment(Request $request, Client $client)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $client->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'                => 'required|numeric|min:0.01',
            'entry_date'            => 'required|date',
            'description'           => 'nullable|string|max:500',
            'bank_account_id'       => 'nullable|integer|exists:bank_accounts,id',
            'petty_cash_account_id' => 'nullable|integer|exists:petty_cash_accounts,id',
        ]);

        $currency = $client->currency ?: 'USD';
        $bankId   = !empty($data['bank_account_id'])       ? (int) $data['bank_account_id']       : null;
        $pcaId    = !empty($data['petty_cash_account_id']) ? (int) $data['petty_cash_account_id'] : null;
        $desc     = $data['description'] ?: 'Payment received from ' . $client->name;

        DB::transaction(function () use ($cid, $client, $data, $currency, $bankId, $pcaId, $desc) {
            $entry = ClientLedgerEntry::create([
                'company_id'            => $cid,
                'client_id'             => $client->id,
                'type'                  => 'payment',
                'amount'                => -(float) $data['amount'],
                'currency'              => $currency,
                'description'           => $desc,
                'entry_date'            => $data['entry_date'],
                'bank_account_id'       => $bankId,
                'petty_cash_account_id' => $pcaId,
                'created_by'            => auth()->id(),
            ]);

            if ($bankId || $pcaId) {
                $cash = CashPostingService::postReceipt(
                    companyId:           $cid,
                    amount:              (float) $data['amount'],
                    currency:            $currency,
                    date:                $data['entry_date'],
                    description:         $desc,
                    refType:             ClientLedgerEntry::class,
                    refId:               $entry->id,
                    bankAccountId:       $bankId,
                    pettyCashAccountId:  $pcaId,
                    createdBy:           auth()->id(),
                );
                $entry->update([
                    'bank_transaction_id'       => $cash['bank_transaction_id'],
                    'petty_cash_transaction_id' => $cash['petty_cash_transaction_id'],
                ]);
            }
        });

        AuditLog::record(
            'paid',
            "Payment of {$currency} " . number_format($data['amount'], 2) . " received from client {$client->name}",
            $client,
            "Client {$client->name}",
            severity: 'warning',
            after: ['amount' => $data['amount'], 'currency' => $currency, 'entry_date' => $data['entry_date']],
            module: 'Client',
        );

        $sym = self::currencySymbol($currency);
        return redirect()->route('clients.show', $client)
            ->with('status', 'Payment of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function recordCredit(Request $request, Client $client)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $client->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'entry_date'  => 'required|date',
            'description' => 'required|string|max:500',
        ]);

        $currency = $client->currency ?: 'USD';

        ClientLedgerEntry::create([
            'company_id'  => $cid,
            'client_id'   => $client->id,
            'type'        => 'credit_note',
            'amount'      => -(float) $data['amount'],
            'currency'    => $currency,
            'description' => $data['description'],
            'entry_date'  => $data['entry_date'],
            'created_by'  => auth()->id(),
        ]);

        AuditLog::record(
            'adjusted',
            "Credit note of {$currency} " . number_format($data['amount'], 2) . " issued to client {$client->name}",
            $client,
            "Client {$client->name}",
            severity: 'warning',
            after: ['amount' => $data['amount'], 'currency' => $currency, 'description' => $data['description']],
            module: 'Client',
        );

        $sym = self::currencySymbol($currency);
        return redirect()->route('clients.show', $client)
            ->with('status', 'Credit note of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function recordAdjustment(Request $request, Client $client)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $client->company_id !== $cid, 403);

        $data = $request->validate([
            'direction'   => 'required|in:debit,credit',
            'amount'      => 'required|numeric|min:0.01',
            'entry_date'  => 'required|date',
            'description' => 'required|string|max:500',
        ]);

        $currency = $client->currency ?: 'USD';
        $signed   = $data['direction'] === 'debit'
                    ? (float) $data['amount']
                    : -(float) $data['amount'];

        ClientLedgerEntry::create([
            'company_id'  => $cid,
            'client_id'   => $client->id,
            'type'        => 'adjustment',
            'amount'      => $signed,
            'currency'    => $currency,
            'description' => $data['description'],
            'entry_date'  => $data['entry_date'],
            'created_by'  => auth()->id(),
        ]);

        $sym = self::currencySymbol($currency);
        return redirect()->route('clients.show', $client)
            ->with('status', 'Adjustment of ' . $sym . number_format($data['amount'], 2) . ' (' . $data['direction'] . ') recorded.');
    }

    public function statement(Client $client)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $client->company_id !== $cid, 403);

        $company = DB::table('companies')->where('id', $cid)->first();

        $entries = ClientLedgerEntry::where('company_id', $cid)
            ->where('client_id', $client->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running = 0;
        foreach ($entries as $e) {
            $running       += (float) $e->amount;
            $e->running_balance = $running;
        }

        $invoicedTotal = (float) $entries->where('type', 'invoice')->sum('amount');
        $paymentTotal  = abs((float) $entries->where('type', 'payment')->sum('amount'));
        $creditTotal   = abs((float) $entries->where('type', 'credit_note')->sum('amount'));
        $netAR         = (float) $entries->sum('amount');
        $currency      = $client->currency ?: 'USD';

        return view('clients.statement', compact(
            'client', 'company', 'entries',
            'invoicedTotal', 'paymentTotal', 'creditTotal', 'netAR', 'currency'
        ));
    }

    public function exportCsv(Client $client)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $client->company_id !== $cid, 403);

        $entries  = ClientLedgerEntry::where('company_id', $cid)
            ->where('client_id', $client->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running  = 0;
        $filename = 'client-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($client->name)) . '-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($entries, &$running) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Description', 'Debit (AR)', 'Credit (Payment)', 'Running Balance', 'Currency']);
            foreach ($entries as $e) {
                $running   += (float) $e->amount;
                $isDebit    = $e->amount > 0;
                fputcsv($out, [
                    $e->entry_date->format('Y-m-d'),
                    $e->type,
                    $e->description,
                    $isDebit  ? number_format((float) $e->amount,        2, '.', '') : '',
                    !$isDebit ? number_format(abs((float) $e->amount), 2, '.', '') : '',
                    number_format($running, 2, '.', ''),
                    $e->currency,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public static function currencySymbol(string $code): string
    {
        return match ($code) {
            'USD' => '$', 'EUR' => '€', 'GBP' => '£',
            'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
            default => $code . ' ',
        };
    }
}
