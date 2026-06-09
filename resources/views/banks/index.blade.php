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
@section('title', 'Bank Accounts')
@section('subtitle', 'Bank balances, deposits, withdrawals & transfers.')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-lg font-bold {{ $fg }}">Bank Accounts</h1>
        <p class="text-xs {{ $muted }} mt-0.5">Track balances and record transactions per account.</p>
    </div>
    <a href="{{ route('banks.create') }}"
       class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Add account
    </a>
</div>

@if($accounts->isEmpty())
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-12 text-center">
        <svg class="w-10 h-10 mx-auto mb-3 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l9-3 9 3v2H3V6z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8v10a1 1 0 001 1h16a1 1 0 001-1V8"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h4"/>
        </svg>
        <div class="text-sm font-semibold {{ $fg }} mb-1">No bank accounts yet</div>
        <div class="text-xs {{ $muted }} mb-4">Add your company's bank accounts to track cash positions.</div>
        <a href="{{ route('banks.create') }}"
           class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            Add first account
        </a>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($accounts as $acc)
            @php $bal = $acc->currentBalance(); @endphp
            <a href="{{ route('banks.show', $acc) }}"
               class="group rounded-2xl border {{ $border }} {{ $surface }} p-5 hover:bg-[color:var(--tw-surface-2)] transition flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-semibold text-sm {{ $fg }} group-hover:text-[color:var(--tw-accent)] transition">{{ $acc->name }}</div>
                        @if($acc->bank_name)
                            <div class="text-xs {{ $muted }} mt-0.5">{{ $acc->bank_name }}</div>
                        @endif
                        @if($acc->account_number)
                            <div class="text-xs {{ $muted }}">{{ $acc->account_number }}</div>
                        @endif
                    </div>
                    <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                        {{ $acc->is_active
                            ? 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'
                            : 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border }}">
                        {{ $acc->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="flex items-end justify-between">
                    <div>
                        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-0.5">Current Balance</div>
                        <div class="text-xl font-bold {{ $bal >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                            {{ $sym($acc->currency) }}{{ number_format(abs($bal), 2) }}
                            @if($bal < 0) <span class="text-xs font-semibold">DR</span> @endif
                        </div>
                    </div>
                    <span class="text-xs font-semibold {{ $muted }} bg-[color:var(--tw-surface-2)] border {{ $border }} rounded-lg px-2 py-1">
                        {{ $acc->currency }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>
@endif

@endsection
