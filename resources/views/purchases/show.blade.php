@php
    $purchase = $purchase;
@endphp

@extends('layouts.app')

@section('title', 'Purchase')
@section('subtitle', 'Review and confirm')

@section('content')
<div class="max-w-220">

    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-100">
                Purchase #{{ $purchase->id }}
            </h1>
            <div class="text-[12px] text-slate-400">
                Status: {{ $purchase->status }}
                @if($purchase->batch_id)
                    • Batch #{{ $purchase->batch_id }}
                @endif
            </div>
        </div>

        <a href="{{ route('purchases.index') }}"
           class="text-[12px] px-3 py-2 rounded-md border border-slate-700">
            Back
        </a>
    </div>

    {{-- Messages --}}
    @if(session('status'))
        <div class="mb-3 text-[12px] text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    {{-- Details --}}
    <div class="border border-slate-800 rounded-lg p-4 text-[13px]">

        <div class="grid grid-cols-2 gap-3">
            <div>
                <div class="text-[11px] text-slate-500">Type</div>
                <div>{{ ucfirst($purchase->type) }}</div>
            </div>

            <div>
                <div class="text-[11px] text-slate-500">Date</div>
                <div>{{ $purchase->purchase_date?->format('Y-m-d') ?? '—' }}</div>
            </div>

            <div>
                <div class="text-[11px] text-slate-500">Quantity</div>
                <div>{{ number_format((float)$purchase->qty,3) }}</div>
            </div>

            <div>
                <div class="text-[11px] text-slate-500">Unit price</div>
                <div>{{ $purchase->currency }} {{ number_format((float)$purchase->unit_price,6) }}</div>
            </div>

            <div class="col-span-2">
                <div class="text-[11px] text-slate-500">Notes</div>
                <div>{{ $purchase->notes ?: '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Confirm --}}
    @if($purchase->status === 'draft')
        <div class="mt-4 flex justify-end">
            <form method="POST" action="{{ route('purchases.confirm', $purchase) }}">
                @csrf
                <button type="submit"
                        class="text-[12px] px-4 py-2 rounded-md
                               bg-emerald-600/20 text-emerald-300 border border-emerald-500/30">
                    Confirm → Create batch
                </button>
            </form>
        </div>
    @endif
</div>
@endsection