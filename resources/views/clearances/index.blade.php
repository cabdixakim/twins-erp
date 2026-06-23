@extends('layouts.app')

@section('title', 'Clearances')

@section('content')
@php
    $isDark  = true;
    $bg      = $isDark ? 'bg-[#0f1724]'  : 'bg-[#f4f6fb]';
    $surface = $isDark ? 'bg-[#1a2537]'  : 'bg-white';
    $surface2= $isDark ? 'bg-[#212f45]'  : 'bg-[#f4f6fb]';
    $border  = $isDark ? 'border-white/8' : 'border-black/8';
    $fg      = $isDark ? 'text-white'     : 'text-[#0f1724]';
    $muted   = $isDark ? 'text-white/45'  : 'text-[#0f1724]/45';
    $muted60 = $isDark ? 'text-white/60'  : 'text-[#0f1724]/60';

    $statusMeta = [
        'in_transit'     => ['label' => 'In Transit',     'dot' => 'bg-amber-400',   'pill' => 'bg-amber-400/15 text-amber-300'],
        'border_cleared' => ['label' => 'Border Cleared', 'dot' => 'bg-purple-400',  'pill' => 'bg-purple-400/15 text-purple-300'],
        'loaded'         => ['label' => 'Loaded',         'dot' => 'bg-blue-400',    'pill' => 'bg-blue-400/15 text-blue-300'],
        'nominated'      => ['label' => 'Nominated',      'dot' => 'bg-slate-400',   'pill' => 'bg-slate-400/15 text-slate-300'],
        'delivered'      => ['label' => 'Delivered',      'dot' => 'bg-emerald-400', 'pill' => 'bg-emerald-400/15 text-emerald-300'],
        'loading_failed' => ['label' => 'Load Failed',    'dot' => 'bg-rose-400',    'pill' => 'bg-rose-400/15 text-rose-300'],
    ];

    $dutyMeta = [
        'pending'  => ['label' => 'Pending',  'pill' => 'bg-amber-400/15 text-amber-300'],
        'posted'   => ['label' => 'Posted',   'pill' => 'bg-emerald-400/15 text-emerald-300'],
        'waived'   => ['label' => 'Waived',   'pill' => 'bg-slate-400/15 text-slate-300'],
        'na'       => ['label' => 'N/A',      'pill' => 'bg-slate-400/10 text-white/30'],
    ];

    $tabs = [
        'all'            => ['label' => 'All',           'count' => $totalCount],
        'in_transit'     => ['label' => 'In Transit',    'count' => $counts['in_transit']     ?? 0],
        'border_cleared' => ['label' => 'Border Cleared','count' => $counts['border_cleared'] ?? 0],
        'loaded'         => ['label' => 'Loaded',        'count' => $counts['loaded']         ?? 0],
        'nominated'      => ['label' => 'Nominated',     'count' => $counts['nominated']      ?? 0],
        'delivered'      => ['label' => 'Delivered',     'count' => $counts['delivered']      ?? 0],
        'loading_failed' => ['label' => 'Failed',        'count' => $counts['loading_failed'] ?? 0],
    ];
@endphp

<div class="p-4 sm:p-6 space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold {{ $fg }}">Clearances</h1>
            <p class="{{ $muted }} text-sm mt-0.5">All import trucks across every shipment — border status at a glance</p>
        </div>
    </div>

    {{-- SEARCH + FILTER BAR --}}
    <form method="GET" action="{{ route('clearances.index') }}" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                   placeholder="Truck reg, TR8, T1, PO ref…"
                   class="w-full h-9 rounded-xl border {{ $border }} {{ $surface2 }} pl-9 pr-3 text-sm {{ $fg }} placeholder:{{ $muted }} focus:outline-none focus:ring-1 focus:ring-white/20"
                   onchange="this.form.submit()">
        </div>
        @if($search)
            <a href="{{ route('clearances.index', ['status' => $status]) }}"
               class="flex items-center gap-1.5 h-9 px-3 rounded-xl text-sm {{ $muted }} {{ $surface2 }} border {{ $border }} hover:text-white transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Clear
            </a>
        @endif
    </form>

    {{-- STATUS TABS --}}
    <div class="flex gap-1 flex-wrap">
        @foreach($tabs as $key => $tab)
            @if($tab['count'] > 0 || $key === 'all')
            <a href="{{ route('clearances.index', array_filter(['status' => $key === 'all' ? null : $key, 'search' => $search ?: null])) }}"
               class="flex items-center gap-1.5 px-3 h-8 rounded-lg text-sm font-medium transition-colors
                      {{ $status === $key
                           ? 'bg-white/12 '.$fg
                           : $muted.' hover:bg-white/6 hover:text-white/80' }}">
                @if($key !== 'all' && isset($statusMeta[$key]))
                    <span class="w-1.5 h-1.5 rounded-full {{ $statusMeta[$key]['dot'] }}"></span>
                @endif
                {{ $tab['label'] }}
                <span class="text-xs {{ $status === $key ? 'text-white/60' : 'text-white/30' }}">{{ $tab['count'] }}</span>
            </a>
            @endif
        @endforeach
    </div>

    {{-- TABLE --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        @if($trucks->isEmpty())
            <div class="py-16 text-center">
                <svg class="w-10 h-10 mx-auto {{ $muted }} mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <p class="{{ $muted }} text-sm">No trucks found
                    @if($search) for "<strong class="text-white/60">{{ $search }}</strong>"@endif
                    @if($status !== 'all') with status <strong class="text-white/60">{{ $statusMeta[$status]['label'] ?? $status }}</strong>@endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b {{ $border }}">
                            <th class="text-left font-semibold {{ $muted }} px-4 py-3 whitespace-nowrap">Truck / Trailer</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Shipment</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Transporter</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Product</th>
                            <th class="text-right font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Qty Loaded</th>
                            <th class="text-right font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Qty Delivered</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Border Date</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">TR8</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">T1</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Duty</th>
                            <th class="text-left font-semibold {{ $muted }} px-3 py-3 whitespace-nowrap">Status</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y {{ $border }}">
                        @foreach($trucks as $truck)
                        @php
                            $nom      = $truck->nomination;
                            $purchase = $nom?->purchase;
                            $unitLabel = ($nom->volume_unit ?? 'L') === 'M3' ? 'M³' : 'L';

                            $sm   = $statusMeta[$truck->status] ?? ['label' => ucfirst($truck->status), 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-400/15 text-slate-300'];

                            if (! $truck->duty_rate_per_1000l && ! $truck->duty_amount) {
                                $dutyKey = 'na';
                            } elseif ($truck->duty_amount == 0 && $truck->duty_rate_per_1000l == 0) {
                                $dutyKey = 'waived';
                            } elseif ($truck->duty_status === 'posted') {
                                $dutyKey = 'posted';
                            } else {
                                $dutyKey = 'pending';
                            }
                            $dm = $dutyMeta[$dutyKey];
                        @endphp
                        <tr class="hover:bg-white/3 transition-colors group">
                            {{-- Truck / Trailer --}}
                            <td class="px-4 py-3">
                                <div class="font-semibold {{ $fg }}">{{ $truck->truck_reg }}</div>
                                @if($truck->trailer_reg)
                                    <div class="text-xs {{ $muted }}">{{ $truck->trailer_reg }}</div>
                                @endif
                                @if($truck->driver_name)
                                    <div class="text-xs {{ $muted60 }} mt-0.5">{{ $truck->driver_name }}</div>
                                @endif
                            </td>

                            {{-- Shipment --}}
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($purchase)
                                    <a href="{{ route('purchases.show', $purchase) }}"
                                       class="font-mono text-xs font-semibold text-[color:var(--tw-accent)] hover:underline">
                                        {{ $purchase->reference }}
                                    </a>
                                    @if($purchase->supplier)
                                        <div class="text-xs {{ $muted }} mt-0.5">{{ $purchase->supplier->name }}</div>
                                    @endif
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>

                            {{-- Transporter --}}
                            <td class="px-3 py-3">
                                <span class="{{ $fg }} text-xs">{{ $nom?->transporter?->name ?? '—' }}</span>
                            </td>

                            {{-- Product --}}
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="{{ $fg }} text-xs font-medium">{{ $purchase?->product?->name ?? '—' }}</span>
                            </td>

                            {{-- Qty Loaded --}}
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                @if($truck->qty_loaded)
                                    <span class="{{ $fg }} font-mono text-xs">{{ number_format($truck->qty_loaded, 0) }}</span>
                                    <span class="{{ $muted }} text-xs"> {{ $unitLabel }}</span>
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>

                            {{-- Qty Delivered --}}
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                @if($truck->qty_delivered !== null)
                                    <span class="{{ $fg }} font-mono text-xs">{{ number_format($truck->qty_delivered, 0) }}</span>
                                    <span class="{{ $muted }} text-xs"> {{ $unitLabel }}</span>
                                    @if(($truck->shortfall_qty ?? 0) > 0)
                                        <div class="text-xs text-rose-400 font-mono">-{{ number_format($truck->shortfall_qty, 0) }} loss</div>
                                    @endif
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>

                            {{-- Border Date --}}
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($truck->border_date)
                                    <span class="{{ $fg }} text-xs">{{ $truck->border_date->format('d M Y') }}</span>
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>

                            {{-- TR8 --}}
                            <td class="px-3 py-3">
                                @if($truck->tr8_number)
                                    <span class="font-mono text-xs {{ $fg }} select-all">{{ $truck->tr8_number }}</span>
                                @else
                                    @if(in_array($truck->status, ['border_cleared','delivered']))
                                        <span class="text-xs text-rose-400">Missing</span>
                                    @else
                                        <span class="{{ $muted }}">—</span>
                                    @endif
                                @endif
                            </td>

                            {{-- T1 --}}
                            <td class="px-3 py-3">
                                @if($truck->t1_number)
                                    <span class="font-mono text-xs {{ $fg }} select-all">{{ $truck->t1_number }}</span>
                                @else
                                    @if(in_array($truck->status, ['border_cleared','delivered']))
                                        <span class="text-xs text-rose-400">Missing</span>
                                    @else
                                        <span class="{{ $muted }}">—</span>
                                    @endif
                                @endif
                            </td>

                            {{-- Duty --}}
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $dm['pill'] }}">
                                    {{ $dm['label'] }}
                                </span>
                                @if($truck->duty_amount && $truck->duty_amount > 0)
                                    <div class="text-xs {{ $muted }} mt-0.5 font-mono">
                                        {{ $truck->duty_currency ?? 'USD' }} {{ number_format($truck->duty_amount, 2) }}
                                    </div>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-medium {{ $sm['pill'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sm['dot'] }}"></span>
                                    {{ $sm['label'] }}
                                </span>
                                @if($truck->delivery_date)
                                    <div class="text-xs {{ $muted }} mt-0.5">{{ $truck->delivery_date->format('d M Y') }}</div>
                                @elseif($truck->pickup_date)
                                    <div class="text-xs {{ $muted }} mt-0.5">Loaded {{ $truck->pickup_date->format('d M') }}</div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-3 py-3 text-right">
                                @if($purchase)
                                    <a href="{{ route('purchases.show', $purchase) }}"
                                       class="inline-flex items-center gap-1 text-xs {{ $muted }} hover:text-white transition-colors opacity-0 group-hover:opacity-100">
                                        View
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if($trucks->hasPages())
                <div class="px-4 py-3 border-t {{ $border }}">
                    {{ $trucks->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- LEGEND --}}
    <div class="flex flex-wrap gap-x-5 gap-y-1.5">
        <span class="text-xs {{ $muted }}">Status:</span>
        @foreach($statusMeta as $key => $meta)
            <span class="flex items-center gap-1.5 text-xs {{ $muted }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                {{ $meta['label'] }}
            </span>
        @endforeach
    </div>

</div>
@endsection
