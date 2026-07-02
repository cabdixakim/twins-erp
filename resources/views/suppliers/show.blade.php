@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $typeMeta = [
        'purchase_invoice' => ['label' => 'Invoice',     'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
        'payment'          => ['label' => 'Payment',     'color' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30'],
        'credit_note'      => ['label' => 'Credit note', 'color' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'],
        'adjustment'       => ['label' => 'Adjustment',  'color' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30'],
    ];

    $sym = fn(string $code) => match($code) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
        default => $code . ' '
    };
@endphp

@extends('layouts.app')
@section('title', $supplier->name . ' — Ledger')
@section('subtitle', 'Purchase invoices, credit notes & payments')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

{{-- Back + actions --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-2">
    <a href="{{ route('suppliers.index') }}"
       class="inline-flex items-center gap-1.5 text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        All suppliers
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('suppliers.statement', $supplier) }}" target="_blank"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
            </svg>
            Print statement
        </a>
        <a href="{{ route('suppliers.export', $supplier) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Export CSV
        </a>
        <button type="button" onclick="document.getElementById('creditModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Credit note
        </button>
        <button type="button" onclick="document.getElementById('paymentModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Record payment
        </button>
    </div>
</div>

{{-- Supplier name --}}
<div class="mb-5">
    <div class="flex items-center gap-2 mb-0.5">
        <h1 class="text-xl font-bold {{ $fg }}">{{ $supplier->name }}</h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
            {{ $supplier->is_active ? 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border }}">
            {{ $supplier->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
    <p class="text-xs {{ $muted }}">
        {{ $supplier->country ?: '' }}{{ ($supplier->country && $supplier->city) ? ', ' : '' }}{{ $supplier->city ?: '' }}
        @if($supplier->contact_person) · {{ $supplier->contact_person }} @endif
        @if($supplier->default_currency) · {{ $supplier->default_currency }} @endif
    </p>
</div>

{{-- Balance summary --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Total invoiced</div>
        <div class="text-base font-bold {{ $fg }}">{{ $sym($currency) }}{{ number_format($invoicedTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Purchase invoices raised</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Payments made</div>
        <div class="text-base font-bold text-sky-500">{{ $sym($currency) }}{{ number_format($paymentTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Settled invoices</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Credit notes</div>
        <div class="text-base font-bold text-emerald-500">{{ $sym($currency) }}{{ number_format($creditTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Supplier credits applied</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Net payable</div>
        @if(abs($netPayable) < 0.005)
            <div class="text-base font-bold text-emerald-500">Settled</div>
        @elseif($netPayable > 0)
            <div class="text-base font-bold text-amber-500">{{ $sym($currency) }}{{ number_format($netPayable, 2) }}</div>
        @else
            <div class="text-base font-bold text-emerald-500">Overpaid {{ $sym($currency) }}{{ number_format(abs($netPayable), 2) }}</div>
        @endif
        <div class="text-[10px] {{ $muted }}">Current balance owed</div>
    </div>
</div>

{{-- Open purchase commitments (fuel bought but not yet invoiced) --}}
@if($openPurchases->isNotEmpty())
<div class="rounded-2xl border mb-5 overflow-hidden" style="border-color:rgba(245,158,11,.35); background:rgba(245,158,11,.05)">
    <div class="px-5 py-3 border-b flex items-center justify-between" style="border-color:rgba(245,158,11,.25)">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4" style="color:#f59e0b" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span class="text-xs font-semibold" style="color:#f59e0b">Open purchases — not yet invoiced</span>
        </div>
        <div class="flex items-center gap-3">
            @foreach($openCommitmentByCurrency as $c => $amt)
                <span class="text-sm font-bold" style="color:#f59e0b">{{ $sym($c) }}{{ number_format($amt, 2) }} <span class="text-[10px] font-normal" style="color:#f59e0b;opacity:.7">{{ $c }}</span></span>
            @endforeach
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b" style="border-color:rgba(245,158,11,.2)">
                    <th class="text-left py-2 pl-5 pr-3 font-semibold tw-muted">Reference</th>
                    <th class="text-left py-2 pr-3 font-semibold tw-muted">Type</th>
                    <th class="text-left py-2 pr-3 font-semibold tw-muted">Status</th>
                    <th class="text-right py-2 pr-3 font-semibold tw-muted">Qty (L)</th>
                    <th class="text-right py-2 pr-5 font-semibold tw-muted">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($openPurchases as $op)
                    @php
                        $opValue = (float)$op->qty * (float)$op->unit_price;
                        $opStatusColor = match($op->status) {
                            'confirmed'  => '#10b981',
                            'nominated'  => '#f59e0b',
                            'received'   => '#10b981',
                            'transferred'=> '#0ea5e9',
                            'dispatched' => '#a855f7',
                            default      => '#94a3b8',
                        };
                    @endphp
                    <tr class="border-b last:border-0" style="border-color:rgba(245,158,11,.15)">
                        <td class="py-2 pl-5 pr-3 tw-fg">
                            <a href="{{ route('purchases.show', $op->id) }}" class="font-mono hover:underline" style="color:#f59e0b">
                                {{ $op->reference ?: '#'.$op->id }}
                            </a>
                        </td>
                        <td class="py-2 pr-3 tw-muted capitalize">{{ str_replace('_', ' ', $op->type) }}</td>
                        <td class="py-2 pr-3">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                                  style="background:{{ $opStatusColor }}20; color:{{ $opStatusColor }}; border:1px solid {{ $opStatusColor }}40">
                                {{ ucfirst($op->status) }}
                            </span>
                        </td>
                        <td class="py-2 pr-3 text-right tw-fg font-semibold">{{ number_format($op->qty, 0) }}</td>
                        <td class="py-2 pr-5 text-right font-semibold" style="color:#f59e0b">
                            {{ $sym($op->currency) }}{{ number_format($opValue, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-2.5 text-[10px] tw-muted" style="border-top:1px solid rgba(245,158,11,.15)">
        Invoices are posted automatically as deliveries are recorded. Import purchases post per truck delivery.
    </div>
</div>
@endif

{{-- Ledger entries --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden mb-6">
    <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
        <span class="text-xs font-semibold {{ $fg }}">Ledger entries</span>
        <span class="text-xs {{ $muted }}">Most recent first</span>
    </div>

    @if($entries->isEmpty())
        <div class="p-8 text-center">
            <p class="text-sm {{ $muted }}">No entries yet — invoices are posted automatically when purchases are received.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs {{ $muted }} border-b {{ $border }}">
                        <th class="text-left py-2.5 pl-5 pr-3 font-semibold">Date</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Type</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Description</th>
                        <th class="text-right py-2.5 pr-3 font-semibold">Debit</th>
                        <th class="text-right py-2.5 pr-5 font-semibold">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $e)
                        @php
                            $meta    = $typeMeta[$e->type] ?? ['label' => $e->type, 'color' => 'bg-slate-500/15 text-slate-400 border border-slate-500/30'];
                            $isDebit = (float) $e->amount > 0;
                        @endphp
                        <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                            <td class="py-3 pl-5 pr-3 text-xs {{ $muted }} whitespace-nowrap">
                                {{ $e->entry_date->format('d M Y') }}
                            </td>
                            <td class="py-3 pr-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $meta['color'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="py-3 pr-3 text-xs {{ $fg }}">
                                @if($e->ref_type === 'purchase' && isset($purchaseRefs[$e->ref_id]))
                                    <a href="{{ route('purchases.show', $e->ref_id) }}" class="hover:text-[color:var(--tw-accent)] underline underline-offset-2">
                                        {{ $e->description }}
                                    </a>
                                @else
                                    {{ $e->description }}
                                @endif
                            </td>
                            <td class="py-3 pr-3 text-right text-xs font-semibold {{ $isDebit ? 'text-amber-500' : $muted }}">
                                {{ $isDebit ? ($sym($e->currency) . number_format(abs((float)$e->amount), 2)) : '' }}
                            </td>
                            <td class="py-3 pr-5 text-right text-xs font-semibold {{ !$isDebit ? 'text-sky-400' : $muted }}">
                                {{ !$isDebit ? ($sym($e->currency) . number_format(abs((float)$e->amount), 2)) : '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="px-5 py-3 border-t {{ $border }}">
                {{ $entries->links() }}
            </div>
        @endif
    @endif
</div>

{{-- Payment Modal --}}
<div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background:rgba(0,0,0,.55)" onclick="if(event.target===this)document.getElementById('paymentModal').classList.add('hidden')">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden"
         onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }}" style="background:var(--tw-surface-2)">
            <div class="text-sm font-bold {{ $fg }}">Record payment to {{ $supplier->name }}</div>
            <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                    class="h-8 w-8 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('suppliers.payments.store', $supplier) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Amount</label>
                <div class="flex gap-2 mt-1">
                    <input name="amount" type="number" step="0.01" min="0.01" required
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                           placeholder="0.00">
                    <input name="currency" value="{{ $supplier->default_currency ?: 'USD' }}"
                           class="w-20 h-10 rounded-xl border {{ $border }} {{ $surface }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                           maxlength="8">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Date</label>
                <input name="entry_date" type="date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full h-10 rounded-xl border {{ $border }} {{ $surface }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Note <span class="font-normal opacity-60">(optional)</span></label>
                <input name="description" type="text"
                       class="mt-1 w-full h-10 rounded-xl border {{ $border }} {{ $surface }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                       placeholder="e.g. Wire ref 12345">
            </div>

            {{-- Pay from: bank or petty cash --}}
            <div class="rounded-xl border {{ $border }} p-3 space-y-3" style="background:var(--tw-surface-2)">
                <div class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">Pay from <span class="font-normal normal-case opacity-60">(optional — links payment to your accounts)</span></div>

                @if($bankAccounts->isNotEmpty())
                <div>
                    <label class="text-xs font-semibold {{ $fg }} mb-1 block">Bank account</label>
                    <select name="bank_account_id" id="sup-pay-bank"
                            class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                            onchange="if(this.value)document.getElementById('sup-pay-pca').value=''">
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
                    <select name="petty_cash_account_id" id="sup-pay-pca"
                            class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                            onchange="if(this.value)document.getElementById('sup-pay-bank').value=''">
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

            <div class="flex items-center gap-3 pt-1">
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                        class="flex-1 h-9 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 h-9 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save payment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Credit Note Modal --}}
<div id="creditModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background:rgba(0,0,0,.55)"
     onclick="if(event.target===this)document.getElementById('creditModal').classList.add('hidden')">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} shadow-2xl overflow-hidden"
         style="background:var(--tw-surface)"
         onclick="event.stopPropagation()">
        <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }}"
             style="background:var(--tw-surface-2)">
            <div class="text-sm font-bold {{ $fg }}">Record credit note — {{ $supplier->name }}</div>
            <button type="button" onclick="document.getElementById('creditModal').classList.add('hidden')"
                    class="h-8 w-8 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="{{ route('suppliers.credits.store', $supplier) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Credit amount</label>
                <div class="flex gap-2 mt-1">
                    <input name="amount" type="number" step="0.01" min="0.01" required
                           class="flex-1 h-10 rounded-xl border {{ $border }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                           style="background:var(--tw-surface)"
                           placeholder="0.00">
                    <input name="currency" value="{{ $supplier->default_currency ?: 'USD' }}"
                           class="w-20 h-10 rounded-xl border {{ $border }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                           style="background:var(--tw-surface)"
                           maxlength="8">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Date</label>
                <input name="entry_date" type="date" value="{{ now()->toDateString() }}" required
                       class="mt-1 w-full h-10 rounded-xl border {{ $border }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                       style="background:var(--tw-surface)">
            </div>
            <div>
                <label class="text-xs font-semibold {{ $muted }}">Reason <span class="font-normal opacity-60">(optional)</span></label>
                <input name="description" type="text"
                       class="mt-1 w-full h-10 rounded-xl border {{ $border }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                       style="background:var(--tw-surface)"
                       placeholder="e.g. Short delivery adjustment">
            </div>
            <div class="flex items-center gap-3 pt-1">
                <button type="button" onclick="document.getElementById('creditModal').classList.add('hidden')"
                        class="flex-1 h-9 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 h-9 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-xs font-semibold text-emerald-500 hover:bg-emerald-500/20 transition">
                    Save credit note
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
