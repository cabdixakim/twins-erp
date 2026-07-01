@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    // Fix #4 — currency symbol mapping
    $currencySymbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
    ];
    $sym = fn(string $code) => $currencySymbols[$code] ?? ($code . ' ');
@endphp

@extends('layouts.app')

@section('title', 'Transporters')
@section('subtitle', 'Freight partners — balances and payment records.')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

{{-- Header row --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-lg font-bold {{ $fg }}">Transporters</h1>
        <p class="text-xs {{ $muted }} mt-0.5">Click a transporter to view its ledger and record payments.</p>
    </div>
    <a href="{{ route('settings.transporters.index') }}"
       class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        Manage transporters
    </a>
</div>

@if($transporters->isEmpty())
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-12 text-center">
        <svg class="w-10 h-10 mx-auto mb-3 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0121 11.414V16a1 1 0 01-1 1h-1"/>
        </svg>
        <div class="text-sm font-semibold {{ $fg }} mb-1">No active transporters</div>
        <div class="text-xs {{ $muted }} mb-4">Add transport partners in settings to track freight and payments here.</div>
        <a href="{{ route('settings.transporters.index') }}"
           class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            Go to transporter settings
        </a>
    </div>
@else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b {{ $border }} {{ $surface2 }} text-xs {{ $muted }}">
                    <th class="text-left py-3 pl-5 pr-3 font-semibold">Transporter</th>
                    <th class="text-left py-3 pr-3 font-semibold">Type</th>
                    <th class="text-right py-3 pr-3 font-semibold">Freight earned</th>
                    <th class="text-right py-3 pr-5 font-semibold">Net payable</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transporters as $tp)
                    @php
                        $bal       = (float) ($balances[$tp->id] ?? 0);
                        $freight   = (float) ($freightTotals[$tp->id] ?? 0);
                        $cur       = $tp->default_currency ?: 'USD';
                        $projected = (float) ($projectedPayables[$tp->id] ?? 0);
                        $projCur   = $projectedCurrencies[$tp->id] ?? $cur;
                    @endphp
                    <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                        <td class="py-3 pl-5 pr-3">
                            <a href="{{ route('transporters.show', $tp) }}"
                               class="font-semibold {{ $fg }} hover:text-[color:var(--tw-accent)] transition">
                                {{ $tp->name }}
                            </a>
                            @if($tp->contact_person)
                                <div class="text-xs {{ $muted }}">{{ $tp->contact_person }}</div>
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-xs {{ $muted }}">
                            {{ $tp->type === 'intl' ? 'International' : ($tp->type === 'local' ? 'Local' : '—') }}
                        </td>
                        <td class="py-3 pr-3 text-right text-xs {{ $muted }}">
                            {{ $sym($cur) }}{{ number_format($freight, 2) }}
                        </td>
                        <td class="py-3 pr-5 text-right">
                            @if(abs($bal) < 0.005)
                                <span class="text-xs {{ $muted }}">Settled</span>
                            @elseif($bal > 0)
                                <span class="text-sm font-bold text-amber-500">{{ $sym($cur) }}{{ number_format($bal, 2) }}</span>
                            @else
                                <span class="text-xs text-emerald-500 font-semibold">Overpaid {{ $sym($cur) }}{{ number_format(abs($bal), 2) }}</span>
                            @endif
                            @if($projected > 0.005)
                                <div class="text-[10px] text-amber-500/70 mt-0.5">~ {{ $sym($projCur) }}{{ number_format($projected, 2) }} projected</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
