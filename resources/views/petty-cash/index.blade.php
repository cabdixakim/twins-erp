@php
    $title    = 'Petty Cash';
    $subtitle = 'Float accounts — track cash advances, expenses, and top-ups.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs font-medium px-3 py-2";
    $btnDanger  = "inline-flex items-center gap-2 rounded-xl border border-rose-500/50 bg-rose-600/10 text-rose-400 hover:bg-rose-600 hover:text-white transition text-xs px-2 py-1.5";

    $typeConfig = [
        'top_up'     => ['bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'Top-up'],
        'expense'    => ['bg-rose-500/10 text-rose-400 border-rose-500/20', 'Expense'],
        'adjustment' => ['bg-amber-500/10 text-amber-400 border-amber-500/20', 'Adjustment'],
        'transfer'   => ['bg-sky-500/10 text-sky-400 border-sky-500/20', 'Transfer'],
    ];
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

<div class="flex gap-5 items-start">

    {{-- ── Left: Accounts sidebar ──────────────────────────── --}}
    <div class="w-64 shrink-0 space-y-3">

        {{-- Account list --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
                <span class="text-xs font-semibold {{ $fg }} uppercase tracking-wide">Accounts</span>
                <button type="button" onclick="document.getElementById('newAccountModal').classList.remove('hidden')"
                    class="text-[10px] {{ $btnPrimary }} px-2 py-1">
                    + New
                </button>
            </div>

            @forelse($accounts as $acc)
            @php
                $isActive = $active && $active->id === $acc->id;
                $balColor = $acc->balance >= 0 ? '#10b981' : '#ef4444';
            @endphp
            <a href="{{ route('petty-cash.index', ['account' => $acc->id]) }}"
               class="flex items-center justify-between px-4 py-3 border-b {{ $border }} transition hover:bg-[color:var(--tw-surface-2)]
                      {{ $isActive ? 'bg-[color:var(--tw-surface-2)]' : '' }}">
                <div class="min-w-0">
                    <div class="text-xs font-semibold {{ $fg }} truncate">{{ $acc->name }}</div>
                    <div class="text-[10px] {{ $muted }}">{{ $acc->currency }}</div>
                </div>
                <div class="text-sm font-bold ml-2 shrink-0" style="color:{{ $balColor }}">
                    {{ number_format($acc->balance, 2) }}
                </div>
            </a>
            @empty
            <div class="px-4 py-6 text-center {{ $muted }} text-xs">
                No accounts yet.<br>Create one to get started.
            </div>
            @endforelse
        </div>

        @if($active)
        {{-- Account stats --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 space-y-3">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }} font-semibold">This Month</div>
            <div class="flex justify-between items-center">
                <span class="text-xs {{ $muted }}">Spent</span>
                <span class="text-sm font-bold text-rose-400">{{ number_format($recentTotals['this_month_spend'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs {{ $muted }}">Topped up</span>
                <span class="text-sm font-bold text-emerald-400">{{ number_format($recentTotals['this_month_topup'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t {{ $border }}">
                <span class="text-xs font-semibold {{ $fg }}">Balance</span>
                <span class="text-sm font-bold {{ $fg }}">
                    {{ $active->currency }} {{ number_format($active->balance, 2) }}
                </span>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Right: Transactions ──────────────────────────────── --}}
    <div class="flex-1 min-w-0 space-y-4">

        @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-600/10 text-emerald-400 px-4 py-3 text-xs font-semibold">
            {{ session('status') }}
        </div>
        @endif

        @if($active)

        {{-- Header + action buttons --}}
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h2 class="text-base font-bold {{ $fg }}">{{ $active->name }}</h2>
                <p class="text-xs {{ $muted }}">{{ $active->currency }} · Opening balance: {{ number_format($active->opening_balance, 2) }}</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button type="button" onclick="openTx('top_up')" class="{{ $btnPrimary }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Top-up
                </button>
                <button type="button" onclick="openTx('expense')" class="{{ $btnGhost }} border-rose-500/30 text-rose-400 hover:bg-rose-600 hover:text-white hover:border-rose-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                    Expense
                </button>
                <button type="button" onclick="openTx('adjustment')" class="{{ $btnGhost }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Adjust
                </button>
            </div>
        </div>

        {{-- Transactions table --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b {{ $border }} {{ $surface2 }}">
                        <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Date</th>
                        <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Type</th>
                        <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Description</th>
                        <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">By</th>
                        <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Amount</th>
                        <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Balance</th>
                        <th class="w-10 px-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[color:var(--tw-border)]">
                    @php
                        // Calculate running balance from top (opening + all prior transactions)
                        // We'll compute per-row balance: since transactions are DESC by date,
                        // we first collect them reversed to build running total
                        $allTx     = $transactions->getCollection();
                        $runBal    = (float)$active->balance; // current balance
                        $balances  = [];
                        foreach ($allTx as $tx) {
                            $balances[$tx->id] = $runBal;
                            $runBal -= (float)$tx->amount;
                        }
                    @endphp
                    @forelse($transactions as $tx)
                    @php
                        $cfg    = $typeConfig[$tx->type] ?? ['bg-slate-500/10 text-slate-400 border-slate-500/20','Other'];
                        $isPos  = $tx->amount > 0;
                        $bal    = $balances[$tx->id];
                        $balCol = $bal >= 0 ? '#10b981' : '#ef4444';
                    @endphp
                    <tr class="hover:bg-[color:var(--tw-surface-2)] transition group">
                        <td class="px-4 py-3 {{ $muted }}">{{ $tx->transaction_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide border {{ $cfg[0] }}">
                                {{ $cfg[1] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 {{ $fg }}">{{ $tx->description }}</td>
                        <td class="px-4 py-3 {{ $muted }}">{{ $tx->createdBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $isPos ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $isPos ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold" style="color:{{ $balCol }}">
                            {{ number_format($bal, 2) }}
                        </td>
                        <td class="px-2 py-3 text-right opacity-0 group-hover:opacity-100 transition">
                            @if(!str_starts_with($tx->description, 'VOID:'))
                            <button type="button"
                                onclick="confirmVoid({{ $tx->id }}, '{{ addslashes($tx->description) }}')"
                                class="text-rose-400 hover:text-rose-300 transition"
                                title="Void this entry">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center {{ $muted }}">
                            No transactions yet. Record a top-up or expense to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div>{{ $transactions->links() }}</div>
        @endif

        @else
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-16 text-center">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.15)">
                <svg class="w-7 h-7" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                </svg>
            </div>
            <p class="text-sm font-semibold {{ $fg }} mb-1">Create a petty cash account</p>
            <p class="text-xs {{ $muted }} mb-4">Track float accounts for field expenses, advances, and small purchases.</p>
            <button type="button" onclick="document.getElementById('newAccountModal').classList.remove('hidden')"
                class="{{ $btnPrimary }}">
                Create First Account
            </button>
        </div>
        @endif

    </div>
</div>

{{-- ─────── New Account Modal ─────────────────────────── --}}
<div id="newAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.55)">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} w-full max-w-sm p-6 shadow-2xl">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">New Petty Cash Account</h3>
        <form action="{{ route('petty-cash.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Account name *</label>
                <input type="text" name="name" required placeholder="e.g. Office Float, Field Expenses"
                    class="w-full rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Currency *</label>
                    <input type="text" name="currency" required placeholder="USD" maxlength="10"
                        class="w-full rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Opening balance *</label>
                    <input type="number" name="opening_balance" required placeholder="0.00" min="0" step="0.01"
                        class="w-full rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="{{ $btnPrimary }} flex-1 justify-center">Create Account</button>
                <button type="button" onclick="document.getElementById('newAccountModal').classList.add('hidden')" class="{{ $btnGhost }}">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ─────── Record Transaction Modal ────────────────────── --}}
@if($active)
<div id="txModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.55)">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} w-full max-w-sm p-6 shadow-2xl">
        <h3 id="txModalTitle" class="text-sm font-bold {{ $fg }} mb-4">Record Transaction</h3>
        <form action="{{ route('petty-cash.transaction', $active) }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" id="txType" name="type" value="expense">
            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Description *</label>
                <input type="text" name="description" required placeholder="What is this for?"
                    class="w-full rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Amount ({{ $active->currency }}) *</label>
                    <input type="number" name="amount" required placeholder="0.00" min="0.01" step="0.01"
                        class="w-full rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Date *</label>
                    <input type="date" name="transaction_date" required value="{{ date('Y-m-d') }}"
                        class="w-full rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" id="txSubmitBtn" class="{{ $btnPrimary }} flex-1 justify-center">Save</button>
                <button type="button" onclick="document.getElementById('txModal').classList.add('hidden')" class="{{ $btnGhost }}">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ─────── Void Modal ────────────────────────────────── --}}
<div id="voidModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.55)">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} w-full max-w-sm p-6 shadow-2xl">
        <h3 class="text-sm font-bold {{ $fg }} mb-1">Void transaction</h3>
        <p id="voidModalDesc" class="text-xs {{ $muted }} mb-4"></p>
        <form id="voidForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Reason <span class="{{ $muted }}">(optional)</span></label>
                <input type="text" name="reason" id="voidReason" placeholder="e.g. Entered wrong amount"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} {{ $fg }} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-rose-500/30">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-rose-500/50 bg-rose-600 text-white font-semibold text-xs px-3 py-2 hover:bg-rose-500 transition flex-1 justify-center">
                    Yes, void it
                </button>
                <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')"
                    class="{{ $btnGhost }}">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
function openTx(type) {
    document.getElementById('txType').value = type;
    const labels = { top_up: 'Record Top-up', expense: 'Record Expense', adjustment: 'Record Adjustment' };
    document.getElementById('txModalTitle').textContent = labels[type] || 'Record Transaction';
    document.getElementById('txModal').classList.remove('hidden');
}

function confirmVoid(txId, desc) {
    document.getElementById('voidModalDesc').textContent = 'Voiding: "' + desc + '"';
    document.getElementById('voidReason').value = '';
    document.getElementById('voidForm').action = `/petty-cash/accounts/{{ $active?->id ?? 0 }}/transactions/${txId}/void`;
    document.getElementById('voidModal').classList.remove('hidden');
}
</script>

@endsection
