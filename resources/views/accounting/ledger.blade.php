@extends('layouts.app')
@section('title', 'General Ledger')
@section('subtitle', 'All accounts with debit, credit and balance for the selected period.')

@section('content')

<div class="space-y-5">

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="rounded-xl border px-3 py-1.5 text-sm"
                   style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
        </div>
        <div>
            <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="rounded-xl border px-3 py-1.5 text-sm"
                   style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
        </div>
        <button type="submit" class="tw-btn-primary text-xs px-4 py-2 rounded-xl">Apply</button>
        <a href="{{ route('accounting.index') }}" class="text-xs self-center" style="color:var(--tw-muted)">← Accounting</a>
    </form>

    @php
        $typeColors = [
            'asset'     => ['pill' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',     'bal' => 'text-sky-400'],
            'liability' => ['pill' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',  'bal' => 'text-rose-400'],
            'equity'    => ['pill' => 'bg-purple-500/10 text-purple-400 border-purple-500/20', 'bal' => 'text-purple-400'],
            'revenue'   => ['pill' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'bal' => 'text-emerald-400'],
            'expense'   => ['pill' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',  'bal' => 'text-amber-400'],
        ];
        $groups = $accounts->groupBy('type');
        $typeOrder = ['asset', 'liability', 'equity', 'revenue', 'expense'];
    @endphp

    @foreach($typeOrder as $type)
    @if($groups->has($type))
    @php $group = $groups[$type]; $colors = $typeColors[$type] ?? ['pill'=>'','bal'=>'']; @endphp

    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        {{-- Group header --}}
        <div class="px-4 py-2.5 flex items-center gap-2 border-b" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
            <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full border {{ $colors['pill'] }}">
                {{ ucfirst($type) }}
            </span>
            <span class="text-[11px] ml-auto" style="color:var(--tw-muted)">{{ $group->count() }} accounts</span>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead>
                <tr class="text-[10px] uppercase tracking-wider border-b" style="background:var(--tw-surface);color:var(--tw-muted);border-color:var(--tw-border)">
                    <th class="px-4 py-2.5 text-left w-20">Code</th>
                    <th class="px-4 py-2.5 text-left">Account name</th>
                    <th class="px-4 py-2.5 text-right w-32">Debit</th>
                    <th class="px-4 py-2.5 text-right w-32">Credit</th>
                    <th class="px-4 py-2.5 text-right w-32">Balance</th>
                    <th class="px-4 py-2.5 w-10"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($group->sortBy('code') as $account)
                @php $balance = $account->balance; @endphp
                <tr class="border-b transition hover:bg-white/[.02]"
                    style="background:var(--tw-surface);border-color:var(--tw-border)">
                    <td class="px-4 py-3 font-mono text-xs font-semibold" style="color:var(--tw-muted)">
                        {{ $account->code }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-xs font-medium" style="color:var(--tw-fg)">{{ $account->name }}</div>
                        @if(!$account->has_activity)
                            <div class="text-[10px] mt-0.5" style="color:var(--tw-muted)">No activity this period</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-xs" style="color:var(--tw-fg)">
                        {{ $account->total_debit > 0 ? number_format($account->total_debit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-xs" style="color:var(--tw-fg)">
                        {{ $account->total_credit > 0 ? number_format($account->total_credit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-xs font-bold {{ $balance != 0 ? $colors['bal'] : '' }}">
                        @if($balance != 0)
                            {{ number_format(abs($balance), 2) }}
                            <span class="text-[10px] font-normal opacity-70">{{ $balance > 0 ? 'Dr' : 'Cr' }}</span>
                        @else
                            <span style="color:var(--tw-muted)">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($account->has_activity)
                        <a href="{{ route('accounting.ledger.account', [$account, 'from' => $from, 'to' => $to]) }}"
                           class="inline-flex items-center justify-center w-6 h-6 rounded-lg transition hover:bg-emerald-500/10"
                           title="View transactions">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                            </svg>
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    @endif
    @endforeach

    @if($accounts->isEmpty())
    <div class="rounded-2xl border p-12 text-center" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <p class="text-sm" style="color:var(--tw-muted)">No accounts set up yet. Go to Chart of Accounts to add them.</p>
    </div>
    @endif

</div>
@endsection
