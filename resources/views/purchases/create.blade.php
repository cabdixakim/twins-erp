@php
    /** @var \App\Models\User|null $u */
    $u = auth()->user();

    $suppliers = $suppliers ?? collect();
    $products  = $products ?? collect();
@endphp

@extends('layouts.app')

@section('title', 'New purchase')
@section('subtitle', 'Create a draft purchase, then confirm to open a batch')

@section('content')
<div class="max-w-[900px]">
    {{-- Header --}}
    <div class="mb-5">
        <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight text-slate-100">
            New purchase
        </h1>
        <p class="mt-1 text-[12px] text-slate-400">
            Save as draft first. When you confirm, the system creates a batch and moves you into the next workflow.
        </p>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-rose-500/10 ring-1 ring-rose-500/20 px-4 py-3">
            <div class="text-[13px] font-semibold text-rose-200">Fix the following:</div>
            <ul class="mt-2 text-[12px] text-rose-200/90 list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('purchases.store') }}" class="space-y-4">
        @csrf

        {{-- Main card --}}
        <div class="rounded-2xl bg-slate-950 ring-1 ring-slate-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-start justify-between gap-3">
                <div>
                    <div class="text-[13px] font-semibold text-slate-100">Purchase details</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">
                        Choose whether this is an import flow (with transport) or a local depot deal (ownership change).
                    </div>
                </div>

                <a href="{{ route('purchases.index') }}"
                   class="h-9 inline-flex items-center px-3 rounded-xl text-[12px] font-semibold
                          bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                    Back
                </a>
            </div>

            <div class="p-4 space-y-4">

                {{-- Type --}}
                <div>
                    <label class="block text-[11px] text-slate-400 mb-2">Purchase type</label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="import"
                                   class="hidden peer"
                                   {{ old('type', 'import') === 'import' ? 'checked' : '' }}>
                            <div class="rounded-2xl px-3 py-2 ring-1 ring-slate-800 bg-slate-900/40
                                        peer-checked:bg-emerald-500/10 peer-checked:ring-emerald-500/25 transition">
                                <div class="text-[13px] font-semibold text-slate-100">Import (transport)</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Goes into nominations → load → transit → TR8 → offload.
                                </div>
                            </div>
                        </label>

                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="local_depot"
                                   class="hidden peer"
                                   {{ old('type') === 'local_depot' ? 'checked' : '' }}>
                            <div class="rounded-2xl px-3 py-2 ring-1 ring-slate-800 bg-slate-900/40
                                        peer-checked:bg-emerald-500/10 peer-checked:ring-emerald-500/25 transition">
                                <div class="text-[13px] font-semibold text-slate-100">Local depot deal</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Ownership changes in the same depot (shrinkage already applied once).
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Product + Supplier --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Product</label>
                        <select name="product_id" required
                                class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                       focus:outline-none focus:ring-2 focus:ring-slate-700">
                            <option value="" disabled {{ old('product_id') ? '' : 'selected' }}>Select product…</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ (string)old('product_id') === (string)$p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-[11px] text-slate-500 mt-1">Supports AGO, PMS, etc (client-controlled).</div>
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Supplier (optional)</label>
                        <select name="supplier_id"
                                class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                       focus:outline-none focus:ring-2 focus:ring-slate-700">
                            <option value="" selected>None</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ (string)old('supplier_id') === (string)$s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-[11px] text-slate-500 mt-1">For local deals this can stay empty.</div>
                    </div>
                </div>

                {{-- Numbers --}}
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] text-slate-400 mb-1">Quantity</label>
                        <input name="qty" required inputmode="decimal"
                               value="{{ old('qty') }}"
                               class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                      placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                               placeholder="e.g. 800000.000">
                        <div class="text-[11px] text-slate-500 mt-1">Use your base unit (litres for fuel).</div>
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Unit price</label>
                        <input name="unit_price" required inputmode="decimal"
                               value="{{ old('unit_price') }}"
                               class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                      placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                               placeholder="e.g. 1.125000">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Currency</label>
                        <input name="currency" required
                               value="{{ old('currency','USD') }}"
                               class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                      placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                               placeholder="USD">
                    </div>
                </div>

                {{-- Date + Notes --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Purchase date (optional)</label>
                        <input type="date" name="purchase_date"
                               value="{{ old('purchase_date') }}"
                               class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                      focus:outline-none focus:ring-2 focus:ring-slate-700">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Notes (optional)</label>
                        <input name="notes"
                               value="{{ old('notes') }}"
                               class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                      placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                               placeholder="Any reference / comment…">
                    </div>
                </div>

            </div>

            {{-- Footer actions --}}
            <div class="px-4 py-3 border-t border-slate-800 flex items-center justify-end gap-2">
                <a href="{{ route('purchases.index') }}"
                   class="h-9 px-3 inline-flex items-center rounded-xl text-[12px] font-semibold
                          bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                    Cancel
                </a>

                <button type="submit"
                        class="h-9 px-3 rounded-xl text-[12px] font-semibold
                               bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20 transition">
                    Save draft
                </button>
            </div>
        </div>
    </form>
</div>
@endsection