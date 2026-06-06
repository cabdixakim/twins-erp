@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $sym = fn(string $code) => match($code) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
        default => $code . ' '
    };
@endphp

@extends('layouts.app')
@section('title', 'Suppliers')
@section('subtitle', 'Fuel suppliers — invoices, payments & balances.')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-lg font-bold {{ $fg }}">Suppliers</h1>
        <p class="text-xs {{ $muted }} mt-0.5">Click a supplier to view their ledger and record payments.</p>
    </div>
    <a href="{{ route('settings.suppliers.index') }}"
       class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        Manage suppliers
    </a>
</div>

@if($suppliers->isEmpty())
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-12 text-center">
        <svg class="w-10 h-10 mx-auto mb-3 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
        </svg>
        <div class="text-sm font-semibold {{ $fg }} mb-1">No active suppliers</div>
        <div class="text-xs {{ $muted }} mb-4">Add fuel suppliers in settings to track invoices and payments here.</div>
        <a href="{{ route('settings.suppliers.index') }}"
           class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            Go to supplier settings
        </a>
    </div>
@else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b {{ $border }} {{ $surface2 }} text-xs {{ $muted }}">
                    <th class="text-left py-3 pl-5 pr-3 font-semibold">Supplier</th>
                    <th class="text-left py-3 pr-3 font-semibold">Country</th>
                    <th class="text-right py-3 pr-3 font-semibold">Invoiced</th>
                    <th class="text-right py-3 pr-5 font-semibold">Net payable</th>
                </tr>
            </thead>
            <tbody>
                @foreach($suppliers as $s)
                    @php
                        $cur        = $s->default_currency ?: 'USD';
                        $invoiced   = (float) ($invoicedTotals[$s->id] ?? 0);
                        $bals       = $balances[$s->id] ?? collect();
                        $netByCur   = $bals->filter(fn($b) => abs($b) >= 0.005);
                    @endphp
                    <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                        <td class="py-3 pl-5 pr-3">
                            <a href="{{ route('suppliers.show', $s) }}"
                               class="font-semibold {{ $fg }} hover:text-[color:var(--tw-accent)] transition">
                                {{ $s->name }}
                            </a>
                            @if($s->contact_person)
                                <div class="text-xs {{ $muted }}">{{ $s->contact_person }}</div>
                            @endif
                        </td>
                        <td class="py-3 pr-3 text-xs {{ $muted }}">{{ $s->country ?: '—' }}</td>
                        <td class="py-3 pr-3 text-right text-xs {{ $muted }}">
                            {{ $invoiced > 0 ? ($sym($cur) . number_format($invoiced, 2)) : '—' }}
                        </td>
                        <td class="py-3 pr-5 text-right">
                            @if($netByCur->isEmpty())
                                <span class="text-xs {{ $muted }}">Settled</span>
                            @else
                                @foreach($netByCur as $c => $bal)
                                    @if($bal > 0)
                                        <span class="text-sm font-bold text-amber-500 block">{{ $sym($c) }}{{ number_format($bal, 2) }}</span>
                                    @else
                                        <span class="text-xs text-emerald-500 font-semibold block">Overpaid {{ $sym($c) }}{{ number_format(abs($bal), 2) }}</span>
                                    @endif
                                @endforeach
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
