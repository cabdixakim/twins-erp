<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transporter;
use App\Models\TransporterLedgerEntry;
use App\Models\ImportTruck;
use App\Models\ImportNomination;
use App\Models\PettyCashAccount;
use App\Models\PettyCashTransaction;
use App\Models\Sale;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransporterLedgerController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $transporters = Transporter::where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $balances = TransporterLedgerEntry::where('company_id', $cid)
            ->selectRaw('transporter_id, SUM(amount) as balance')
            ->groupBy('transporter_id')
            ->pluck('balance', 'transporter_id');

        $freightTotals = TransporterLedgerEntry::where('company_id', $cid)
            ->where('type', 'freight_charge')
            ->selectRaw('transporter_id, SUM(amount) as total')
            ->groupBy('transporter_id')
            ->pluck('total', 'transporter_id');

        return view('transporters.index', compact('transporters', 'balances', 'freightTotals'));
    }

    public function show(Transporter $transporter)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $transporter->company_id !== $cid, 403);

        $entries = TransporterLedgerEntry::where('company_id', $cid)
            ->where('transporter_id', $transporter->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(50);

        $breakdown = TransporterLedgerEntry::where('company_id', $cid)
            ->where('transporter_id', $transporter->id)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $freightTotal     = (float) ($breakdown['freight_charge'] ?? 0);
        $advanceTotal     = abs((float) ($breakdown['advance'] ?? 0));
        $shortChargeTotal = abs((float) ($breakdown['short_charge'] ?? 0));
        $paymentTotal     = abs((float) ($breakdown['payment'] ?? 0));
        $netPayable       = (float) $breakdown->sum();

        $currency = $transporter->default_currency ?: 'USD';

        // Build per-trip summary: group freight_charge entries by sale_id
        // Then find advances linked to the same sale_id
        $tripSaleIds = TransporterLedgerEntry::where('company_id', $cid)
            ->where('transporter_id', $transporter->id)
            ->where('type', 'freight_charge')
            ->whereNotNull('ref_id')
            ->where('ref_type', Sale::class)
            ->pluck('ref_id')
            ->unique();

        $tripSales = Sale::whereIn('id', $tripSaleIds)
            ->with(['product', 'depot'])
            ->orderByDesc('sale_date')
            ->get()
            ->keyBy('id');

        // Load all entries for trip sales (freight + advances)
        $tripEntries = TransporterLedgerEntry::where('company_id', $cid)
            ->where('transporter_id', $transporter->id)
            ->where(function ($q) use ($tripSaleIds) {
                $q->whereIn('sale_id', $tripSaleIds)
                  ->orWhere(function ($q2) use ($tripSaleIds) {
                      $q2->where('type', 'freight_charge')
                         ->where('ref_type', Sale::class)
                         ->whereIn('ref_id', $tripSaleIds);
                  });
            })
            ->get();

        // Build trip rows
        $trips = [];
        foreach ($tripSaleIds as $saleId) {
            $sale    = $tripSales[$saleId] ?? null;
            $freight = $tripEntries
                ->where('type', 'freight_charge')
                ->where('ref_type', Sale::class)
                ->where('ref_id', $saleId)
                ->sum('amount');
            $tripAdvances = $tripEntries
                ->where('sale_id', $saleId)
                ->where('type', 'advance');
            $advancesTotal = abs($tripAdvances->sum('amount'));
            $trips[$saleId] = [
                'sale'           => $sale,
                'freight'        => (float) $freight,
                'advances'       => $tripAdvances->values(),
                'advances_total' => $advancesTotal,
                'net'            => (float) $freight - $advancesTotal,
            ];
        }
        // Sort trips by sale date desc
        uasort($trips, fn($a, $b) =>
            ($b['sale']?->sale_date ?? now()) <=> ($a['sale']?->sale_date ?? now())
        );

        // Build clickable reference links for ledger tab
        $allEntries = $entries->getCollection();

        $truckIds = $allEntries
            ->where('ref_type', ImportTruck::class)
            ->pluck('ref_id')
            ->unique();

        $nominationIds = $allEntries
            ->where('ref_type', ImportNomination::class)
            ->pluck('ref_id')
            ->unique();

        $truckPurchaseIds = $truckIds->isNotEmpty()
            ? DB::table('import_trucks')
                ->join('import_nominations', 'import_trucks.nomination_id', '=', 'import_nominations.id')
                ->whereIn('import_trucks.id', $truckIds)
                ->pluck('import_nominations.purchase_id', 'import_trucks.id')
            : collect();

        $nominationPurchaseIds = $nominationIds->isNotEmpty()
            ? DB::table('import_nominations')
                ->whereIn('id', $nominationIds)
                ->pluck('purchase_id', 'id')
            : collect();

        $refLinks = [];
        foreach ($truckIds as $tid) {
            if (isset($truckPurchaseIds[$tid])) {
                $refLinks[ImportTruck::class . ':' . $tid] = route('purchases.show', $truckPurchaseIds[$tid]);
            }
        }
        foreach ($nominationIds as $nid) {
            if (isset($nominationPurchaseIds[$nid])) {
                $refLinks[ImportNomination::class . ':' . $nid] = route('purchases.show', $nominationPurchaseIds[$nid]);
            }
        }

        $saleEntries = $allEntries->where('ref_type', Sale::class);
        foreach ($saleEntries as $e) {
            $key = Sale::class . ':' . $e->ref_id;
            if (!isset($refLinks[$key])) {
                $refLinks[$key] = route('sales.index', ['sale' => $e->ref_id]);
            }
        }

        // Load open/posted sales for this transporter (for advance trip selector)
        $openSales = Sale::where('company_id', $cid)
            ->where('transporter_id', $transporter->id)
            ->whereIn('status', ['draft', 'posted'])
            ->with(['product', 'depot'])
            ->orderByDesc('sale_date')
            ->limit(50)
            ->get();

        $pettyCashAccounts = PettyCashAccount::where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('transporters.show', compact(
            'transporter', 'entries', 'refLinks',
            'freightTotal', 'advanceTotal', 'shortChargeTotal', 'paymentTotal',
            'netPayable', 'currency', 'pettyCashAccounts',
            'trips', 'openSales'
        ));
    }

    public function recordPayment(Request $request, Transporter $transporter)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $transporter->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'                => 'required|numeric|min:0.01',
            'entry_date'            => 'required|date',
            'description'           => 'nullable|string|max:500',
            'petty_cash_account_id' => 'nullable|integer|exists:petty_cash_accounts,id',
        ]);

        $currency = $transporter->default_currency ?: 'USD';

        DB::transaction(function () use ($cid, $transporter, $data, $currency) {
            $entry = TransporterLedgerEntry::create([
                'company_id'     => $cid,
                'transporter_id' => $transporter->id,
                'type'           => 'payment',
                'amount'         => -(float) $data['amount'],
                'currency'       => $currency,
                'description'    => $data['description'] ?: 'Payment to transporter',
                'entry_date'     => $data['entry_date'],
                'created_by'     => auth()->id(),
            ]);

            if (!empty($data['petty_cash_account_id'])) {
                $account = PettyCashAccount::where('company_id', $cid)
                    ->where('id', (int) $data['petty_cash_account_id'])
                    ->firstOrFail();

                PettyCashTransaction::create([
                    'company_id'       => $cid,
                    'account_id'       => $account->id,
                    'type'             => 'transporter_advance',
                    'amount'           => -(float) $data['amount'],
                    'currency'         => $currency,
                    'description'      => 'Transporter payment — ' . $transporter->name
                                         . ($data['description'] ? ' · ' . $data['description'] : ''),
                    'transaction_date' => $data['entry_date'],
                    'ref_type'         => TransporterLedgerEntry::class,
                    'ref_id'           => $entry->id,
                    'created_by'       => auth()->id(),
                ]);
            }
        });

        $sym = self::currencySymbol($currency);
        return redirect()->route('transporters.show', $transporter)
            ->with('status', 'Payment of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function recordAdvance(Request $request, Transporter $transporter)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $transporter->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'                => 'required|numeric|min:0.01',
            'entry_date'            => 'required|date',
            'advance_type'          => 'required|in:trip,fuel,driver,general,other',
            'sale_id'               => 'nullable|integer|exists:sales,id',
            'description'           => 'nullable|string|max:500',
            'petty_cash_account_id' => 'nullable|integer|exists:petty_cash_accounts,id',
        ]);

        // Validate sale belongs to this company + transporter
        $sale = null;
        if (!empty($data['sale_id'])) {
            $sale = Sale::where('company_id', $cid)
                ->where('transporter_id', $transporter->id)
                ->where('id', (int) $data['sale_id'])
                ->first();
            if (!$sale) {
                return back()->withErrors(['sale_id' => 'Invalid trip selected.'])->withInput();
            }
        }

        $currency = $transporter->default_currency ?: 'USD';

        $advanceLabels = [
            'trip'    => 'Trip advance',
            'fuel'    => 'Fuel advance',
            'driver'  => 'Driver advance',
            'general' => 'General advance',
            'other'   => 'Advance',
        ];
        $label = $advanceLabels[$data['advance_type']] ?? 'Advance';

        $defaultDesc = $label . ' — ' . $transporter->name
            . ($sale ? ' · Sale ' . ($sale->reference ?: '#' . $sale->id) : '');

        DB::transaction(function () use ($cid, $transporter, $data, $currency, $sale, $defaultDesc) {
            $entry = TransporterLedgerEntry::create([
                'company_id'     => $cid,
                'transporter_id' => $transporter->id,
                'type'           => 'advance',
                'advance_type'   => $data['advance_type'],
                'sale_id'        => $sale?->id,
                'amount'         => -(float) $data['amount'],
                'currency'       => $currency,
                'description'    => $data['description'] ?: $defaultDesc,
                'entry_date'     => $data['entry_date'],
                'created_by'     => auth()->id(),
            ]);

            if (!empty($data['petty_cash_account_id'])) {
                $account = PettyCashAccount::where('company_id', $cid)
                    ->where('id', (int) $data['petty_cash_account_id'])
                    ->firstOrFail();

                PettyCashTransaction::create([
                    'company_id'       => $cid,
                    'account_id'       => $account->id,
                    'type'             => 'transporter_advance',
                    'amount'           => -(float) $data['amount'],
                    'currency'         => $currency,
                    'description'      => $entry->description,
                    'transaction_date' => $data['entry_date'],
                    'ref_type'         => TransporterLedgerEntry::class,
                    'ref_id'           => $entry->id,
                    'created_by'       => auth()->id(),
                ]);
            }
        });

        $sym = self::currencySymbol($currency);
        return redirect()->route('transporters.show', $transporter)
            ->with('status', $label . ' of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function recordAdjustment(Request $request, Transporter $transporter)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $transporter->company_id !== $cid, 403);

        $data = $request->validate([
            'direction'   => 'required|in:debit,credit',
            'amount'      => 'required|numeric|min:0.01',
            'entry_date'  => 'required|date',
            'description' => 'required|string|max:500',
        ]);

        $currency = $transporter->default_currency ?: 'USD';
        $signed   = $data['direction'] === 'debit'
                    ? (float) $data['amount']
                    : -(float) $data['amount'];

        TransporterLedgerEntry::create([
            'company_id'     => $cid,
            'transporter_id' => $transporter->id,
            'type'           => 'adjustment',
            'amount'         => $signed,
            'currency'       => $currency,
            'description'    => $data['description'],
            'entry_date'     => $data['entry_date'],
            'created_by'     => auth()->id(),
        ]);

        $sym = self::currencySymbol($currency);
        return redirect()->route('transporters.show', $transporter)
            ->with('status', 'Adjustment of ' . $sym . number_format($data['amount'], 2) . ' (' . $data['direction'] . ') recorded.');
    }

    public function statement(Transporter $transporter)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $transporter->company_id !== $cid, 403);

        $company = DB::table('companies')->where('id', $cid)->first();

        $entries = TransporterLedgerEntry::where('company_id', $cid)
            ->where('transporter_id', $transporter->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running = 0;
        foreach ($entries as $e) {
            $running += (float) $e->amount;
            $e->running_balance = $running;
        }

        $freightTotal     = (float) $entries->where('type', 'freight_charge')->sum('amount');
        $advanceTotal     = abs((float) $entries->where('type', 'advance')->sum('amount'));
        $shortChargeTotal = abs((float) $entries->where('type', 'short_charge')->sum('amount'));
        $paymentTotal     = abs((float) $entries->where('type', 'payment')->sum('amount'));
        $netPayable       = (float) $entries->sum('amount');
        $currency         = $transporter->default_currency ?: 'USD';

        return view('transporters.statement', compact(
            'transporter', 'company', 'entries',
            'freightTotal', 'advanceTotal', 'shortChargeTotal', 'paymentTotal',
            'netPayable', 'currency'
        ));
    }

    public function exportCsv(Transporter $transporter)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $transporter->company_id !== $cid, 403);

        $entries = TransporterLedgerEntry::where('company_id', $cid)
            ->where('transporter_id', $transporter->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $running  = 0;
        $filename = 'transporter-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($transporter->name)) . '-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($entries, &$running) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Advance Type', 'Trip (Sale Ref)', 'Description', 'Debit', 'Credit', 'Running Balance', 'Currency']);
            foreach ($entries as $e) {
                $running += (float) $e->amount;
                $isDebit  = $e->amount > 0;
                fputcsv($out, [
                    $e->entry_date->format('Y-m-d'),
                    $e->type,
                    $e->advance_type ?? '',
                    $e->sale_id ? ('Sale #' . $e->sale_id) : '',
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
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'ZAR' => 'R ',
            'CDF' => 'FC ',
            'ZMW' => 'K ',
            'ZWL' => 'ZWL ',
            default => $code . ' ',
        };
    }
}
