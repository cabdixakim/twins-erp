@extends('layouts.app')

@php
    $title    = 'Inventory Settings';
    $subtitle = 'Manage your costing method and inventory periods.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnPrimary = "inline-flex items-center justify-center rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
    $btnGhost   = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
@endphp

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl bg-emerald-600 text-white border border-emerald-500/50 px-3 py-2 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 rounded-xl bg-red-600/20 text-red-400 border border-red-500/30 px-3 py-2 text-xs">
        {{ $errors->first() }}
    </div>
@endif

<div class="grid md:grid-cols-2 gap-6">

    {{-- COSTING METHOD --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-6">
        <h2 class="text-sm font-semibold {{ $fg }} mb-1">Costing Method</h2>
        <p class="text-xs {{ $muted }} mb-5">
            Controls how inventory cost is calculated. This setting cannot be changed once inventory movements have been posted.
            To change it later, you must start a new inventory period.
        </p>

        @if($canChangeCosting)
            <form method="POST" action="{{ route('settings.inventory.update-costing') }}">
                @csrf
                @method('PATCH')

                <div class="space-y-3 mb-5">
                    <label class="flex items-start gap-3 p-3 rounded-xl border {{ $border }} cursor-pointer hover:bg-[color:var(--tw-surface-2)] transition {{ $company->costing_method === 'weighted_average' ? 'border-emerald-500/50 bg-emerald-500/5' : '' }}">
                        <input type="radio" name="costing_method" value="weighted_average"
                               class="mt-0.5 accent-emerald-500"
                               {{ $company->costing_method === 'weighted_average' ? 'checked' : '' }}>
                        <div>
                            <div class="text-sm font-semibold {{ $fg }}">Weighted Average <span class="ml-1 text-xs font-normal text-emerald-400">(recommended)</span></div>
                            <div class="text-xs {{ $muted }} mt-0.5">Cost of goods sold is calculated using the running average cost of all stock in a depot. No batch selection required on sale.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-xl border {{ $border }} cursor-pointer hover:bg-[color:var(--tw-surface-2)] transition {{ $company->costing_method === 'specific_lot' ? 'border-emerald-500/50 bg-emerald-500/5' : '' }}">
                        <input type="radio" name="costing_method" value="specific_lot"
                               class="mt-0.5 accent-emerald-500"
                               {{ $company->costing_method === 'specific_lot' ? 'checked' : '' }}>
                        <div>
                            <div class="text-sm font-semibold {{ $fg }}">Specific Lot</div>
                            <div class="text-xs {{ $muted }} mt-0.5">Cost is tied to the exact batch received. Each sale requires selecting a specific batch. Ideal when lot-level traceability is required.</div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="{{ $btnPrimary }} px-4 py-2 text-sm">
                    Save Costing Method
                </button>
            </form>
        @else
            {{-- Locked state --}}
            <div class="p-3 rounded-xl border {{ $border }} {{ $surface2 }} mb-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    <span class="text-xs font-semibold text-amber-400">Costing method is locked</span>
                </div>
                <p class="text-xs {{ $muted }}">
                    Inventory movements have been posted under
                    <span class="font-semibold {{ $fg }}">
                        {{ $company->costing_method === 'weighted_average' ? 'Weighted Average' : 'Specific Lot' }}
                    </span>.
                    To change the costing method, you must close the current period and start a new one.
                </p>
            </div>

            <div class="flex items-center gap-2 p-3 rounded-xl border border-dashed {{ $border }} {{ $surface2 }} opacity-50 mb-4">
                <div class="flex-1">
                    <div class="text-sm font-semibold {{ $fg }}">
                        {{ $company->costing_method === 'weighted_average' ? 'Weighted Average' : 'Specific Lot' }}
                        <span class="ml-1 text-xs font-normal text-emerald-400">(active)</span>
                    </div>
                </div>
                <svg class="w-4 h-4 {{ $muted }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </div>

            <button type="button"
                    onclick="document.getElementById('newPeriodModal').classList.remove('hidden')"
                    class="{{ $btnPrimary }} px-4 py-2 text-sm">
                Close Period &amp; Start New
            </button>
            <p class="text-xs {{ $muted }} mt-2">Closing the current period locks all existing movements. A new period starts immediately.</p>

            {{-- New Period Modal --}}
            <div id="newPeriodModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
                <div class="rounded-2xl border {{ $border }} {{ $surface }} p-6 w-full max-w-md shadow-2xl">
                    <h2 class="text-base font-semibold {{ $fg }} mb-1">Close Period &amp; Start New</h2>
                    <p class="text-xs {{ $muted }} mb-5">
                        This will close <span class="font-semibold {{ $fg }}">{{ $openPeriod?->name }}</span> and
                        open a fresh period. All future postings will use the new costing method.
                    </p>
                    <form method="POST" action="{{ route('settings.inventory.close-period') }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold {{ $muted }} mb-1">New Period Name</label>
                                <input type="text" name="new_period_name"
                                       value="Period {{ now()->format('M Y') }}"
                                       class="w-full rounded-xl border {{ $border }} bg-transparent px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                                       required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold {{ $muted }} mb-2">Costing Method for New Period</label>
                                <div class="space-y-2">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border {{ $border }} cursor-pointer hover:bg-[color:var(--tw-surface-2)] transition">
                                        <input type="radio" name="new_costing_method" value="weighted_average" class="mt-0.5 accent-emerald-500" checked>
                                        <div>
                                            <div class="text-sm font-semibold {{ $fg }}">Weighted Average</div>
                                            <div class="text-xs {{ $muted }} mt-0.5">Running average cost across all stock. Recommended for most fuel operations.</div>
                                        </div>
                                    </label>
                                    <label class="flex items-start gap-3 p-3 rounded-xl border {{ $border }} cursor-pointer hover:bg-[color:var(--tw-surface-2)] transition">
                                        <input type="radio" name="new_costing_method" value="specific_lot" class="mt-0.5 accent-emerald-500">
                                        <div>
                                            <div class="text-sm font-semibold {{ $fg }}">Specific Lot</div>
                                            <div class="text-xs {{ $muted }} mt-0.5">Cost tied to exact batch. Requires selecting a batch on each sale.</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button type="button"
                                    onclick="document.getElementById('newPeriodModal').classList.add('hidden')"
                                    class="{{ $btnGhost }} flex-1 px-4 py-2 text-sm">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 px-4 py-2 text-sm rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-semibold transition">
                                Close &amp; Start New Period
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    {{-- INVENTORY PERIODS --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-6">
        <h2 class="text-sm font-semibold {{ $fg }} mb-1">Inventory Periods</h2>
        <p class="text-xs {{ $muted }} mb-5">
            Each period locks in a costing method and a time range. Closed periods are immutable.
        </p>

        {{-- Open Period --}}
        @if($openPeriod)
            <div class="p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/5 mb-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold text-emerald-400">Open Period</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-semibold">Active</span>
                </div>
                <div class="text-sm font-semibold {{ $fg }}">{{ $openPeriod->name }}</div>
                <div class="text-xs {{ $muted }} mt-1 space-y-0.5">
                    <div>Costing: <span class="{{ $fg }} font-medium">{{ $openPeriod->costing_method === 'weighted_average' ? 'Weighted Average' : 'Specific Lot' }}</span></div>
                    <div>Started: <span class="{{ $fg }} font-medium">{{ $openPeriod->starts_at->format('d M Y') }}</span></div>
                </div>
            </div>
        @else
            <div class="p-3 rounded-xl border border-dashed {{ $border }} {{ $surface2 }} mb-4 text-center">
                <p class="text-xs {{ $muted }}">No open period. One will be created automatically on the first inventory posting.</p>
            </div>
        @endif

        {{-- Closed Periods --}}
        @if($closedPeriods->isNotEmpty())
            <h3 class="text-xs font-semibold {{ $muted }} uppercase tracking-wide mb-2">Closed Periods</h3>
            <div class="space-y-2">
                @foreach($closedPeriods as $period)
                    <div class="p-3 rounded-xl border {{ $border }} {{ $surface2 }}">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold {{ $fg }}">{{ $period->name }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-[color:var(--tw-surface)] text-[color:var(--tw-muted)] border {{ $border }}">Closed</span>
                        </div>
                        <div class="text-xs {{ $muted }} mt-1 space-y-0.5">
                            <div>Costing: <span class="{{ $fg }} font-medium">{{ $period->costing_method === 'weighted_average' ? 'Weighted Average' : 'Specific Lot' }}</span></div>
                            <div>
                                {{ $period->starts_at->format('d M Y') }}
                                →
                                {{ $period->ends_at?->format('d M Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs {{ $muted }}">No closed periods yet.</p>
        @endif
    </div>

</div>

@endsection
