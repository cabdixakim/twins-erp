<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DutyRate;
use App\Models\Product;

class DutyRateController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $products = Product::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get();

        $rates = DutyRate::where('company_id', $cid)
            ->with('product')
            ->orderByDesc('effective_from')
            ->get();

        return view('settings.duty-rates', compact('products', 'rates'));
    }

    public function store(Request $request)
    {
        $cid = (int) auth()->user()->active_company_id;

        $data = $request->validate([
            'product_id'     => 'required|integer',
            'rate_per_1000l' => 'required|numeric|min:0',
            'currency'       => 'required|string|max:8',
            'effective_from' => 'required|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
            'notes'          => 'nullable|string|max:500',
        ]);

        // Verify product belongs to company
        abort_unless(Product::where('company_id', $cid)->where('id', $data['product_id'])->exists(), 403);

        DutyRate::create(array_merge($data, [
            'company_id' => $cid,
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('settings.duty-rates.index')
            ->with('status', 'Duty rate created.');
    }

    public function update(Request $request, DutyRate $dutyRate)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyRate->company_id !== $cid, 403);

        $data = $request->validate([
            'rate_per_1000l' => 'required|numeric|min:0',
            'currency'       => 'required|string|max:8',
            'effective_from' => 'required|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
            'notes'          => 'nullable|string|max:500',
        ]);

        $dutyRate->update($data);

        return redirect()->route('settings.duty-rates.index')
            ->with('status', 'Duty rate updated.');
    }

    public function destroy(DutyRate $dutyRate)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyRate->company_id !== $cid, 403);

        $dutyRate->delete();

        return redirect()->route('settings.duty-rates.index')
            ->with('status', 'Duty rate deleted.');
    }

    public function forProduct(Request $request)
    {
        $cid       = (int) auth()->user()->active_company_id;
        $productId = (int) $request->query('product_id');
        $date      = $request->query('date', now()->toDateString());

        $rate = DutyRate::where('company_id', $cid)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        return response()->json([
            'rate'     => $rate?->rate_per_1000l,
            'currency' => $rate?->currency,
        ]);
    }
}
