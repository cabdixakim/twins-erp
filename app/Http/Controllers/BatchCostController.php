<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Batch;
use App\Models\BatchCost;

class BatchCostController extends Controller
{
    public function store(Request $request, Purchase $purchase)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if(!$purchase->batch_id, 400, 'Purchase has no batch yet — confirm it first.');
        abort_if($purchase->status === 'draft', 400, 'Confirm the purchase before adding landed costs.');

        $data = $request->validate([
            'category'            => 'required|in:freight,duty,border_charge,hospitality,storage,penalty,other',
            'description'         => 'required|string|max:500',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => 'required|string|max:8',
            'exchange_rate'       => 'nullable|numeric|min:0',
            'entry_date'          => 'required|date',
            'is_included_in_cost' => 'nullable|boolean',
        ]);

        $exchangeRate = (float) ($data['exchange_rate'] ?? 1);
        if ($exchangeRate <= 0) {
            $exchangeRate = 1;
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
            'amount_base'         => round((float) $data['amount'] * $exchangeRate, 4),
            'is_included_in_cost' => !empty($data['is_included_in_cost']),
            'entry_date'          => $data['entry_date'],
            'created_by'          => auth()->id(),
        ]);

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Landed cost added.');
    }

    public function destroy(Purchase $purchase, BatchCost $batchCost)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if((int) $batchCost->purchase_id !== $purchase->id, 403);

        if ($batchCost->auto_posted) {
            return back()->with('error', 'Auto-posted costs (freight, shortfall) cannot be deleted. They are system records from truck deliveries.');
        }

        $batchCost->delete();

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Landed cost removed.');
    }
}
