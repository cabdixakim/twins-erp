@php
    $title    = 'Petty Cash';
    $subtitle = 'Operational cash float — track expenses, advances, and top-ups.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $fieldCls = "w-full rounded-xl border {$border} {$bg} {$fg} text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500/30";

    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border {$border} bg-[color:var(--tw-btn)] {$fg} hover:bg-[color:var(--tw-btn-hover)] transition text-xs font-medium px-3 py-2";

    $typeConfig = [
        'top_up'     => ['bg-emerald-500/10 text-emerald-400 border-emerald-500/20', 'Top-up'],
        'expense'    => ['bg-rose-500/10 text-rose-400 border-rose-500/20', 'Expense'],
        'adjustment' => ['bg-amber-500/10 text-amber-400 border-amber-500/20', 'Adjustment'],
        'transfer'   => ['bg-sky-500/10 text-sky-400 border-sky-500/20', 'Transfer'],
    ];

    $categories = [
        ''                 => '— Select category —',
        'fuel'             => 'Fuel',
        'driver_advance'   => 'Driver advance',
        'border_fees'      => 'Border fees',
        'hospitality'      => 'Hospitality',
        'office'           => 'Office supplies',
        'transport'        => 'Transport / logistics',
        'maintenance'      => 'Maintenance',
        'bank_charges'     => 'Bank charges',
        'other'            => 'Other',
    ];
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

@if(session('status'))
<div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-600/10 text-emerald-400 px-4 py-3 text-xs font-semibold">
    {{ session('status') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-400 px-4 py-3 text-xs font-semibold">
    {{ $errors->first() }}
</div>
@endif

<div class="flex gap-5 items-start">

    {{-- ── Left: Accounts sidebar ──────────────────────────── --}}
    <div class="w-56 shrink-0 space-y-3">

        {{-- Account list --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
                <span class="text-[10px] font-semibold {{ $muted }} uppercase tracking-wide">Accounts</span>
                {{-- Only show + New if already has accounts --}}
                @if($accounts->isNotEmpty())
                <button type="button" onclick="document.getElementById('newAccountModal').classList.remove('hidden')"
                    class="text-[10px] {{ $muted }} hover:text-[color:var(--tw-fg)] transition font-semibold">
                    + New
                </button>
                @endif
            </div>

            @forelse($accounts as $acc)
            @php
                $isActive = $active && $active->id === $acc->id;
                $balColor = $acc->balance >= 0 ? '#10b981' : '#ef4444';
            @endphp
            <a href="{{ route('petty-cash.index', ['account' => $acc->id]) }}"
               class="flex items-center justify-between px-4 py-3 border-b {{ $border }} last:border-0 transition
                      {{ $isActive ? 'bg-[color:var(--tw-surface-2)]' : 'hover:bg-[color:var(--tw-surface-2)]' }}">
                <div class="min-w-0">
                    <div class="text-xs font-semibold {{ $fg }} truncate">{{ $acc->name }}</div>
                    <div class="text-[10px] {{ $muted }}">{{ $acc->currency }}</div>
                </div>
                <div class="text-sm font-bold ml-2 shrink-0 tabular-nums" style="color:{{ $balColor }}">
                    {{ number_format($acc->balance, 2) }}
                </div>
            </a>
            @empty
            {{-- Empty state: prompt to create first account --}}
            <div class="px-4 py-6 text-center space-y-2">
                <p class="text-xs {{ $muted }}">No cash float yet.</p>
                <button type="button" onclick="document.getElementById('newAccountModal').classList.remove('hidden')"
                    class="{{ $btnPrimary }} text-[11px] px-3 py-1.5 mx-auto">
                    Create float
                </button>
            </div>
            @endforelse
        </div>

        @if($active)
        {{-- This month stats --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 space-y-2.5">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }} font-semibold">This month</div>
            <div class="flex justify-between items-center">
                <span class="text-[11px] {{ $muted }}">Expenses</span>
                <span class="text-sm font-bold text-rose-400">{{ number_format($recentTotals['this_month_spend'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-[11px] {{ $muted }}">Top-ups</span>
                <span class="text-sm font-bold text-emerald-400">{{ number_format($recentTotals['this_month_topup'], 2) }}</span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t {{ $border }}">
                <span class="text-[11px] font-semibold {{ $fg }}">Balance</span>
                <span class="text-sm font-bold {{ $active->balance >= 0 ? 'text-emerald-400' : 'text-rose-400' }} tabular-nums">
                    {{ $active->currency }} {{ number_format($active->balance, 2) }}
                </span>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Right: Main content ───────────────────────────────── --}}
    <div class="flex-1 min-w-0 space-y-4">

        @if($active)

        {{-- Header bar --}}
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h2 class="text-base font-bold {{ $fg }}">{{ $active->name }}</h2>
                <p class="text-[11px] {{ $muted }}">
                    {{ $active->currency }} · Opening balance: {{ number_format($active->opening_balance, 2) }}
                    @if($bankAccounts->isNotEmpty())
                        · <a href="{{ route('banks.index') }}" class="hover:underline">{{ $bankAccounts->count() }} linked bank {{ $bankAccounts->count() === 1 ? 'account' : 'accounts' }}</a>
                    @endif
                </p>
            </div>
            <div class="flex gap-2 flex-wrap">
                {{-- Export --}}
                <a href="{{ route('petty-cash.export', $active) }}"
                   class="{{ $btnGhost }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    Export CSV
                </a>
                {{-- Top-up --}}
                <button type="button" onclick="openTx('top_up')" class="{{ $btnPrimary }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Top-up
                </button>
                {{-- Expense --}}
                <button type="button" onclick="openTx('expense')"
                    class="{{ $btnGhost }} border-rose-500/30 text-rose-400 hover:bg-rose-600 hover:text-white hover:border-rose-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
                    </svg>
                    Expense
                </button>
                {{-- Adjust --}}
                <button type="button" onclick="openTx('adjustment')" class="{{ $btnGhost }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Adjust
                </button>
            </div>
        </div>

        {{-- Ledger table --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b {{ $border }} {{ $surface2 }}">
                            <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28 whitespace-nowrap">Date</th>
                            <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Type</th>
                            <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Category</th>
                            <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Purpose / Description</th>
                            <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-36">Recipient</th>
                            <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Reference</th>
                            <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">By</th>
                            <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Amount</th>
                            <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Balance</th>
                            <th class="w-8 px-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--tw-border)]">
                        @php
                            $allTx    = $transactions->getCollection();
                            $runBal   = (float)$active->balance;
                            $balances = [];
                            foreach ($allTx as $tx) {
                                $balances[$tx->id] = $runBal;
                                $runBal -= (float)$tx->amount;
                            }
                        @endphp
                        @forelse($transactions as $tx)
                        @php
                            $cfg    = $typeConfig[$tx->type] ?? ['bg-slate-500/10 text-slate-400 border-slate-500/20', 'Other'];
                            $isPos  = $tx->amount > 0;
                            $bal    = $balances[$tx->id];
                            $balCol = $bal >= 0 ? '#10b981' : '#ef4444';
                            $isVoid = str_starts_with($tx->description, 'VOID:');
                        @endphp
                        <tr class="hover:bg-[color:var(--tw-surface-2)] transition group {{ $isVoid ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3 {{ $muted }} whitespace-nowrap">{{ $tx->transaction_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide border {{ $cfg[0] }}">
                                    {{ $cfg[1] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 {{ $muted }}">
                                @if($tx->category)
                                    {{ $categories[$tx->category] ?? ucfirst(str_replace('_',' ',$tx->category)) }}
                                @else
                                    <span class="opacity-30">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 {{ $fg }} max-w-[220px]">
                                <span title="{{ $tx->description }}">{{ Str::limit($tx->description, 60) }}</span>
                            </td>
                            <td class="px-4 py-3 {{ $muted }}">{{ $tx->recipient ?: '—' }}</td>
                            <td class="px-4 py-3 {{ $muted }} font-mono text-[11px]">{{ $tx->reference ?: '—' }}</td>
                            <td class="px-4 py-3 {{ $muted }}">{{ $tx->createdBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $isPos ? 'text-emerald-400' : 'text-rose-400' }}">
                                {{ $isPos ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums" style="color:{{ $balCol }}">
                                {{ number_format($bal, 2) }}
                            </td>
                            <td class="px-2 py-3 text-right opacity-0 group-hover:opacity-100 transition">
                                @if(!$isVoid)
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
                            <td colspan="10" class="px-4 py-12 text-center {{ $muted }}">
                                No transactions yet. Record a top-up to fund this account.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($transactions->hasPages())
            <div>{{ $transactions->links() }}</div>
        @endif

        @else
        {{-- No account selected / no accounts exist --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-16 text-center">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center"
                 style="background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.15)">
                <svg class="w-7 h-7" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                </svg>
            </div>
            <p class="text-sm font-semibold {{ $fg }} mb-1">Set up your petty cash float</p>
            <p class="text-xs {{ $muted }} mb-4 max-w-xs mx-auto">Give it a name (e.g. "Main Float"), set the current cash on hand as the opening balance, then start logging top-ups and expenses.</p>
            <button type="button" onclick="document.getElementById('newAccountModal').classList.remove('hidden')"
                class="{{ $btnPrimary }}">
                Create first float
            </button>
        </div>
        @endif

    </div>
</div>

{{-- ─────── New Account Modal ─────────────────────────── --}}
<div id="newAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.55)">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} w-full max-w-sm p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold {{ $fg }}">New cash float</h3>
            <button type="button" onclick="document.getElementById('newAccountModal').classList.add('hidden')"
                    class="text-xl leading-none {{ $muted }} hover:text-[color:var(--tw-fg)]">&times;</button>
        </div>
        <form action="{{ route('petty-cash.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Name *</label>
                <input type="text" name="name" required placeholder="e.g. Main Float, Kinshasa Office"
                    class="{{ $fieldCls }}">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Currency *</label>
                    <input type="text" name="currency" required placeholder="USD" maxlength="10"
                        class="{{ $fieldCls }}">
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Cash on hand now</label>
                    <input type="number" name="opening_balance" value="0" placeholder="0.00" min="0" step="0.01"
                        class="{{ $fieldCls }}">
                </div>
            </div>
            <p class="text-[10px] {{ $muted }}">Opening balance = cash physically in the tin right now.</p>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="{{ $btnPrimary }} flex-1 justify-center">Create float</button>
                <button type="button" onclick="document.getElementById('newAccountModal').classList.add('hidden')" class="{{ $btnGhost }}">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- ─────── Record Transaction Modal ────────────────────── --}}
@if($active)
<div id="txModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.55)">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} w-full max-w-md p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 id="txModalTitle" class="text-sm font-bold {{ $fg }}">Record Transaction</h3>
            <button type="button" onclick="document.getElementById('txModal').classList.add('hidden')"
                    class="text-xl leading-none {{ $muted }} hover:text-[color:var(--tw-fg)]">&times;</button>
        </div>
        <form action="{{ route('petty-cash.transaction', $active) }}" method="POST" class="space-y-3">
            @csrf
            <input type="hidden" id="txType" name="type" value="expense">

            {{-- Row 1: Date + Amount --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Date *</label>
                    <input type="date" name="transaction_date" required value="{{ date('Y-m-d') }}"
                        class="{{ $fieldCls }}">
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Amount ({{ $active->currency }}) *</label>
                    <input type="number" name="amount" required placeholder="0.00" min="0.01" step="0.01"
                        class="{{ $fieldCls }}">
                </div>
            </div>

            {{-- Purpose / Description --}}
            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Purpose / Description *</label>
                <input type="text" name="description" required placeholder="What is this for?"
                    class="{{ $fieldCls }}">
            </div>

            {{-- Category + Recipient --}}
            <div class="grid grid-cols-2 gap-3">
                <div id="categoryRow">
                    <label class="block text-[11px] {{ $muted }} mb-1">Category</label>
                    <select name="category" class="{{ $fieldCls }}">
                        @foreach($categories as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="recipientRow">
                    <label class="block text-[11px] {{ $muted }} mb-1">Recipient / Payee</label>
                    <input type="text" name="recipient" placeholder="Name or company"
                        class="{{ $fieldCls }}">
                </div>
            </div>

            {{-- Reference --}}
            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Reference</label>
                <input type="text" name="reference" placeholder="Receipt no., voucher, invoice ref…"
                    class="{{ $fieldCls }}">
            </div>

            {{-- Bank account dropdown — only shown for Top-up --}}
            @if($bankAccounts->isNotEmpty())
            <div id="bankAccountRow" class="hidden">
                <label class="block text-[11px] {{ $muted }} mb-1">Fund from bank account <span class="{{ $muted }}">(optional)</span></label>
                <select name="bank_account_id" class="{{ $fieldCls }}">
                    <option value="">— Cash / other source —</option>
                    @foreach($bankAccounts as $ba)
                        <option value="{{ $ba->id }}">{{ $ba->name }} ({{ $ba->currency }})</option>
                    @endforeach
                </select>
                <p class="text-[10px] {{ $muted }} mt-1">Selecting a bank also posts a matching withdrawal on that account.</p>
            </div>
            @endif

            <div class="flex gap-2 pt-1">
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
                    Void it
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
    const titles = { top_up: 'Record Top-up', expense: 'Record Expense', adjustment: 'Record Adjustment' };
    document.getElementById('txModalTitle').textContent = titles[type] || 'Record Transaction';

    // Show/hide bank funding row (top_up only)
    const bankRow = document.getElementById('bankAccountRow');
    if (bankRow) bankRow.classList.toggle('hidden', type !== 'top_up');

    // Hide category/recipient for adjustments
    const catRow = document.getElementById('categoryRow');
    const recRow = document.getElementById('recipientRow');
    const isAdj  = type === 'adjustment';
    if (catRow) catRow.classList.toggle('hidden', isAdj);
    if (recRow) recRow.classList.toggle('hidden', isAdj);

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
