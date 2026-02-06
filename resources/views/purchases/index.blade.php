@php
    $purchases = $purchases ?? null;
@endphp

@extends('layouts.app')

@section('title', 'Purchases')
@section('subtitle', 'Draft → Confirm → Batch → Workflow')

@section('content')
<div class="max-w-220">

    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-100">Purchases</h1>
            <p class="mt-0.5 text-[12px] text-slate-400">
                Draft purchases first. Confirm to create batches.
            </p>
        </div>

        <a href="{{ route('purchases.create') }}"
           class="h-8 px-3 inline-flex items-center rounded-lg text-[12px] font-semibold
                  bg-slate-800 hover:bg-slate-700 ring-1 ring-slate-700 transition">
            + New purchase
        </a>
    </div>

    {{-- Status --}}
    @if(session('status'))
        <div class="mb-3 text-[12px] text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    {{-- List --}}
    <div class="border border-slate-800 rounded-lg divide-y divide-slate-800">

        @forelse(($purchases?->items() ?? []) as $p)
            <a href="{{ route('purchases.show', $p) }}"
               class="block px-3 py-2 hover:bg-slate-900 transition">

                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[13px] font-semibold text-slate-100">
                                Purchase #{{ $p->id }}
                            </span>

                            @if($p->status === 'confirmed')
                                <span class="text-[11px] text-emerald-300">
                                    confirmed
                                </span>
                            @else
                                <span class="text-[11px] text-slate-400">
                                    draft
                                </span>
                            @endif
                        </div>

                        <div class="mt-0.5 text-[11px] text-slate-500">
                            {{ ucfirst($p->type) }}
                            • {{ number_format((float)$p->qty, 3) }}
                             {{ $p->Product?->base_uom }}
                            • {{ $p->currency }}
                            • {{ $p->purchase_date?->format('Y-m-d') ?? 'no date' }}
                        </div>
                           <span class="text-[11px] text-slate-500">- Created by: {{ $p->creator?->name ?? '' }}</span>
                    </div>

                    <div class="text-[11px] text-slate-500">
                        Open →
                    </div>
                </div>
            </a>
        @empty
            <div class="px-4 py-6 text-center">
                <div class="text-[13px] text-slate-300">No purchases yet</div>
                <div class="text-[12px] text-slate-500 mt-1">
                    Create your first draft purchase.
                </div>
            </div>
        @endforelse
    </div>

    @if(method_exists($purchases, 'links'))
        <div class="mt-3">
            {{ $purchases->links() }}
        </div>
    @endif
</div>
@endsection