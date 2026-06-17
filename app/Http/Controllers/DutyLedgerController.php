<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\DutyVendor;
use App\Models\DutyLedgerEntry;
use App\Models\ImportTruck;

class DutyLedgerController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $vendors = DutyVendor::where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $balances = DutyLedgerEntry::where('company_id', $cid)
            ->selectRaw('duty_vendor_id, currency, SUM(amount) as balance')
            ->groupBy('duty_vendor_id', 'currency')
            ->get()
            ->groupBy('duty_vendor_id')
            ->map(fn($rows) => $rows->pluck('balance', 'currency'));

        $chargeTotals = DutyLedgerEntry::where('company_id', $cid)
            ->where('type', 'duty_charge')
            ->selectRaw('duty_vendor_id, SUM(amount) as total')
            ->groupBy('duty_vendor_id')
            ->pluck('total', 'duty_vendor_id');

        return view('duty-vendors.index', compact('vendors', 'balances', 'chargeTotals'));
    }

    public function show(DutyVendor $dutyVendor)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyVendor->company_id !== $cid, 403);

        $entries = DutyLedgerEntry::where('company_id', $cid)
            ->where('duty_vendor_id', $dutyVendor->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30);

        $breakdown = DutyLedgerEntry::where('company_id', $cid)
            ->where('duty_vendor_id', $dutyVendor->id)
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $chargesTotal = (float) ($breakdown['duty_charge'] ?? 0);
        $paymentTotal = abs((float) ($breakdown['payment'] ?? 0));
        $netPayable   = (float) $breakdown->sum();
        $currency     = $dutyVendor->default_currency ?: 'USD';

        // Build reference links
        $allEntries = $entries->getCollection();
        $truckIds   = $allEntries->where('ref_type', ImportTruck::class)->pluck('ref_id')->unique()->filter();

        $truckPurchaseIds = $truckIds->isNotEmpty()
            ? DB::table('import_trucks')
                ->join('import_nominations', 'import_trucks.nomination_id', '=', 'import_nominations.id')
                ->whereIn('import_trucks.id', $truckIds)
                ->pluck('import_nominations.purchase_id', 'import_trucks.id')
            : collect();

        $refLinks = [];
        foreach ($truckIds as $tid) {
            if (isset($truckPurchaseIds[$tid])) {
                $refLinks[ImportTruck::class . ':' . $tid] = route('purchases.show', $truckPurchaseIds[$tid]);
            }
        }

        return view('duty-vendors.show', compact(
            'dutyVendor', 'entries', 'refLinks',
            'chargesTotal', 'paymentTotal', 'netPayable', 'currency'
        ));
    }

    public function recordPayment(Request $request, DutyVendor $dutyVendor)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyVendor->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'nullable|string|max:8',
            'entry_date'  => 'required|date',
            'description' => 'nullable|string|max:500',
        ]);

        $currency = $data['currency'] ?: ($dutyVendor->default_currency ?: 'USD');

        DutyLedgerEntry::create([
            'company_id'     => $cid,
            'duty_vendor_id' => $dutyVendor->id,
            'type'           => 'payment',
            'amount'         => -(float) $data['amount'],
            'currency'       => $currency,
            'description'    => $data['description'] ?: 'Payment to customs authority',
            'entry_date'     => $data['entry_date'],
            'created_by'     => auth()->id(),
        ]);

        $sym = self::currencySymbol($currency);
        return redirect()->route('duty-vendors.show', $dutyVendor)
            ->with('status', 'Payment of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    public function recordAdjustment(Request $request, DutyVendor $dutyVendor)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyVendor->company_id !== $cid, 403);

        $data = $request->validate([
            'direction'   => 'required|in:debit,credit',
            'amount'      => 'required|numeric|min:0.01',
            'entry_date'  => 'required|date',
            'description' => 'required|string|max:500',
        ]);

        $currency = $dutyVendor->default_currency ?: 'USD';
        $signed   = $data['direction'] === 'debit'
                    ? (float) $data['amount']
                    : -(float) $data['amount'];

        DutyLedgerEntry::create([
            'company_id'     => $cid,
            'duty_vendor_id' => $dutyVendor->id,
            'type'           => 'adjustment',
            'amount'         => $signed,
            'currency'       => $currency,
            'description'    => $data['description'],
            'entry_date'     => $data['entry_date'],
            'created_by'     => auth()->id(),
        ]);

        $sym = self::currencySymbol($currency);
        return redirect()->route('duty-vendors.show', $dutyVendor)
            ->with('status', 'Adjustment of ' . $sym . number_format($data['amount'], 2) . ' (' . $data['direction'] . ') recorded.');
    }

    public function statement(Request $request, DutyVendor $dutyVendor)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyVendor->company_id !== $cid, 403);

        $company  = DB::table('companies')->where('id', $cid)->first();
        $dateFrom = $request->query('from');
        $dateTo   = $request->query('to');

        $query = DutyLedgerEntry::where('company_id', $cid)
            ->where('duty_vendor_id', $dutyVendor->id)
            ->orderBy('entry_date')
            ->orderBy('id');

        if ($dateFrom) $query->whereDate('entry_date', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('entry_date', '<=', $dateTo);

        $entries = $query->get();

        $openingBalance = 0.0;
        if ($dateFrom) {
            $openingBalance = (float) DutyLedgerEntry::where('company_id', $cid)
                ->where('duty_vendor_id', $dutyVendor->id)
                ->whereDate('entry_date', '<', $dateFrom)
                ->sum('amount');
        }

        $running = $openingBalance;
        foreach ($entries as $e) {
            $running += (float) $e->amount;
            $e->running_balance = $running;
        }

        $chargesTotal = (float) $entries->where('type', 'duty_charge')->sum('amount');
        $paymentTotal = abs((float) $entries->where('type', 'payment')->sum('amount'));
        $netPayable   = $running;
        $currency     = $dutyVendor->default_currency ?: 'USD';

        return view('duty-vendors.statement', compact(
            'dutyVendor', 'company', 'entries',
            'chargesTotal', 'paymentTotal', 'netPayable', 'currency',
            'dateFrom', 'dateTo', 'openingBalance'
        ));
    }

    public function exportCsv(DutyVendor $dutyVendor)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyVendor->company_id !== $cid, 403);

        $entries  = DutyLedgerEntry::where('company_id', $cid)
            ->where('duty_vendor_id', $dutyVendor->id)
            ->orderBy('entry_date')->orderBy('id')->get();

        $running  = 0;
        $filename = 'duty-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($dutyVendor->name)) . '-' . date('Y-m-d') . '.csv';

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

    public static function currencySymbol(string $code): string
    {
        return match ($code) {
            'USD' => '$', 'EUR' => '€', 'GBP' => '£',
            'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
            default => $code . ' ',
        };
    }
}
