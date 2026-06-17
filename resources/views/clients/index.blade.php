@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $sym = fn(string $code) => match($code) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ',
        default => $code . ' ',
    };
@endphp

@extends('layouts.app')
@section('title', 'Clients — AR')
@section('subtitle', 'Accounts receivable · outstanding balances')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

{{-- Header --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-2">
    <div>
        <h1 class="text-lg font-bold {{ $fg }}">Clients</h1>
        <p class="text-xs {{ $muted }} mt-0.5">Click a client to view their AR ledger and record payments.</p>
    </div>
    <a href="{{ route('settings.clients.index') }}"
       class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        Manage clients
    </a>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 col-span-2 sm:col-span-1">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Total Outstanding AR</div>
        <div class="text-xl font-bold text-emerald-500">{{ number_format($totalAR, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Across all active clients</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Active clients</div>
        <div class="text-xl font-bold {{ $fg }}">{{ $clients->count() }}</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">With open balance</div>
        <div class="text-xl font-bold text-amber-500">
            {{ $clients->filter(fn($c) => array_sum($balances[$c->id] ?? []) > 0.005)->count() }}
        </div>
    </div>
</div>

@if($clients->isEmpty())
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-12 text-center">
        <svg class="w-10 h-10 mx-auto mb-3 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <div class="text-sm font-semibold {{ $fg }} mb-1">No active clients</div>
        <div class="text-xs {{ $muted }} mb-4">Add clients in settings, then link them to sales for AR tracking.</div>
        <a href="{{ route('settings.clients.index') }}"
           class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            Go to client settings
        </a>
    </div>
@else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b {{ $border }} {{ $surface2 }} text-xs {{ $muted }}">
                    <th class="text-left py-3 pl-5 pr-3 font-semibold">Client</th>
                    <th class="text-left py-3 pr-3 font-semibold hidden sm:table-cell">Type</th>
                    <th class="text-right py-3 pr-3 font-semibold hidden lg:table-cell">Current</th>
                    <th class="text-right py-3 pr-3 font-semibold hidden lg:table-cell">1–30d</th>
                    <th class="text-right py-3 pr-3 font-semibold hidden lg:table-cell">31–60d</th>
                    <th class="text-right py-3 pr-3 font-semibold hidden lg:table-cell">60d+</th>
                    <th class="text-right py-3 pr-3 font-semibold">Invoiced</th>
                    <th class="text-right py-3 pr-3 font-semibold">Outstanding</th>
                    <th class="py-3 pr-5"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($clients as $client)
                    @php
                        $cur      = $client->currency ?: 'USD';
                        $balance  = (float) ($balances[$client->id][$cur] ?? array_sum($balances[$client->id] ?? []));
                        $invoiced = (float) ($invoicedTotals[$client->id][$cur] ?? array_sum($invoicedTotals[$client->id] ?? []));
                        $s        = $sym($cur);
                        $a        = $aging[$client->id] ?? null;
                        $aCurrent = $a ? (float)$a->bucket_current : 0;
                        $a1_30    = $a ? (float)$a->bucket_1_30 : 0;
                        $a31_60   = $a ? (float)$a->bucket_31_60 : 0;
                        $a60plus  = $a ? (float)$a->bucket_60_plus : 0;
                    @endphp
                    <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                        <td class="py-3 pl-5 pr-3">
                            <a href="{{ route('clients.show', $client) }}"
                               class="font-semibold {{ $fg }} hover:text-[color:var(--tw-accent)] transition">
                                {{ $client->name }}
                            </a>
                            @if($client->code)
                                <span class="ml-1 text-[10px] {{ $muted }}">{{ $client->code }}</span>
                            @endif
                            @if($client->contact_person)
                                <div class="text-[10px] {{ $muted }}">{{ $client->contact_person }}</div>
                            @endif
                        </td>
                        <td class="py-3 pr-3 {{ $muted }} hidden sm:table-cell text-xs">{{ $client->type ?: '—' }}</td>

                        {{-- Aging buckets --}}
                        <td class="py-3 pr-3 text-right text-xs hidden lg:table-cell">
                            @if($aCurrent > 0.005)
                                <span class="text-emerald-400 font-medium">{{ $s }}{{ number_format($aCurrent, 0) }}</span>
                            @else
                                <span class="{{ $muted }}">—</span>
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-right text-xs hidden lg:table-cell">
                            @if($a1_30 > 0.005)
                                <span class="text-amber-400 font-medium">{{ $s }}{{ number_format($a1_30, 0) }}</span>
                            @else
                                <span class="{{ $muted }}">—</span>
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-right text-xs hidden lg:table-cell">
                            @if($a31_60 > 0.005)
                                <span class="text-orange-400 font-medium">{{ $s }}{{ number_format($a31_60, 0) }}</span>
                            @else
                                <span class="{{ $muted }}">—</span>
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-right text-xs hidden lg:table-cell">
                            @if($a60plus > 0.005)
                                <span class="text-rose-400 font-bold">{{ $s }}{{ number_format($a60plus, 0) }}</span>
                            @else
                                <span class="{{ $muted }}">—</span>
                            @endif
                        </td>

                        <td class="py-3 pr-3 text-right text-xs {{ $muted }}">
                            @if($invoiced > 0) {{ $s }}{{ number_format($invoiced, 2) }} @else — @endif
                        </td>
                        <td class="py-3 pr-3 text-right font-semibold text-xs whitespace-nowrap">
                            @if(abs($balance) < 0.005)
                                <span class="text-emerald-500">Settled</span>
                            @elseif($balance > 0)
                                <span class="text-amber-500">{{ $s }}{{ number_format($balance, 2) }}</span>
                            @else
                                <span class="text-sky-500">{{ $s }}{{ number_format(abs($balance), 2) }} cr</span>
                            @endif
                        </td>
                        <td class="py-3 pr-5 text-right">
                            <a href="{{ route('invoices.index', ['client_id' => $client->id]) }}"
                               class="text-[10px] {{ $muted }} hover:text-[color:var(--tw-accent)] transition font-medium">
                                Invoices →
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
