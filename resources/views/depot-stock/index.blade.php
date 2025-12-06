@extends('layouts.app')

@php
    $title    = 'Depot stock';
    $subtitle = 'See live AGO position by depot and start receive / sale / adjustments.';
@endphp

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-900/30 border border-emerald-500/60 px-3 py-2 text-xs text-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid md:grid-cols-3 gap-6">

        {{-- LEFT: Depots selector --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-sm font-semibold">Depots</h2>
                    <p class="text-[11px] text-slate-400">
                        Choose where you want to work today.
                    </p>
                </div>
            </div>

            @if($depots->isEmpty())
                <p class="text-xs text-slate-500">
                    No depots configured yet. Go to Settings → Depots to add one.
                </p>
            @else
                <ul class="space-y-1 text-xs">
                    @foreach($depots as $depot)
                        <li>
                            <a href="{{ route('depot-stock.index', ['depot' => $depot->id]) }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl
                               {{ $currentDepot && $currentDepot->id === $depot->id
                                   ? 'bg-slate-800 text-slate-50'
                                   : 'bg-slate-950/40 text-slate-300 hover:bg-slate-900'
                               }}">
                                <div class="min-w-0">
                                    <div class="font-semibold text-[13px] truncate">{{ $depot->name }}</div>
                                    <div class="text-[10px] text-slate-500 truncate">
                                        {{ $depot->city ?: 'City not set' }}
                                    </div>
                                </div>

                                <span class="text-[9px] px-2 py-0.5 rounded-full
                                    {{ $depot->is_active
                                        ? 'bg-emerald-900/50 text-emerald-200 border border-emerald-500/60'
                                        : 'bg-slate-800 text-slate-300 border border-slate-500/60'
                                    }}">
                                    {{ $depot->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- RIGHT: Selected depot dashboard --}}
        <div class="md:col-span-2 space-y-4">

            @if(!$currentDepot)
                <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/50 p-6 text-center">
                    <p class="text-sm text-slate-300 mb-1">No depots available yet.</p>
                    <p class="text-xs text-slate-500">
                        First configure your depots under <strong>Settings → Depots</strong>, then come back here.
                    </p>
                </div>
            @else
                {{-- Header card --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Working depot</div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold truncate">{{ $currentDepot->name }}</h2>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                {{ $currentDepot->is_active ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/50'
                                                            : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                                {{ $currentDepot->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            {{ $currentDepot->city ?: 'City not set' }}
                        </p>
                    </div>

                    {{-- Quick actions --}}
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-500/90 text-slate-950 hover:bg-emerald-400 disabled:opacity-40"
                            disabled
                        >
                            Receive AGO
                            <span class="ml-1 text-[9px] uppercase tracking-wide">Soon</span>
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-cyan-500/90 text-slate-950 hover:bg-cyan-400 disabled:opacity-40"
                            disabled
                        >
                            New sale
                            <span class="ml-1 text-[9px] uppercase tracking-wide">Soon</span>
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-slate-800 text-slate-100 hover:bg-slate-700 disabled:opacity-40"
                            disabled
                        >
                            Adjustment
                            <span class="ml-1 text-[9px] uppercase tracking-wide">Soon</span>
                        </button>
                    </div>
                </div>

                {{-- Metric cards – placeholder for now --}}
                <div class="grid sm:grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">On hand</div>
                        <div class="mt-1 text-lg font-semibold">
                            {{ number_format($metrics['on_hand_l'], 0) }} L
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Physical stock in this depot
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">In transit</div>
                        <div class="mt-1 text-lg font-semibold">
                            {{ number_format($metrics['in_transit_l'], 0) }} L
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Trucks not yet offloaded here
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Reserved</div>
                        <div class="mt-1 text-lg font-semibold">
                            {{ number_format($metrics['reserved_l'], 0) }} L
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Linked to open sales / clients
                        </div>
                    </div>
                </div>

                {{-- Activity placeholder --}}
                <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3 mt-2">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold">Recent movements</h3>
                        <span class="text-[11px] text-slate-500">Coming soon</span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Once we wire purchase offloads, sales and adjustments, you’ll see a live ledger
                        of all AGO movements for <strong>{{ $currentDepot->name }}</strong> here.
                    </p>
                </div>
            @endif

        </div>
    </div>

@endsection