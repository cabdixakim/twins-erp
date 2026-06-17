<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase;
use App\Models\HospitalityCharge;
use App\Models\SupplierLedgerEntry;
use App\Models\PettyCashTransaction;

class HospitalityController extends Controller
{
    public function store(Request $request, Purchase $purchase)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if($purchase->status === 'draft', 400, 'Confirm the purchase before adding hospitality charges.');

        $data = $request->validate([
            'paid_to_type'          => 'required|in:supplier,petty_cash',
            'paid_to_id'            => 'nullable|integer',
            'amount'                => 'required|numeric|min:0.01',
            'currency'              => 'required|string|max:8',
            'exchange_rate'         => 'nullable|numeric|min:0',
            'entry_date'            => 'required|date',
            'description'           => 'nullable|string|max:500',
        ]);

        $exchangeRate = max(1, (float) ($data['exchange_rate'] ?? 1));
        $amountBase   = round((float) $data['amount'] * $exchangeRate, 4);
        $paidToId     = (int) ($data['paid_to_id'] ?? 0) ?: null;
        $paidToName   = null;

        if ($data['paid_to_type'] === 'supplier' && $paidToId) {
            $paidToName = DB::table('suppliers')->where('id', $paidToId)->value('name');
        } elseif ($data['paid_to_type'] === 'petty_cash' && $paidToId) {
            $paidToName = DB::table('petty_cash_accounts')->where('id', $paidToId)->value('name');
        }

        $desc = $data['description'] ?: 'Hospitality — ' . ($paidToName ?? 'PO ' . $purchase->reference);

        DB::transaction(function () use ($cid, $purchase, $data, $amountBase, $paidToId, $paidToName, $desc, $exchangeRate) {
            $charge = HospitalityCharge::create([
                'company_id'   => $cid,
                'purchase_id'  => $purchase->id,
                'paid_to_type' => $data['paid_to_type'],
                'paid_to_id'   => $paidToId,
                'paid_to_name' => $paidToName,
                'amount'       => (float) $data['amount'],
                'currency'     => $data['currency'],
                'exchange_rate'=> $exchangeRate,
                'amount_base'  => $amountBase,
                'entry_date'   => $data['entry_date'],
                'description'  => $desc,
                'created_by'   => auth()->id(),
            ]);

            if ($data['paid_to_type'] === 'supplier' && $paidToId) {
                $supplierCurrency = DB::table('suppliers')->where('id', $paidToId)->value('default_currency') ?? $data['currency'];
                SupplierLedgerEntry::create([
                    'company_id'  => $cid,
                    'supplier_id' => $paidToId,
                    'type'        => 'purchase_invoice',
                    'amount'      => (float) $data['amount'],
                    'currency'    => $supplierCurrency,
                    'description' => $desc . ' — PO ' . $purchase->reference,
                    'entry_date'  => $data['entry_date'],
                    'ref_type'    => HospitalityCharge::class,
                    'ref_id'      => $charge->id,
                    'created_by'  => auth()->id(),
                ]);
            } elseif ($data['paid_to_type'] === 'petty_cash' && $paidToId) {
                $account = \App\Models\PettyCashAccount::where('company_id', $cid)
                    ->where('id', $paidToId)
                    ->first();

                if ($account) {
                    PettyCashTransaction::create([
                        'company_id'       => $cid,
                        'account_id'       => $account->id,
                        'type'             => 'expense',
                        'amount'           => -(float) $data['amount'],
                        'currency'         => $data['currency'],
                        'description'      => $desc,
                        'transaction_date' => $data['entry_date'],
                        'ref_type'         => HospitalityCharge::class,
                        'ref_id'           => $charge->id,
                        'created_by'       => auth()->id(),
                    ]);
                }
            }

            // Also create batch cost entry if purchase has batch
            if ($purchase->batch_id) {
                DB::table('batch_costs')->insert([
                    'batch_id'            => $purchase->batch_id,
                    'purchase_id'         => $purchase->id,
                    'company_id'          => $cid,
                    'category'            => 'hospitality',
                    'description'         => $desc,
                    'amount'              => (float) $data['amount'],
                    'currency'            => $data['currency'],
                    'exchange_rate'       => $exchangeRate,
                    'amount_base'         => $amountBase,
                    'entry_date'          => $data['entry_date'],
                    'is_included_in_cost' => false,
                    'auto_posted'         => false,
                    'created_by'          => auth()->id(),
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Hospitality charge of ' . $data['currency'] . ' ' . number_format($data['amount'], 2) . ' recorded.');
    }

    public function destroy(Purchase $purchase, HospitalityCharge $hospitalityCharge)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if((int) $hospitalityCharge->purchase_id !== $purchase->id, 403);

        $hospitalityCharge->delete();

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Hospitality charge removed.');
    }
}
