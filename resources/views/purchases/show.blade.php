@extends('layouts.standalone')

@section('title','AGO Purchase')

@section('content')
<div class="w-full max-w-205 mx-auto">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <div class="text-[18px] font-semibold text-slate-100">AGO Purchase</div>
            <div class="text-[12px] text-slate-400">Confirm to auto-create a Batch.</div>
        </div>

        <a href="{{ route('purchases.index') }}"
           class="h-9 inline-flex items-center px-3 rounded-xl text-[12px] font-semibold bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800">
            Back
        </a>
    </div>

    <div class="rounded-2xl bg-slate-950 ring-1 ring-slate-800 p-4 space-y-3">
        <div class="text-[12px] text-slate-400">Status</div>
        <div class="flex items-center gap-2">
            <div class="text-[13px] font-semibold text-slate-100">{{ strtoupper($purchase->status) }}</div>
            @if($purchase->batch_id)
                <div class="text-[11px] text-emerald-300 bg-emerald-500/10 ring-1 ring-emerald-500/20 px-2 py-0.5 rounded-lg">
                    Batch #{{ $purchase->batch_id }}
                </div>
            @endif
        </div>

        <div class="grid sm:grid-cols-3 gap-3 pt-2">
            <div>
                <div class="text-[11px] text-slate-500">Type</div>
                <div class="text-[13px] text-slate-100 font-semibold">{{ $purchase->type }}</div>
            </div>
            <div>
                <div class="text-[11px] text-slate-500">Qty</div>
                <div class="text-[13px] text-slate-100 font-semibold">{{ number_format($purchase->qty_liters,3) }} L</div>
            </div>
            <div>
                <div class="text-[11px] text-slate-500">Unit price</div>
                <div class="text-[13px] text-slate-100 font-semibold">{{ strtoupper($purchase->currency) }} {{ number_format($purchase->unit_price,2) }}</div>
            </div>
        </div>

        @if($purchase->status === 'draft')
            <form method="post" action="{{ route('purchases.confirm',$purchase) }}" class="pt-3 flex justify-end">
                @csrf
                <button class="h-9 px-3 rounded-xl text-[12px] font-semibold bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20">
                    Confirm & create batch
                </button>
            </form>
        @endif
    </div>
</div>
@endsection