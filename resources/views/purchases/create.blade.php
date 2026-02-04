@extends('layouts.standalone')

@section('title','New AGO Purchase')

@section('content')
<div class="w-full max-w-180 mx-auto">
    <div class="mb-4">
        <div class="text-[18px] font-semibold text-slate-100">New AGO Purchase</div>
        <div class="text-[12px] text-slate-400">Choose import vs local. Confirm later to create a batch.</div>
    </div>

    <form method="post" action="{{ route('purchases.store') }}" class="rounded-2xl bg-slate-950 ring-1 ring-slate-800 p-4 space-y-4">
        @csrf

        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Purchase type</label>
                <select name="type" class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]">
                    <option value="import">Import (needs transport)</option>
                    <option value="local_depot">Local (already in depot)</option>
                </select>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Supplier (optional)</label>
                <select name="supplier_id" class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]">
                    <option value="">—</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Purchase date</label>
                <input type="date" name="purchase_date" class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]">
            </div>
            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Quantity (liters)</label>
                <input name="qty_liters" required inputmode="decimal"
                       class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]"
                       placeholder="e.g. 800000">
            </div>
            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Currency</label>
                <input name="currency" value="USD"
                       class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]">
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Unit price</label>
                <input name="unit_price" required inputmode="decimal"
                       class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]"
                       placeholder="e.g. 1.25">
            </div>
            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Product</label>
                <input value="AGO" disabled
                       class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px] opacity-70">
            </div>
        </div>

        <div>
            <label class="block text-[11px] text-slate-400 mb-1">Notes</label>
            <textarea name="notes" rows="3"
                      class="w-full px-3 py-2 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]"></textarea>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
            <a href="{{ route('purchases.index') }}"
               class="h-9 inline-flex items-center px-3 rounded-xl text-[12px] font-semibold bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800">
                Cancel
            </a>
            <button type="submit"
                    class="h-9 px-3 rounded-xl text-[12px] font-semibold bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20">
                Create draft
            </button>
        </div>
    </form>
</div>
@endsection