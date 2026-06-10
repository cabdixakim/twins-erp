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

    $typeMeta = [
        'deposit'      => ['label' => 'Deposit',      'color' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'],
        'withdrawal'   => ['label' => 'Withdrawal',   'color' => 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border border-rose-500/30'],
        'transfer_in'  => ['label' => 'Transfer in',  'color' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30'],
        'transfer_out' => ['label' => 'Transfer out', 'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
    ];

    $fieldBase = 'w-full rounded-xl border ' . $border . ' bg-[color:var(--tw-surface-2)] px-3 py-2 text-sm ' . $fg . ' focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40';
    $label     = 'block text-xs font-semibold ' . $fg . ' mb-1';
    $errText   = 'mt-1 text-[11px] text-rose-500';
@endphp

@extends('layouts.app')
@section('title', $bank->name)
@section('subtitle', ($bank->bank_name ? $bank->bank_name . ' · ' : '') . $bank->currency . ' account')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-2.5 text-xs font-semibold text-rose-600 dark:text-rose-300">
        {{ $errors->first() }}
    </div>
@endif

{{-- Back + actions --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-2">
    <a href="{{ route('banks.index') }}"
       class="inline-flex items-center gap-1.5 text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        All accounts
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('banks.export', $bank) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Export CSV
        </a>
        <a href="{{ route('banks.edit', $bank) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Edit
        </a>
        <form method="POST" action="{{ route('banks.toggle-active', $bank) }}" class="inline">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                {{ $bank->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
        @if($otherAccounts->isNotEmpty())
        <button type="button" onclick="document.getElementById('transferModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4M4 17h12m0 0l-4-4m4 4l-4 4"/>
            </svg>
            Transfer
        </button>
        @endif
        <button type="button" onclick="document.getElementById('withdrawalModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4M4 12l4-4M4 12l4 4"/>
            </svg>
            Withdrawal
        </button>
        <button type="button" onclick="document.getElementById('depositModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Deposit
        </button>
    </div>
</div>

{{-- Account header --}}
<div class="mb-5 rounded-2xl border {{ $border }} {{ $surface }} p-5 flex flex-wrap gap-6">
    <div>
        <div class="flex items-center gap-2 mb-0.5">
            <h1 class="text-xl font-bold {{ $fg }}">{{ $bank->name }}</h1>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                {{ $bank->is_active ? 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border }}">
                {{ $bank->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        @if($bank->bank_name)
            <div class="text-xs {{ $muted }}">{{ $bank->bank_name }}{{ $bank->account_number ? ' · ' . $bank->account_number : '' }}</div>
        @endif
    </div>
    <div class="ml-auto text-right">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-0.5">Current Balance</div>
        <div class="text-2xl font-bold {{ $balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
            {{ $sym($bank->currency) }}{{ number_format(abs($balance), 2) }}
            @if($balance < 0) <span class="text-sm">DR</span> @endif
        </div>
        <div class="text-[11px] {{ $muted }}">Opening: {{ $sym($bank->currency) }}{{ number_format($bank->opening_balance, 2) }}</div>
    </div>
</div>

{{-- Transactions table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
        <span class="text-xs font-semibold {{ $fg }}">Transactions</span>
        <span class="text-[11px] {{ $muted }}">{{ $transactions->total() }} total</span>
    </div>

    @if($transactions->isEmpty())
        <div class="p-10 text-center">
            <div class="text-sm font-semibold {{ $fg }} mb-1">No transactions yet</div>
            <div class="text-xs {{ $muted }}">Use the Deposit button above to record the first transaction.</div>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b {{ $border }} {{ $surface2 }} text-xs {{ $muted }}">
                        <th class="text-left py-3 pl-5 pr-3 font-semibold">Date</th>
                        <th class="text-left py-3 pr-3 font-semibold">Type</th>
                        <th class="text-left py-3 pr-3 font-semibold">Reference</th>
                        <th class="text-left py-3 pr-3 font-semibold">Description</th>
                        <th class="text-right py-3 pr-3 font-semibold">Amount</th>
                        <th class="text-left py-3 pr-3 font-semibold">By</th>
                        <th class="text-right py-3 pr-5 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $tx)
                        <tr class="border-b {{ $border }} last:border-0 {{ $tx->isVoided() ? 'opacity-50' : '' }}">
                            <td class="py-3 pl-5 pr-3 text-xs {{ $muted }} whitespace-nowrap">
                                {{ $tx->entry_date->format('d M Y') }}
                            </td>
                            <td class="py-3 pr-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $typeMeta[$tx->type]['color'] }}">
                                    {{ $typeMeta[$tx->type]['label'] }}
                                    @if($tx->isVoided())
                                        · <span class="ml-1">Voided</span>
                                    @endif
                                </span>
                                @if(in_array($tx->type, ['transfer_in','transfer_out']) && $tx->transferAccount)
                                    <div class="text-[10px] {{ $muted }} mt-0.5">
                                        {{ $tx->type === 'transfer_out' ? '→ ' : '← ' }}
                                        <a href="{{ route('banks.show', $tx->transferAccount) }}"
                                           class="hover:underline">{{ $tx->transferAccount->name }}</a>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 pr-3 text-xs {{ $fg }}">{{ $tx->reference ?? '—' }}</td>
                            <td class="py-3 pr-3 text-xs {{ $muted }} max-w-[200px] truncate">{{ $tx->description ?? '—' }}</td>
                            <td class="py-3 pr-3 text-xs font-semibold text-right whitespace-nowrap
                                {{ $tx->isVoided() ? $muted : (in_array($tx->type, ['deposit','transfer_in']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500') }}">
                                {{ in_array($tx->type, ['deposit','transfer_in']) ? '+' : '-' }}{{ $sym($tx->currency) }}{{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="py-3 pr-3 text-xs {{ $muted }}">{{ $tx->createdBy?->name ?? '—' }}</td>
                            <td class="py-3 pr-5 text-right">
                                @if(!$tx->isVoided())
                                    <button type="button"
                                            onclick="openVoid({{ $tx->id }})"
                                            class="text-[11px] text-rose-500 hover:text-rose-400 font-semibold transition">
                                        Void
                                    </button>
                                @else
                                    <span class="text-[11px] {{ $muted }}" title="{{ $tx->void_reason }}">Voided</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="px-5 py-3 border-t {{ $border }} {{ $surface2 }}">
                {{ $transactions->links() }}
            </div>
        @endif
    @endif
</div>

{{-- ── RECONCILIATION SECTION ──────────────────────────────────────────── --}}
@php
    $unreconciled = $transactions->getCollection()->filter(fn($t) => !$t->isVoided() && !$t->is_reconciled);
    $unreconciledSum = $unreconciled->sum(fn($t) => $t->signedAmount());
    $reconciledCount = $transactions->getCollection()->filter(fn($t) => !$t->isVoided() && $t->is_reconciled)->count();
@endphp

<div class="mt-5 rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold {{ $fg }}">Reconciliation</span>
            @if($reconciledCount > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">
                    {{ $reconciledCount }} reconciled
                </span>
            @endif
            @if($unreconciled->count() > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30">
                    {{ $unreconciled->count() }} unreconciled
                </span>
            @endif
        </div>
        <button type="button" onclick="toggleReconcilePanel()"
                class="text-[11px] font-semibold {{ $muted }} hover:text-[color:var(--tw-fg)] transition">
            Reconcile →
        </button>
    </div>

    <div id="reconcilePanel" class="hidden">
        @if($unreconciled->isEmpty())
            <div class="px-5 py-8 text-center text-xs {{ $muted }}">All transactions on this page are reconciled.</div>
        @else
        <form method="POST" action="{{ route('banks.reconcile', $bank) }}" id="reconcileForm">
            @csrf
            <input type="hidden" name="action" id="reconcileAction" value="reconcile">

            {{-- Statement ref + balance --}}
            <div class="px-5 py-4 border-b {{ $border }} {{ $surface2 }} flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1 font-semibold">Statement reference</label>
                    <input type="text" name="statement_ref" placeholder="e.g. Bank stmt June 2026"
                           class="rounded-xl border {{ $border }} bg-[color:var(--tw-surface-2)] px-3 py-1.5 text-xs {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40 w-56">
                </div>
                <div class="ml-auto flex items-center gap-3 text-xs {{ $muted }}">
                    <span>Unreconciled movement:</span>
                    <span class="font-bold text-sm {{ $unreconciledSum >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                        {{ $unreconciledSum >= 0 ? '+' : '' }}{{ $sym($bank->currency) }}{{ number_format(abs($unreconciledSum), 2) }}
                    </span>
                </div>
            </div>

            {{-- Unreconciled transactions --}}
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b {{ $border }} {{ $surface2 }} text-[10px] uppercase tracking-wide {{ $muted }}">
                            <th class="py-2.5 pl-5 pr-2 w-8">
                                <input type="checkbox" id="selectAllRecon" onchange="toggleAll(this)"
                                       class="rounded border-[color:var(--tw-border)] accent-emerald-500">
                            </th>
                            <th class="text-left py-2.5 pr-3 font-semibold">Date</th>
                            <th class="text-left py-2.5 pr-3 font-semibold">Type</th>
                            <th class="text-left py-2.5 pr-3 font-semibold">Description</th>
                            <th class="text-right py-2.5 pr-5 font-semibold">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unreconciled as $tx)
                        <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition">
                            <td class="py-2.5 pl-5 pr-2">
                                <input type="checkbox" name="transaction_ids[]" value="{{ $tx->id }}"
                                       class="recon-checkbox rounded border-[color:var(--tw-border)] accent-emerald-500">
                            </td>
                            <td class="py-2.5 pr-3 {{ $muted }} whitespace-nowrap">{{ $tx->entry_date->format('d M Y') }}</td>
                            <td class="py-2.5 pr-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $typeMeta[$tx->type]['color'] ?? '' }}">
                                    {{ $typeMeta[$tx->type]['label'] ?? $tx->type }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-3 {{ $fg }} max-w-[220px] truncate">{{ $tx->description ?? ($tx->reference ?? '—') }}</td>
                            <td class="py-2.5 pr-5 text-right font-semibold whitespace-nowrap
                                {{ in_array($tx->type, ['deposit','transfer_in']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">
                                {{ in_array($tx->type, ['deposit','transfer_in']) ? '+' : '-' }}{{ $sym($tx->currency) }}{{ number_format($tx->amount, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t {{ $border }} {{ $surface2 }} flex items-center justify-between gap-3 flex-wrap">
                <span id="reconSelectedCount" class="text-[11px] {{ $muted }}">0 selected</span>
                <div class="flex gap-2">
                    <button type="button" onclick="submitReconcile('unreconcile')"
                            class="h-8 px-3 rounded-xl border {{ $border }} text-[11px] font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition hidden" id="unreconcileBtn">
                        Mark unreconciled
                    </button>
                    <button type="button" onclick="submitReconcile('reconcile')" id="reconcileBtn"
                            class="h-8 px-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/20 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Mark reconciled
                    </button>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>

{{-- ── DEPOSIT MODAL ──────────────────────────────────────────────────── --}}
<div id="depositModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold {{ $fg }}">Record Deposit</h2>
            <button type="button" onclick="document.getElementById('depositModal').classList.add('hidden')"
                    class="text-xl leading-none {{ $muted }} hover:text-[color:var(--tw-fg)]">&times;</button>
        </div>
        <form method="POST" action="{{ route('banks.transactions.store', $bank) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="type" value="deposit">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="{{ $fieldBase }}" placeholder="0.00">
                </div>
                <div>
                    <label class="{{ $label }}">Date *</label>
                    <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required
                           class="{{ $fieldBase }}">
                </div>
            </div>
            <div>
                <label class="{{ $label }}">Reference</label>
                <input type="text" name="reference" class="{{ $fieldBase }}" placeholder="Cheque no., wire ref…">
            </div>
            <div>
                <label class="{{ $label }}">Description</label>
                <input type="text" name="description" class="{{ $fieldBase }}" placeholder="Optional note">
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit"
                        class="h-9 px-5 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-xs font-semibold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/20 transition">
                    Save deposit
                </button>
                <button type="button" onclick="document.getElementById('depositModal').classList.add('hidden')"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── WITHDRAWAL MODAL ────────────────────────────────────────────────── --}}
<div id="withdrawalModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold {{ $fg }}">Record Withdrawal</h2>
            <button type="button" onclick="document.getElementById('withdrawalModal').classList.add('hidden')"
                    class="text-xl leading-none {{ $muted }} hover:text-[color:var(--tw-fg)]">&times;</button>
        </div>
        <form method="POST" action="{{ route('banks.transactions.store', $bank) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="type" value="withdrawal">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="{{ $fieldBase }}" placeholder="0.00">
                </div>
                <div>
                    <label class="{{ $label }}">Date *</label>
                    <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required
                           class="{{ $fieldBase }}">
                </div>
            </div>
            <div>
                <label class="{{ $label }}">Reference</label>
                <input type="text" name="reference" class="{{ $fieldBase }}" placeholder="Optional ref">
            </div>
            <div>
                <label class="{{ $label }}">Description</label>
                <input type="text" name="description" class="{{ $fieldBase }}" placeholder="What was this for?">
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit"
                        class="h-9 px-5 rounded-xl border border-rose-500/40 bg-rose-500/10 text-xs font-semibold text-rose-600 dark:text-rose-300 hover:bg-rose-500/20 transition">
                    Save withdrawal
                </button>
                <button type="button" onclick="document.getElementById('withdrawalModal').classList.add('hidden')"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── TRANSFER MODAL ──────────────────────────────────────────────────── --}}
@if($otherAccounts->isNotEmpty())
<div id="transferModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold {{ $fg }}">Transfer to Another Account</h2>
            <button type="button" onclick="document.getElementById('transferModal').classList.add('hidden')"
                    class="text-xl leading-none {{ $muted }} hover:text-[color:var(--tw-fg)]">&times;</button>
        </div>
        <form method="POST" action="{{ route('banks.transactions.store', $bank) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="type" value="transfer">
            <div>
                <label class="{{ $label }}">Transfer to *</label>
                <select name="transfer_account_id" required class="{{ $fieldBase }}">
                    <option value="">— select account —</option>
                    @foreach($otherAccounts as $oa)
                        <option value="{{ $oa->id }}">{{ $oa->name }} ({{ $oa->currency }})</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="{{ $fieldBase }}" placeholder="0.00">
                </div>
                <div>
                    <label class="{{ $label }}">Date *</label>
                    <input type="date" name="entry_date" value="{{ date('Y-m-d') }}" required
                           class="{{ $fieldBase }}">
                </div>
            </div>
            <div>
                <label class="{{ $label }}">Reference</label>
                <input type="text" name="reference" class="{{ $fieldBase }}" placeholder="Optional ref">
            </div>
            <div>
                <label class="{{ $label }}">Note</label>
                <input type="text" name="description" class="{{ $fieldBase }}" placeholder="Optional note">
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit"
                        class="h-9 px-5 rounded-xl border border-sky-500/40 bg-sky-500/10 text-xs font-semibold text-sky-700 dark:text-sky-300 hover:bg-sky-500/20 transition">
                    Record transfer
                </button>
                <button type="button" onclick="document.getElementById('transferModal').classList.add('hidden')"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ── VOID MODAL ──────────────────────────────────────────────────────── --}}
<div id="voidModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold {{ $fg }}">Void Transaction</h2>
            <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')"
                    class="text-xl leading-none {{ $muted }} hover:text-[color:var(--tw-fg)]">&times;</button>
        </div>
        <form id="voidForm" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="{{ $label }}">Reason (optional)</label>
                <input type="text" name="void_reason" class="{{ $fieldBase }}" placeholder="Brief reason…">
            </div>
            <p class="text-[11px] {{ $muted }}">
                This will reverse the effect on the account balance. Transfers will also void their counterpart entry.
            </p>
            <div class="pt-1 flex gap-2">
                <button type="submit"
                        class="h-9 px-5 rounded-xl bg-rose-600 text-xs font-semibold text-white hover:bg-rose-500 transition">
                    Void
                </button>
                <button type="button" onclick="document.getElementById('voidModal').classList.add('hidden')"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $fg }} transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openVoid(txId) {
    document.getElementById('voidForm').action =
        '{{ url("/banks/{$bank->id}/transactions") }}/' + txId + '/void';
    document.getElementById('voidModal').classList.remove('hidden');
}

function toggleReconcilePanel() {
    const p = document.getElementById('reconcilePanel');
    p.classList.toggle('hidden');
}

function toggleAll(master) {
    document.querySelectorAll('.recon-checkbox').forEach(cb => cb.checked = master.checked);
    updateReconCount();
}

function updateReconCount() {
    const checked = document.querySelectorAll('.recon-checkbox:checked').length;
    const el = document.getElementById('reconSelectedCount');
    if (el) el.textContent = checked + ' selected';
}

document.querySelectorAll('.recon-checkbox').forEach(cb => {
    cb.addEventListener('change', updateReconCount);
});

function submitReconcile(action) {
    const checked = document.querySelectorAll('.recon-checkbox:checked');
    if (!checked.length) { alert('Select at least one transaction.'); return; }
    document.getElementById('reconcileAction').value = action;
    document.getElementById('reconcileForm').submit();
}
</script>

@endsection
