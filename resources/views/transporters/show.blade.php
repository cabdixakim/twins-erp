@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $typeMeta = [
        'freight_charge' => ['label' => 'Freight',      'color' => 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'],
        'advance'        => ['label' => 'Advance',      'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
        'short_charge'   => ['label' => 'Short charge', 'color' => 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30'],
        'payment'        => ['label' => 'Payment',      'color' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30'],
        'recovery'       => ['label' => 'Recovery',     'color' => 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30'],
        'adjustment'     => ['label' => 'Adjustment',   'color' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30'],
        'settlement'     => ['label' => 'Settlement',   'color' => 'bg-teal-500/15 text-teal-700 dark:text-teal-300 border border-teal-500/30'],
    ];

    $advanceTypeMeta = [
        'trip'    => ['label' => 'Trip advance',    'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
        'fuel'    => ['label' => 'Fuel advance',    'color' => 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border border-orange-500/30'],
        'driver'  => ['label' => 'Driver advance',  'color' => 'bg-yellow-500/15 text-yellow-700 dark:text-yellow-300 border border-yellow-500/30'],
        'general' => ['label' => 'General advance', 'color' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30'],
        'other'   => ['label' => 'Advance',         'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
    ];

    $currencySymbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
    ];
    $sym = fn(string $code) => $currencySymbols[$code] ?? ($code . ' ');

    $activeTab = request('tab', 'trips');
@endphp

@extends('layouts.app')

@section('title', $transporter->name . ' — Ledger')
@section('subtitle', 'Trip advances, freight & payments')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600/10 text-emerald-700 dark:text-emerald-300 px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-600/10 text-rose-600 dark:text-rose-300 px-4 py-2.5 text-xs font-semibold">
        {{ session('error') }}
    </div>
@endif

{{-- Back + actions --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-2">
    <a href="{{ route('transporters.index') }}"
       class="inline-flex items-center gap-1.5 text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        All transporters
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('transporters.statement', $transporter) }}" target="_blank"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
            </svg>
            Statement
        </a>
        <a href="{{ route('transporters.export', $transporter) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Export CSV
        </a>
        <button type="button" onclick="openAdvanceModal()"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-amber-500/30 bg-amber-500/10 text-xs font-semibold text-amber-600 dark:text-amber-300 hover:bg-amber-500/20 transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Advance
        </button>
        <button type="button" onclick="openAdjustmentModal()"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Adjustment
        </button>
        <button type="button" onclick="openPaymentModal()"
                class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Payment
        </button>
        <button type="button" onclick="openSettleModal()"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-teal-500/30 bg-teal-500/10 text-xs font-semibold text-teal-600 dark:text-teal-300 hover:bg-teal-500/20 transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Settle Account
        </button>
    </div>
</div>

{{-- Transporter name + meta --}}
<div class="mb-5">
    <div class="flex items-center gap-2 mb-0.5">
        <h1 class="text-xl font-bold {{ $fg }}">{{ $transporter->name }}</h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
            {{ $transporter->is_active
                ? 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'
                : 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border }}">
            {{ $transporter->is_active ? 'Active' : 'Inactive' }}
        </span>
        @if($transporter->type)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
            {{ $transporter->type === 'local'
                ? 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30'
                : 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30' }}">
            {{ $transporter->type === 'local' ? 'Local' : 'International' }}
        </span>
        @endif
    </div>
    <p class="text-xs {{ $muted }}">
        @if($transporter->city || $transporter->country)
            {{ $transporter->city }}{{ $transporter->city && $transporter->country ? ', ' : '' }}{{ $transporter->country }}
        @endif
        @if($transporter->contact_person)
            @if($transporter->city || $transporter->country) · @endif
            {{ $transporter->contact_person }}
        @endif
        @if($transporter->phone)
            · {{ $transporter->phone }}
        @endif
    </p>
</div>

{{-- Balance summary --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Freight earned</div>
        <div class="text-base font-bold {{ $fg }}">{{ $sym($currency) }}{{ number_format($freightTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Gross from trips</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Advances paid</div>
        <div class="text-base font-bold text-amber-500">{{ $sym($currency) }}{{ number_format($advanceTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Trip + fuel + driver</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Short charges</div>
        <div class="text-base font-bold text-rose-500">{{ $sym($currency) }}{{ number_format($shortChargeTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Deducted for loss</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Payments made</div>
        <div class="text-base font-bold text-sky-500">{{ $sym($currency) }}{{ number_format($paymentTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Settled to date</div>
    </div>
    <div class="rounded-2xl border {{ $netPayable > 0.005 ? 'border-amber-500/40' : $border }} {{ $surface }} p-4 sm:col-span-1 col-span-2">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Net payable</div>
        @if(abs($netPayable) < 0.005)
            <div class="text-base font-bold text-emerald-500">Settled</div>
            <div class="text-[10px] {{ $muted }}">Nothing outstanding</div>
        @elseif($netPayable > 0)
            <div class="text-base font-bold text-amber-500">{{ $sym($currency) }}{{ number_format($netPayable, 2) }}</div>
            <div class="text-[10px] {{ $muted }}">Still owed to transporter</div>
        @else
            <div class="text-base font-bold text-emerald-500">{{ $sym($currency) }}{{ number_format(abs($netPayable), 2) }} overpaid</div>
            <div class="text-[10px] {{ $muted }}">Credit on account</div>
        @endif
    </div>
</div>

{{-- Tabs --}}
<div class="flex gap-1 mb-4 border-b {{ $border }}">
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'trips']) }}"
       class="px-4 py-2.5 text-xs font-semibold transition border-b-2 -mb-px
              {{ $activeTab === 'trips'
                  ? 'border-[color:var(--tw-accent)] text-[color:var(--tw-accent)]'
                  : 'border-transparent ' . $muted . ' hover:text-[color:var(--tw-fg)]' }}">
        Trips
        @if(count($trips) + count($importTrips) > 0)
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold
                {{ $activeTab === 'trips' ? 'bg-[color:var(--tw-accent)]/15 text-[color:var(--tw-accent)]' : 'bg-[color:var(--tw-surface-2)] ' . $muted }}">
                {{ count($trips) + count($importTrips) }}
            </span>
        @endif
    </a>
    <a href="{{ request()->fullUrlWithQuery(['tab' => 'ledger']) }}"
       class="px-4 py-2.5 text-xs font-semibold transition border-b-2 -mb-px
              {{ $activeTab === 'ledger'
                  ? 'border-[color:var(--tw-accent)] text-[color:var(--tw-accent)]'
                  : 'border-transparent ' . $muted . ' hover:text-[color:var(--tw-fg)]' }}">
        Full ledger
        <span class="ml-1 px-1.5 py-0.5 rounded-full text-[9px] font-bold
            {{ $activeTab === 'ledger' ? 'bg-[color:var(--tw-accent)]/15 text-[color:var(--tw-accent)]' : 'bg-[color:var(--tw-surface-2)] ' . $muted }}">
            {{ $entries->total() }}
        </span>
    </a>
</div>

{{-- ══ TRIPS TAB ══ --}}
@if($activeTab === 'trips')

{{-- In-progress trucks (nominated / loaded / in-transit / border) --}}
@if(isset($inProgressImportTrucks) && count($inProgressImportTrucks) > 0)
<div class="mb-5">
    <div class="text-[10px] uppercase tracking-widest font-semibold {{ $muted }} px-1 mb-2">In progress — projected payable</div>
    <div class="space-y-2">
        @foreach($inProgressImportTrucks as $ipt)
        @php
            $rate    = (float)($ipt->nomination->rate_per_1000l ?? 0);
            $isNom   = $ipt->status === 'nominated';
            $qty     = $isNom ? (float)$ipt->capacity : (float)$ipt->qty_loaded;
            $proj    = $qty * $rate;
            $cur     = $ipt->nomination->currency ?? $currency;
            $scBadge = match($ipt->statusColor()) {
                's-blue'   => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30',
                's-amber'  => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30',
                's-orange' => 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border border-orange-500/30',
                's-purple' => 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30',
                default    => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30',
            };
        @endphp
        <div class="rounded-2xl border border-dashed {{ $border }} {{ $surface }} overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 flex-wrap gap-2">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-semibold {{ $fg }}">{{ $ipt->truck_reg }}</span>
                        @if($ipt->trailer_reg)
                            <span class="text-[10px] {{ $muted }} font-mono">+ {{ $ipt->trailer_reg }}</span>
                        @endif
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $scBadge }}">
                            {{ $ipt->statusLabel() }}
                        </span>
                        @if($ipt->nomination?->purchase)
                            <a href="{{ route('purchases.show', $ipt->nomination->purchase->id) }}"
                               class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30 hover:bg-purple-500/25 transition">
                                {{ $ipt->nomination->purchase->reference }}
                            </a>
                        @endif
                    </div>
                    <div class="text-[11px] {{ $muted }} mt-0.5 flex items-center gap-2 flex-wrap">
                        @if($ipt->driver_name)
                            <span>{{ $ipt->driver_name }}</span>
                        @endif
                        @if($isNom && $ipt->capacity > 0)
                            <span>{{ number_format((float)$ipt->capacity, 0) }} capacity</span>
                        @elseif(!$isNom && $qty > 0)
                            <span>{{ number_format($qty, 0) }} loaded</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] {{ $muted }}">{{ $isNom ? 'Est. (capacity × rate)' : 'Projected (loaded × rate)' }}</div>
                    <div class="text-sm font-semibold text-amber-600 dark:text-amber-400">~ {{ $sym($cur) }}{{ number_format($proj, 2) }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if(count($trips) === 0 && count($importTrips) === 0)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} px-5 py-14 text-center">
        <div class="text-sm font-semibold {{ $fg }} mb-1">No trips yet</div>
        <div class="text-xs {{ $muted }} max-w-xs mx-auto">
            Trips appear here when a sale is posted or an import truck is delivered with this transporter. You can also give a
            <button type="button" onclick="openAdvanceModal()"
                    class="text-amber-600 dark:text-amber-300 underline underline-offset-2">general advance</button>
            before any trip is created.
        </div>
    </div>
@else
    {{-- ── Import truck trips (international transporter) ────────────── --}}
    @if(count($importTrips) > 0)
    <div class="space-y-3 @if(count($trips) > 0) mb-5 @endif">
        @if(count($trips) > 0)
        <div class="text-[10px] uppercase tracking-widest font-semibold {{ $muted }} px-1 mb-1">Import shipments</div>
        @endif
        @foreach($importTrips as $truckId => $itrip)
            @php
                $itruck    = $itrip['truck'];
                $ifreight  = $itrip['freight'];
                $ishort    = $itrip['short_charge'];
                $inet      = $itrip['net'];
                $ipurchase = $itrip['purchase'];
            @endphp
            <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
                <div class="flex items-start justify-between px-5 py-4 border-b {{ $border }} {{ $surface2 }} flex-wrap gap-2">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold {{ $fg }}">
                                {{ $itruck?->truck_reg ?? 'Truck #'.$truckId }}
                            </span>
                            @if($itruck?->trailer_reg)
                                <span class="text-[10px] {{ $muted }} font-mono">+ {{ $itruck->trailer_reg }}</span>
                            @endif
                            @if($ipurchase)
                                <a href="{{ route('purchases.show', $ipurchase->id) }}"
                                   class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30 hover:bg-purple-500/25 transition">
                                    {{ $ipurchase->reference }}
                                </a>
                            @endif
                            @if($itruck?->delivery_date)
                                <span class="text-[10px] {{ $muted }}">
                                    Delivered {{ \Carbon\Carbon::parse($itruck->delivery_date)->format('d M Y') }}
                                </span>
                            @endif
                        </div>
                        <div class="text-[11px] {{ $muted }} mt-0.5 flex items-center gap-2 flex-wrap">
                            @if($itruck?->qty_loaded)
                                <span>{{ number_format($itruck->qty_loaded, 0) }}L loaded</span>
                            @endif
                            @if($itruck?->qty_delivered)
                                <span>→ {{ number_format($itruck->qty_delivered, 0) }}L delivered</span>
                            @endif
                            @if($itruck?->driver_name)
                                <span>· {{ $itruck->driver_name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-right flex-wrap">
                        <div class="text-xs">
                            <div class="{{ $muted }} text-[10px]">Freight</div>
                            <div class="font-bold {{ $fg }}">{{ $sym($currency) }}{{ number_format($ifreight, 2) }}</div>
                        </div>
                        @if(abs($ishort) > 0.005)
                        <div class="text-xs">
                            <div class="{{ $muted }} text-[10px]">Short charge</div>
                            <div class="font-bold text-rose-500">− {{ $sym($currency) }}{{ number_format(abs($ishort), 2) }}</div>
                        </div>
                        @endif
                        <div class="text-xs border-l {{ $border }} pl-3">
                            <div class="{{ $muted }} text-[10px]">Net owed</div>
                            @if(abs($inet) < 0.005)
                                <div class="font-bold text-emerald-500">Settled</div>
                            @elseif($inet > 0)
                                <div class="font-bold text-amber-600 dark:text-amber-400">{{ $sym($currency) }}{{ number_format($inet, 2) }}</div>
                            @else
                                <div class="font-bold text-emerald-500">Overpaid {{ $sym($currency) }}{{ number_format(abs($inet), 2) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── Local sale trips ─────────────────────────────────────────── --}}
    @if(count($trips) > 0)
    @if(count($importTrips) > 0)
    <div class="text-[10px] uppercase tracking-widest font-semibold {{ $muted }} px-1 mb-1">Local deliveries</div>
    @endif
    <div class="space-y-3">
        @foreach($trips as $saleId => $trip)
            @php
                $sale     = $trip['sale'];
                $net      = $trip['net'];
                $freight  = $trip['freight'];
                $advTotal = $trip['advances_total'];
            @endphp
            <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
                {{-- Trip header --}}
                <div class="flex items-start justify-between px-5 py-4 border-b {{ $border }} {{ $surface2 }} flex-wrap gap-2">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold {{ $fg }}">
                                {{ $sale ? ($sale->reference ?: 'Sale #' . $sale->id) : 'Sale #' . $saleId }}
                            </span>
                            @if($sale)
                                <span class="text-[10px] {{ $muted }}">
                                    {{ $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') : '' }}
                                </span>
                                @if($sale->status === 'posted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">Posted</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30">Draft</span>
                                @endif
                            @endif
                        </div>
                        <div class="text-[11px] {{ $muted }} mt-0.5 flex items-center gap-2 flex-wrap">
                            @if($sale?->product)
                                <span>{{ $sale->product->name }}</span>
                            @endif
                            @if($sale?->qty)
                                <span>{{ number_format($sale->qty, 0) }}L</span>
                            @endif
                            @if($sale?->depot)
                                <span>→ {{ $sale->depot->name }}</span>
                            @endif
                            @if($sale?->truck_no)
                                <span class="font-mono">{{ $sale->truck_no }}</span>
                            @endif
                            @if($sale?->driver_name)
                                <span>{{ $sale->driver_name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-right flex-wrap">
                        <div class="text-xs">
                            <div class="{{ $muted }} text-[10px]">Freight</div>
                            <div class="font-bold {{ $fg }}">{{ $sym($currency) }}{{ number_format($freight, 2) }}</div>
                        </div>
                        @if($advTotal > 0)
                        <div class="text-xs">
                            <div class="{{ $muted }} text-[10px]">Advances</div>
                            <div class="font-bold text-amber-500">− {{ $sym($currency) }}{{ number_format($advTotal, 2) }}</div>
                        </div>
                        @endif
                        <div class="text-xs border-l {{ $border }} pl-3">
                            <div class="{{ $muted }} text-[10px]">Net owed</div>
                            @if(abs($net) < 0.005)
                                <div class="font-bold text-emerald-500">Settled</div>
                            @elseif($net > 0)
                                <div class="font-bold text-amber-600 dark:text-amber-400">{{ $sym($currency) }}{{ number_format($net, 2) }}</div>
                            @else
                                <div class="font-bold text-emerald-500">Overpaid {{ $sym($currency) }}{{ number_format(abs($net), 2) }}</div>
                            @endif
                        </div>
                        <button type="button"
                                onclick="openAdvanceModal({{ $saleId }}, '{{ addslashes($sale ? ($sale->reference ?: 'Sale #' . $sale->id) : 'Sale #' . $saleId) }}')"
                                class="inline-flex items-center gap-1 h-8 px-2.5 rounded-lg border border-amber-500/30 bg-amber-500/10 text-[11px] font-semibold text-amber-600 dark:text-amber-300 hover:bg-amber-500/20 transition">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add advance
                        </button>
                    </div>
                </div>

                {{-- Advances for this trip --}}
                @if($trip['advances']->isNotEmpty())
                <div class="divide-y divide-[color:var(--tw-border)]">
                    @foreach($trip['advances'] as $adv)
                        @php
                            $am = $advanceTypeMeta[$adv->advance_type ?? 'other'] ?? $advanceTypeMeta['other'];
                        @endphp
                        <div class="flex items-center justify-between px-5 py-3 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $am['color'] }}">
                                    {{ $am['label'] }}
                                </span>
                                <span class="text-xs {{ $fg }}">{{ $adv->description }}</span>
                                <span class="text-[10px] {{ $muted }}">{{ $adv->entry_date->format('d M Y') }}</span>
                            </div>
                            <span class="text-xs font-semibold text-rose-500">− {{ $sym($adv->currency) }}{{ number_format(abs($adv->amount), 2) }}</span>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="px-5 py-3 text-[11px] {{ $muted }} italic">No advances recorded for this trip.</div>
                @endif
            </div>
        @endforeach
    </div>
    @endif {{-- /count($trips) > 0 --}}
@endif {{-- /trips or importTrips exist --}}

{{-- General advances (not linked to a trip) --}}
@php
    $generalAdvances = $entries->getCollection()
        ->where('type', 'advance')
        ->whereNull('sale_id');
@endphp
@if($generalAdvances->isNotEmpty())
<div class="mt-4 rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-xs font-semibold {{ $fg }}">General advances</div>
        <div class="text-[10px] {{ $muted }}">Not linked to a specific trip</div>
    </div>
    <div class="divide-y divide-[color:var(--tw-border)]">
        @foreach($generalAdvances as $adv)
            @php $am = $advanceTypeMeta[$adv->advance_type ?? 'general'] ?? $advanceTypeMeta['general']; @endphp
            <div class="flex items-center justify-between px-5 py-3 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $am['color'] }}">
                        {{ $am['label'] }}
                    </span>
                    <span class="text-xs {{ $fg }}">{{ $adv->description }}</span>
                    <span class="text-[10px] {{ $muted }}">{{ $adv->entry_date->format('d M Y') }}</span>
                </div>
                <span class="text-xs font-semibold text-rose-500">− {{ $sym($adv->currency) }}{{ number_format(abs($adv->amount), 2) }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ══ LEDGER TAB ══ --}}
@else

<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-sm font-semibold {{ $fg }}">All ledger entries</div>
        <div class="text-xs {{ $muted }}">{{ $entries->total() }} {{ $entries->total() === 1 ? 'entry' : 'entries' }}</div>
    </div>

    @if($entries->isEmpty())
        <div class="px-5 py-12 text-center">
            <div class="text-xs {{ $muted }}">No entries yet.</div>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="{{ $muted }} border-b {{ $border }} {{ $surface2 }}">
                        <th class="text-left py-2.5 pl-5 pr-3 font-semibold">Date</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Type</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Description</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Trip</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Ref</th>
                        <th class="text-right py-2.5 pr-5 font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $meta     = $typeMeta[$entry->type] ?? ['label' => ucfirst($entry->type), 'color' => 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border];
                            $isDebit  = $entry->amount > 0;
                            $linkKey  = $entry->ref_type && $entry->ref_id ? $entry->ref_type . ':' . $entry->ref_id : null;
                            $refUrl   = $linkKey ? ($refLinks[$linkKey] ?? null) : null;
                            $refLabel = $entry->ref_type ? (class_basename($entry->ref_type) . ' #' . $entry->ref_id) : null;

                            // For advances with an advance_type, show that as a sub-label
                            $advMeta = $entry->type === 'advance' && $entry->advance_type
                                ? ($advanceTypeMeta[$entry->advance_type] ?? null)
                                : null;
                        @endphp
                        <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                            <td class="py-2.5 pl-5 pr-3 {{ $muted }} whitespace-nowrap">
                                {{ $entry->entry_date->format('d M Y') }}
                            </td>
                            <td class="py-2.5 pr-3 whitespace-nowrap">
                                @if($advMeta)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $advMeta['color'] }}">
                                        {{ $advMeta['label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $meta['color'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-3 {{ $fg }} max-w-xs">
                                {{ $entry->description }}
                            </td>
                            <td class="py-2.5 pr-3 whitespace-nowrap">
                                @if($entry->sale_id)
                                    <a href="{{ route('sales.index', ['sale' => $entry->sale_id]) }}"
                                       class="font-mono text-[10px] text-[color:var(--tw-accent)] hover:underline">
                                        Sale #{{ $entry->sale_id }}
                                    </a>
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-3 whitespace-nowrap">
                                @if($refLabel)
                                    @if($refUrl)
                                        <a href="{{ $refUrl }}"
                                           class="font-mono text-[10px] text-[color:var(--tw-accent)] hover:underline">
                                            {{ $refLabel }}
                                        </a>
                                    @else
                                        <span class="font-mono text-[10px] {{ $muted }}">{{ $refLabel }}</span>
                                    @endif
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-5 text-right font-semibold whitespace-nowrap">
                                @if($isDebit)
                                    <span class="{{ $fg }}">{{ $sym($entry->currency) }}{{ number_format($entry->amount, 2) }}</span>
                                @else
                                    <span class="text-rose-500">− {{ $sym($entry->currency) }}{{ number_format(abs($entry->amount), 2) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="px-5 py-3 border-t {{ $border }} {{ $surface2 }}">
                {{ $entries->links() }}
            </div>
        @endif
    @endif
</div>

@endif

{{-- ── Record advance modal ── --}}
<div id="advanceModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
            <div>
                <div class="text-sm font-semibold {{ $fg }}">Record advance</div>
                <div id="advanceModalSubtitle" class="text-[10px] {{ $muted }}">Cash paid out before freight is fully earned</div>
            </div>
            <button type="button" onclick="closeAdvanceModal()"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('transporters.advances.store', $transporter) }}" class="p-5 space-y-4">
            @csrf

            {{-- Hidden sale_id, populated by JS --}}
            <input type="hidden" name="sale_id" id="advanceSaleId" value="">

            {{-- Trip indicator (shown when advance is linked to a trip) --}}
            <div id="advanceTripRow" class="hidden rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2.5 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 1 2-1m2 0h4m4 0l2 1 1-1V8h-3l-2-3h-4"/>
                    </svg>
                    <span id="advanceTripLabel" class="text-xs font-semibold text-amber-600 dark:text-amber-300"></span>
                </div>
                <button type="button" onclick="clearTripFromAdvanceModal()"
                        class="text-[10px] text-amber-600 dark:text-amber-300 underline underline-offset-2">
                    Change to general
                </button>
            </div>

            {{-- Trip selector (shown when no trip pre-selected) --}}
            <div id="advanceTripSelectRow" class="{{ count($openSales) > 0 ? '' : 'hidden' }}">
                <label class="block text-xs font-semibold {{ $fg }} mb-1">
                    Link to a trip <span class="{{ $muted }}">(optional)</span>
                </label>
                <select id="advanceTripSelect" onchange="onTripSelectChange(this)"
                        class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                    <option value="">— General advance (not trip-specific) —</option>
                    @foreach($openSales as $s)
                        @php
                            $sLabel = ($s->reference ?: 'Sale #' . $s->id)
                                . ($s->product ? ' · ' . $s->product->name : '')
                                . ($s->qty ? ' · ' . number_format($s->qty, 0) . 'L' : '')
                                . ($s->depot ? ' → ' . $s->depot->name : '');
                        @endphp
                        <option value="{{ $s->id }}" data-label="{{ $s->reference ?: 'Sale #' . $s->id }}">
                            {{ $sLabel }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] {{ $muted }} mt-1">Linking to a trip shows this advance in the trip breakdown.</p>
            </div>

            {{-- Advance type --}}
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Advance type</label>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach([
                        'trip'    => 'Trip advance',
                        'fuel'    => 'Fuel advance',
                        'driver'  => 'Driver advance',
                        'general' => 'General',
                        'other'   => 'Other',
                    ] as $val => $lbl)
                    <label class="flex items-center gap-2 h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} cursor-pointer text-xs {{ $fg }}">
                        <input type="radio" name="advance_type" value="{{ $val }}"
                               class="accent-amber-500"
                               {{ $val === 'trip' ? 'checked' : '' }}>
                        {{ $lbl }}
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Amount --}}
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Amount</label>
                <div class="flex items-center gap-2">
                    <span class="h-10 px-3 flex items-center rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} whitespace-nowrap select-none">
                        {{ $sym($currency) }}{{ $currency }}
                    </span>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
                </div>
            </div>

            {{-- Date --}}
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Date</label>
                <input type="date" name="entry_date" required value="{{ date('Y-m-d') }}"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            {{-- Note --}}
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Note <span class="{{ $muted }}">(optional)</span></label>
                <input type="text" name="description" placeholder="e.g. Driver cash before Lubumbashi trip"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            {{-- Petty cash source --}}
            @if($pettyCashAccounts->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Pay from petty cash <span class="{{ $muted }}">(optional)</span></label>
                <select name="petty_cash_account_id"
                        class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40">
                    <option value="">— Record cash separately —</option>
                    @foreach($pettyCashAccounts as $pca)
                        <option value="{{ $pca->id }}">{{ $pca->name }} ({{ $pca->currency }})</option>
                    @endforeach
                </select>
                <p class="text-[10px] {{ $muted }} mt-1">If selected, deducted from that cash float automatically.</p>
            </div>
            @endif

            @if($errors->any())
                <div class="text-xs text-rose-500 space-y-0.5">
                    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="closeAdvanceModal()"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-amber-500/40 bg-amber-500/10 text-xs font-semibold text-amber-600 dark:text-amber-300 hover:bg-amber-500/20 transition">
                    Save advance
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Adjustment modal ── --}}
<div id="adjustmentModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
            <div>
                <div class="text-sm font-semibold {{ $fg }}">Record adjustment</div>
                <div class="text-[10px] {{ $muted }}">Debit = adds to what we owe · Credit = reduces what we owe</div>
            </div>
            <button type="button" onclick="closeAdjustmentModal()"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('transporters.adjustments.store', $transporter) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Direction</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} cursor-pointer text-xs font-semibold {{ $fg }}">
                        <input type="radio" name="direction" value="debit" checked class="accent-amber-500"> Debit (we owe more)
                    </label>
                    <label class="flex items-center gap-2 h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} cursor-pointer text-xs font-semibold {{ $fg }}">
                        <input type="radio" name="direction" value="credit" class="accent-emerald-500"> Credit (we owe less)
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Amount</label>
                <div class="flex items-center gap-2">
                    <span class="h-10 px-3 flex items-center rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} whitespace-nowrap select-none">
                        {{ $sym($currency) }}{{ $currency }}
                    </span>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Date</label>
                <input type="date" name="entry_date" required value="{{ date('Y-m-d') }}"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Reason <span class="text-rose-400">*</span></label>
                <input type="text" name="description" required placeholder="e.g. Overcharge correction"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="closeAdjustmentModal()"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save adjustment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Record payment modal ── --}}
<div id="paymentModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
            <div class="text-sm font-semibold {{ $fg }}">Record payment</div>
            <button type="button" onclick="closePaymentModal()"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('transporters.payments.store', $transporter) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Amount</label>
                <div class="flex items-center gap-2">
                    <span class="h-10 px-3 flex items-center rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} whitespace-nowrap select-none">
                        {{ $sym($currency) }}{{ $currency }}
                    </span>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           placeholder="0.00"
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Payment date</label>
                <input type="date" name="entry_date" required
                       value="{{ date('Y-m-d') }}"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Note <span class="{{ $muted }}">(optional)</span></label>
                <input type="text" name="description"
                       placeholder="e.g. Bank transfer ref #12345"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            {{-- Pay from: bank or petty cash --}}
            <div class="rounded-xl border {{ $border }} p-3 space-y-3" style="background:var(--tw-surface-2)">
                <div class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">Pay from <span class="font-normal normal-case opacity-60">(optional)</span></div>
                @if($bankAccounts->isNotEmpty())
                <div>
                    <label class="text-xs font-semibold {{ $fg }} mb-1 block">Bank account</label>
                    <select name="bank_account_id" id="trp-pay-bank"
                            class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                            onchange="if(this.value)document.getElementById('trp-pay-pca').value=''">
                        <option value="">— none —</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->name }} ({{ $ba->currency }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($pettyCashAccounts->isNotEmpty())
                <div>
                    <label class="text-xs font-semibold {{ $fg }} mb-1 block">Petty cash</label>
                    <select name="petty_cash_account_id" id="trp-pay-pca"
                            class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                            onchange="if(this.value)document.getElementById('trp-pay-bank').value=''">
                        <option value="">— none —</option>
                        @foreach($pettyCashAccounts as $pca)
                            <option value="{{ $pca->id }}">{{ $pca->name }} ({{ $pca->currency }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if($bankAccounts->isEmpty() && $pettyCashAccounts->isEmpty())
                    <p class="text-xs {{ $muted }}">No bank or petty cash accounts set up yet.</p>
                @endif
            </div>
            @if($errors->any())
                <div class="text-xs text-rose-500 space-y-0.5">
                    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            @endif
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="closePaymentModal()"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save payment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openPaymentModal()    { document.getElementById('paymentModal').classList.remove('hidden'); }
function closePaymentModal()   { document.getElementById('paymentModal').classList.add('hidden'); }
function openAdjustmentModal() { document.getElementById('adjustmentModal').classList.remove('hidden'); }
function closeAdjustmentModal(){ document.getElementById('adjustmentModal').classList.add('hidden'); }

function openAdvanceModal(saleId, saleLabel) {
    const modal = document.getElementById('advanceModal');
    modal.classList.remove('hidden');

    if (saleId) {
        // Pre-link to this trip
        document.getElementById('advanceSaleId').value = saleId;
        document.getElementById('advanceTripLabel').textContent = saleLabel || ('Sale #' + saleId);
        document.getElementById('advanceTripRow').classList.remove('hidden');
        document.getElementById('advanceTripSelectRow').classList.add('hidden');
        document.getElementById('advanceModalSubtitle').textContent = 'Advance for: ' + (saleLabel || 'Trip #' + saleId);
    } else {
        // General / user picks trip from dropdown
        document.getElementById('advanceSaleId').value = '';
        document.getElementById('advanceTripRow').classList.add('hidden');
        document.getElementById('advanceTripSelectRow').classList.remove('hidden');
        document.getElementById('advanceModalSubtitle').textContent = 'Cash paid out before freight is fully earned';
        // Reset select
        const sel = document.getElementById('advanceTripSelect');
        if (sel) sel.value = '';
    }
}

function closeAdvanceModal() {
    document.getElementById('advanceModal').classList.add('hidden');
}

function clearTripFromAdvanceModal() {
    document.getElementById('advanceSaleId').value = '';
    document.getElementById('advanceTripRow').classList.add('hidden');
    document.getElementById('advanceTripSelectRow').classList.remove('hidden');
    document.getElementById('advanceModalSubtitle').textContent = 'Cash paid out before freight is fully earned';
}

function onTripSelectChange(sel) {
    document.getElementById('advanceSaleId').value = sel.value;
}

['paymentModal','advanceModal','adjustmentModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});

@if($errors->any())
openPaymentModal();
@endif

function openSettleModal()  { document.getElementById('settleModal').classList.remove('hidden'); }
function closeSettleModal() { document.getElementById('settleModal').classList.add('hidden'); }
document.getElementById('settleModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
@endpush

{{-- ── Settle Account Modal ─────────────────────────────────────────────── --}}
<div id="settleModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="relative w-full max-w-md mx-4 rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6">
        <h3 class="text-sm font-bold {{ $fg }} mb-1">Settle Account</h3>
        <p class="text-xs {{ $muted }} mb-5">Records a final entry that zeros the current balance, marking the account as fully settled.</p>

        <form method="POST" action="{{ route('transporters.settle', $transporter) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold {{ $muted }} mb-1.5">Settlement Date</label>
                    <input type="date" name="settlement_date" value="{{ now()->format('Y-m-d') }}" required
                           class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-xs {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/30">
                </div>
                <div>
                    <label class="block text-xs font-semibold {{ $muted }} mb-1.5">Note (optional)</label>
                    <input type="text" name="note" placeholder="e.g. Full and final settlement"
                           class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-xs {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/30">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeSettleModal()"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-teal-500/40 bg-teal-500/10 text-xs font-semibold text-teal-600 dark:text-teal-300 hover:bg-teal-500/20 transition">
                    Settle Account
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
