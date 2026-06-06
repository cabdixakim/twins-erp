<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transporter;
use App\Models\TransporterLedgerEntry;
use App\Models\ImportTruck;
use App\Models\ImportNomination;

class TransporterLedgerController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        // Fix #2: only show active transporters
        $transporters = Transporter::where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $balances = TransporterLedgerEntry::where('company_id', $cid)
            ->selectRaw('transporter_id, SUM(amount) as balance')
            ->groupBy('transporter_id')
            ->pluck('balance', 'transporter_id');

        // Precompute freight totals to avoid N+1 in the view
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
            ->paginate(30);

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

        // Fix #3: Build clickable reference links
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

        return view('transporters.show', compact(
            'transporter', 'entries', 'refLinks',
            'freightTotal', 'advanceTotal', 'shortChargeTotal', 'paymentTotal',
            'netPayable', 'currency'
        ));
    }

    public function recordPayment(Request $request, Transporter $transporter)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $transporter->company_id !== $cid, 403);

        $data = $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'entry_date'  => 'required|date',
            'currency'    => 'required|string|max:8',
            'description' => 'nullable|string|max:500',
        ]);

        TransporterLedgerEntry::create([
            'company_id'     => $cid,
            'transporter_id' => $transporter->id,
            'type'           => 'payment',
            'amount'         => -(float) $data['amount'],
            'currency'       => $data['currency'],
            'description'    => $data['description'] ?: 'Payment to transporter',
            'entry_date'     => $data['entry_date'],
            'created_by'     => auth()->id(),
        ]);

        $sym    = self::currencySymbol($data['currency']);
        return redirect()->route('transporters.show', $transporter)
            ->with('status', 'Payment of ' . $sym . number_format($data['amount'], 2) . ' recorded.');
    }

    /**
     * Map currency codes to display symbols.
     */
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
