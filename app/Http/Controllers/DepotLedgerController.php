<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Depot;
use App\Models\DepotLedgerEntry;

class DepotLedgerController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $depots = Depot::where('company_id', $cid)
            ->where('is_active', true)
            ->where(function ($q) { $q->whereNull('is_system')->orWhere('is_system', false); })
            ->orderBy('name')
            ->get();

        $balances = DepotLedgerEntry::where('company_id', $cid)
            ->selectRaw('depot_id, currency, SUM(amount) as balance')
            ->groupBy('depot_id', 'currency')
            ->get()
            ->groupBy('depot_id')
            ->map(fn($rows) => $rows->pluck('balance', 'currency'));

        $chargeTotals = DepotLedgerEntry::where('company_id', $cid)
            ->whereIn('type', ['storage_charge', 'throughput_charge', 'loading_fee', 'other_charge'])
            ->selectRaw('depot_id, SUM(amount) as total')
            ->groupBy('depot_id')
            ->pluck('total', 'depot_id');

        return view('depots.index', compact('depots', 'balances', 'chargeTotals'));
    }

    public function show(Depot $depot)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);
        abort_if($depot->is_system, 404);

        $entries = DepotLedgerEntry::where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30);

        $breakdown = DepotLedgerEntry::where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $chargeTypes   = ['storage_charge', 'throughput_charge', 'loading_fee', 'other_charge'];
        $chargesTotal  = (float) $breakdown->only($chargeTypes)->sum();
        $paymentTotal  = abs((float) ($breakdown['payment'] ?? 0));
        $netPayable    = (float) $breakdown->sum();
        $currency      = $depot->default_currency ?: 'USD';

        $chargeConfigs = \App\Models\DepotChargeConfig::where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('category')
            ->orderBy('effective_from')
            ->get();

        // For paid_by = 'depot', we resolve the depot name from its own record
        // (paid_by_id references a depot, transporter, etc.)

        return view('depots.show', compact(
            'depot', 'entries',
            'chargesTotal', 'paymentTotal', 'netPayable', 'currency',
            'chargeConfigs'
        ));
    }

    public function runMonthlyStorage(Request $request, Depot $depot)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);
        abort_if($depot->is_system, 404);

        $data = $request->validate([
            'year'  => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $posted = \App\Services\DepotStorageAccrual::postForDepot(
            $depot,
            (int) $data['year'],
            (int) $data['month'],
            $cid,
            (int) auth()->id()
        );

        $period = sprintf('%04d-%02d', $data['year'], $data['month']);

        if (empty($posted)) {
            return redirect()->route('depots.show', $depot)
                ->with('status', "Monthly storage for {$period}: nothing new to post (already done, no stock, or no configs).");
        }

        return redirect()->route('depots.show', $depot)
            ->with('status', "Storage posted for {$period}: " . implode('; ', $posted));
    }

    public function previewMonthlyStorage(Request $request, Depot $depot)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);

        $year  = (int) ($request->query('year', now()->year));
        $month = (int) ($request->query('month', now()->month));

        $preview = \App\Services\DepotStorageAccrual::preview($depot, $year, $month, $cid);

        return response()->json($preview);
    }

    public function recordCharge(Request $request, Depot $depot)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);

        $data = $request->validate([
            'type'        => 'required|in:storage_charge,throughput_charge,loading_fee,other_charge',
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'nullable|string|max:8',
            'entry_date'  => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $currency = $data['currency'] ?: ($depot->default_currency ?: 'USD');

        DepotLedgerEntry::create([
            'company_id'  => $cid,
            'depot_id'    => $depot->id,
            'type'        => $data['type'],
            'amount'      => (float) $data['amount'],
            'currency'    => $currency,
            'description' => $data['description'] ?: ucfirst(str_replace('_', ' ', $data['type'])),
            'entry_date'  => $data['entry_date'],
            'created_by'  => auth()->id(),
        ]);

        $sym = SupplierLedgerController::currencySymbol($currency);
        return redirect()->route('depots.show', $depot)
            ->with('status', 'Charge of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function recordPayment(Request $request, Depot $depot)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'nullable|string|max:8',
            'entry_date'  => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $currency = $data['currency'] ?: ($depot->default_currency ?: 'USD');

        DepotLedgerEntry::create([
            'company_id'  => $cid,
            'depot_id'    => $depot->id,
            'type'        => 'payment',
            'amount'      => -(float) $data['amount'],
            'currency'    => $currency,
            'description' => $data['description'] ?: 'Payment to depot',
            'entry_date'  => $data['entry_date'],
            'created_by'  => auth()->id(),
        ]);

        $sym = SupplierLedgerController::currencySymbol($currency);
        return redirect()->route('depots.show', $depot)
            ->with('status', 'Payment of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function statement(Depot $depot)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);

        $company = DB::table('companies')->where('id', $cid)->first();

        $entries = DepotLedgerEntry::where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running = 0;
        foreach ($entries as $e) {
            $running += (float) $e->amount;
            $e->running_balance = $running;
        }

        $chargeTypes  = ['storage_charge', 'throughput_charge', 'loading_fee', 'other_charge'];
        $chargesTotal = (float) $entries->whereIn('type', $chargeTypes)->sum('amount');
        $paymentTotal = abs((float) $entries->where('type', 'payment')->sum('amount'));
        $netPayable   = (float) $entries->sum('amount');
        $currency     = $depot->default_currency ?: 'USD';

        return view('depots.statement', compact(
            'depot', 'company', 'entries',
            'chargesTotal', 'paymentTotal', 'netPayable', 'currency'
        ));
    }

    public function exportCsv(Depot $depot)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);

        $entries  = DepotLedgerEntry::where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->orderBy('entry_date')->orderBy('id')->get();

        $running  = 0;
        $filename = 'depot-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($depot->name)) . '-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($entries, &$running) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Description', 'Charge', 'Payment', 'Running Balance', 'Currency']);
            foreach ($entries as $e) {
                $running += (float) $e->amount;
                $isCharge = $e->amount > 0;
                fputcsv($out, [
                    $e->entry_date->format('Y-m-d'),
                    $e->type,
                    $e->description,
                    $isCharge  ? number_format((float) $e->amount,      2, '.', '') : '',
                    !$isCharge ? number_format(abs((float) $e->amount), 2, '.', '') : '',
                    number_format($running, 2, '.', ''),
                    $e->currency,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
