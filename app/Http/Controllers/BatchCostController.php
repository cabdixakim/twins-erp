<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Batch;
use App\Models\BatchCost;
use Illuminate\Support\Facades\DB;

class BatchCostController extends Controller
{
    public function store(Request $request, Purchase $purchase)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if(!$purchase->batch_id, 400, 'Purchase has no batch yet — confirm it first.');
        abort_if($purchase->status === 'draft', 400, 'Confirm the purchase before adding landed costs.');

        $data = $request->validate([
            'category'              => 'required|in:freight,duty,border_charge,hospitality,storage,penalty,other',
            'description'           => 'required|string|max:500',
            'amount'                => 'required|numeric|min:0.01',
            'currency'              => 'required|string|max:8',
            'exchange_rate'         => 'nullable|numeric|min:0',
            'entry_date'            => 'required|date',
            'is_included_in_cost'   => 'nullable|boolean',
            'paid_by_type'          => 'nullable|in:self,depot,transporter,other',
            'paid_by_id_depot'      => 'nullable|integer',
            'paid_by_id_transporter'=> 'nullable|integer',
            'paid_by_name'          => 'nullable|string|max:200',
        ]);

        $exchangeRate = (float) ($data['exchange_rate'] ?? 1);
        if ($exchangeRate <= 0) {
            $exchangeRate = 1;
        }
        $amountBase = round((float) $data['amount'] * $exchangeRate, 4);

        $paidByType = $data['paid_by_type'] ?? 'self';
        $paidById   = null;
        $paidByName = null;

        if ($paidByType === 'depot') {
            $paidById = (int) ($data['paid_by_id_depot'] ?? 0) ?: null;
            if ($paidById) {
                $paidByName = DB::table('depots')->where('id', $paidById)->value('name');
            }
        } elseif ($paidByType === 'transporter') {
            $paidById = (int) ($data['paid_by_id_transporter'] ?? 0) ?: null;
            if ($paidById) {
                $paidByName = DB::table('transporters')->where('id', $paidById)->value('name');
            }
        } elseif ($paidByType === 'other') {
            $paidByName = $data['paid_by_name'] ?? null;
        }

        BatchCost::create([
            'company_id'          => $cid,
            'batch_id'            => $purchase->batch_id,
            'purchase_id'         => $purchase->id,
            'category'            => $data['category'],
            'description'         => $data['description'],
            'amount'              => (float) $data['amount'],
            'currency'            => $data['currency'],
            'exchange_rate'       => $exchangeRate,
            'amount_base'         => $amountBase,
            'is_included_in_cost' => !empty($data['is_included_in_cost']),
            'entry_date'          => $data['entry_date'],
            'paid_by_type'        => $paidByType !== 'self' ? $paidByType : null,
            'paid_by_id'          => $paidById,
            'paid_by_name'        => $paidByName,
            'created_by'          => auth()->id(),
        ]);

        // ── Secondary AP auto-posting ────────────────────────────────────────────
        //
        // Storage/throughput/loading/offloading categories ALWAYS generate a depot
        // ledger charge against the purchase's depot (we owe the depot for these),
        // regardless of paid_by_type.  Other categories only post when a specific
        // third party fronted the cost (paid_by_type = depot | transporter).
        //
        $depotCategories = ['storage', 'handling_fee', 'loading_fee', 'offloading'];
        $ledgerTypeMap   = [
            'storage'          => 'storage_charge',
            'handling_fee'=> 'handling_fee',
            'loading_fee'      => 'loading_fee',
            'offloading'       => 'loading_fee',
        ];

        if (in_array($data['category'], $depotCategories)) {
            // Use the explicitly chosen depot, or fall back to the purchase's own depot
            $targetDepotId = ($paidByType === 'depot' && $paidById)
                ? $paidById
                : (int) ($purchase->depot_id ?? 0);

            if ($targetDepotId) {
                $ledgerType = $ledgerTypeMap[$data['category']] ?? 'other_charge';
                DB::table('depot_ledger_entries')->insert([
                    'company_id'  => $cid,
                    'depot_id'    => $targetDepotId,
                    'type'        => $ledgerType,
                    'amount'      => $amountBase,
                    'currency'    => $data['currency'],
                    'description' => $data['description'] . " — PO {$purchase->reference}",
                    'entry_date'  => $data['entry_date'],
                    'ref_type'    => Purchase::class,
                    'ref_id'      => $purchase->id,
                    'created_by'  => auth()->id(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        } elseif ($paidByType === 'depot' && $paidById) {
            // Non-storage cost fronted by a depot (e.g. duty, border charge)
            DB::table('depot_ledger_entries')->insert([
                'company_id'  => $cid,
                'depot_id'    => $paidById,
                'type'        => 'other_charge',
                'amount'      => $amountBase,
                'currency'    => $data['currency'],
                'description' => "{$data['description']} (fronted for PO {$purchase->reference})",
                'entry_date'  => $data['entry_date'],
                'ref_type'    => Purchase::class,
                'ref_id'      => $purchase->id,
                'created_by'  => auth()->id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } elseif ($paidByType === 'transporter' && $paidById) {
            // Transporter/clearing agent fronted it — record an advance owed back to them
            DB::table('transporter_ledger_entries')->insert([
                'company_id'     => $cid,
                'transporter_id' => $paidById,
                'type'           => 'advance',
                'amount'         => $amountBase,
                'currency'       => $data['currency'],
                'description'    => "{$data['description']} — advance for PO {$purchase->reference}",
                'entry_date'     => $data['entry_date'],
                'ref_type'       => Purchase::class,
                'ref_id'         => $purchase->id,
                'created_by'     => auth()->id(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Landed cost added.');
    }

    public function destroy(Purchase $purchase, BatchCost $batchCost)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if((int) $batchCost->purchase_id !== $purchase->id, 403);

        if ($batchCost->auto_posted) {
            return back()->with('error', 'Auto-posted costs (freight, hospitality) cannot be deleted — they are system records from truck milestones.');
        }

        $batchCost->delete();

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Landed cost removed.');
    }
}
