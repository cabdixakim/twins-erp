<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transporter;
use App\Models\TransporterLedgerEntry;

class TransporterLedgerController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $transporters = Transporter::where('company_id', $cid)
            ->orderBy('name')
            ->get();

        $balances = TransporterLedgerEntry::where('company_id', $cid)
            ->selectRaw('transporter_id, SUM(amount) as balance')
            ->groupBy('transporter_id')
            ->pluck('balance', 'transporter_id');

        return view('transporters.index', compact('transporters', 'balances'));
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

        return view('transporters.show', compact(
            'transporter', 'entries',
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

        return redirect()->route('transporters.show', $transporter)
            ->with('status', 'Payment of ' . $data['currency'] . ' ' . number_format($data['amount'], 2) . ' recorded.');
    }
}
