<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase;
use App\Models\HospitalityCharge;
use App\Models\SupplierLedgerEntry;
use App\Models\PettyCashTransaction;
use App\Models\PettyCashAccount;

class HospitalityController extends Controller
{
    public function store(Request $request, Purchase $purchase)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if($purchase->status === 'draft', 400, 'Confirm the purchase before adding hospitality charges.');

        $data = $request->validate([
            'paid_to_type'  => 'required|in:supplier,petty_cash',
            'paid_to_id'    => 'required|integer|min:1',
            'amount'        => 'required|numeric|min:0.01',
            'currency'      => 'required|string|max:8',
            'exchange_rate' => 'nullable|numeric|min:0',
            'entry_date'    => 'required|date',
            'description'   => 'nullable|string|max:500',
        ]);

        $exchangeRate = max(1, (float) ($data['exchange_rate'] ?? 1));
        $amountBase   = round((float) $data['amount'] * $exchangeRate, 4);
        $rawId        = (int) $data['paid_to_id'];
        $paidToId     = null;
        $paidToName   = null;

        if ($data['paid_to_type'] === 'supplier') {
            $supplier = DB::table('suppliers')
                ->where('id', $rawId)->where('company_id', $cid)->first();
            if (! $supplier) {
                return back()->withErrors(['paid_to_id' => 'Selected supplier not found or does not belong to this company.'])->withInput();
            }
            $paidToId   = $rawId;
            $paidToName = $supplier->name;
        } elseif ($data['paid_to_type'] === 'petty_cash') {
            $account = PettyCashAccount::where('id', $rawId)->where('company_id', $cid)->first();
            if (! $account) {
                return back()->withErrors(['paid_to_id' => 'Selected petty cash account not found or does not belong to this company.'])->withInput();
            }
            $paidToId   = $rawId;
            $paidToName = $account->name;
        }

        $desc = $data['description'] ?: 'Hospitality — ' . ($paidToName ?? 'PO ' . $purchase->reference);

        DB::transaction(function () use ($cid, $purchase, $data, $amountBase, $paidToId, $paidToName, $desc, $exchangeRate) {
            $charge = HospitalityCharge::create([
                'company_id'    => $cid,
                'purchase_id'   => $purchase->id,
                'paid_to_type'  => $data['paid_to_type'],
                'paid_to_id'    => $paidToId,
                'paid_to_name'  => $paidToName,
                'amount'        => (float) $data['amount'],
                'currency'      => $data['currency'],
                'exchange_rate' => $exchangeRate,
                'amount_base'   => $amountBase,
                'entry_date'    => $data['entry_date'],
                'description'   => $desc,
                'created_by'    => auth()->id(),
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
                $account = PettyCashAccount::where('company_id', $cid)->where('id', $paidToId)->first();
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

            if ($purchase->batch_id) {
                DB::table('batch_costs')->insert([
                    'batch_id'              => $purchase->batch_id,
                    'purchase_id'           => $purchase->id,
                    'company_id'            => $cid,
                    'hospitality_charge_id' => $charge->id,
                    'category'              => 'hospitality',
                    'description'           => $desc,
                    'amount'                => (float) $data['amount'],
                    'currency'              => $data['currency'],
                    'exchange_rate'         => $exchangeRate,
                    'amount_base'           => $amountBase,
                    'entry_date'            => $data['entry_date'],
                    'is_included_in_cost'   => false,
                    'auto_posted'           => false,
                    'created_by'            => auth()->id(),
                    'created_at'            => now(),
                    'updated_at'            => now(),
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
        abort_if((int) $hospitalityCharge->company_id !== $cid, 403);

        DB::transaction(function () use ($hospitalityCharge) {
            // Reverse supplier ledger entry
            SupplierLedgerEntry::where('ref_type', HospitalityCharge::class)
                ->where('ref_id', $hospitalityCharge->id)
                ->delete();

            // Reverse petty cash transaction
            PettyCashTransaction::where('ref_type', HospitalityCharge::class)
                ->where('ref_id', $hospitalityCharge->id)
                ->delete();

            // Remove batch cost entry
            DB::table('batch_costs')
                ->where('hospitality_charge_id', $hospitalityCharge->id)
                ->delete();

            $hospitalityCharge->delete();
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Hospitality charge removed and all linked entries reversed.');
    }
}
