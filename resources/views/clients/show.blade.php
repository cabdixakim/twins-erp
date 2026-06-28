@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $typeMeta = [
        'invoice'    => ['label' => 'Invoice',    'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
        'payment'    => ['label' => 'Payment',    'color' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30'],
        'credit_note'=> ['label' => 'Credit',     'color' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'],
        'adjustment' => ['label' => 'Adjustment', 'color' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30'],
    ];

    $currencySymbols = ['USD'=>'$','EUR'=>'€','GBP'=>'£','ZAR'=>'R ','CDF'=>'FC ','ZMW'=>'K ','ZWL'=>'ZWL '];
    $sym = fn(string $code) => $currencySymbols[$code] ?? ($code . ' ');
@endphp

@extends('layouts.app')
@section('title', $client->name . ' — AR Ledger')
@section('subtitle', 'Invoices, payments & outstanding balance')

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white px-4 py-2.5 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-600/10 text-rose-600 dark:text-rose-300 px-4 py-2.5 text-xs font-semibold">
        {{ session('error') }}
    </div>
@endif

{{-- Back + actions --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-2">
    <a href="{{ route('clients.index') }}"
       class="inline-flex items-center gap-1.5 text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        All clients
    </a>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('clients.statement', $client) }}" target="_blank"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
            </svg>
            Print statement
        </a>
        <a href="{{ route('clients.export', $client) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
            </svg>
            Export CSV
        </a>
        <button type="button" onclick="document.getElementById('creditModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 text-xs font-semibold text-emerald-600 dark:text-emerald-300 hover:bg-emerald-500/20 transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4 4-4M5 10h11a4 4 0 010 8h-1"/>
            </svg>
            Credit note
        </button>
        <button type="button" onclick="document.getElementById('adjustmentModal').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Adjustment
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

{{-- Client name + meta --}}
<div class="mb-5">
    <div class="flex items-center gap-2 mb-0.5">
        <h1 class="text-xl font-bold {{ $fg }}">{{ $client->name }}</h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
            {{ $client->is_active
                ? 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'
                : 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border }}">
            {{ $client->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
    <p class="text-xs {{ $muted }}">
        {{ $client->type ? ucfirst($client->type) : 'Client' }}
        @if($client->city || $client->country)
            · {{ $client->city }}{{ $client->city && $client->country ? ', ' : '' }}{{ $client->country }}
        @endif
        @if($client->contact_person)
            · {{ $client->contact_person }}
        @endif
        @if($client->credit_limit)
            · Credit limit: {{ $sym($currency) }}{{ number_format($client->credit_limit, 2) }}
        @endif
    </p>
</div>

{{-- Balance summary --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Total invoiced</div>
        <div class="text-base font-bold {{ $fg }}">{{ $sym($currency) }}{{ number_format($invoicedTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">From posted sales</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Payments received</div>
        <div class="text-base font-bold text-sky-500">{{ $sym($currency) }}{{ number_format($paymentTotal, 2) }}</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Credit notes</div>
        <div class="text-base font-bold text-emerald-500">{{ $sym($currency) }}{{ number_format($creditTotal, 2) }}</div>
    </div>
    <div class="rounded-2xl border {{ $netAR > 0.005 ? 'border-amber-500/40' : $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Net receivable</div>
        @if(abs($netAR) < 0.005)
            <div class="text-base font-bold text-emerald-500">Settled</div>
            <div class="text-[10px] {{ $muted }}">Nothing outstanding</div>
        @elseif($netAR > 0)
            <div class="text-base font-bold text-amber-500">{{ $sym($currency) }}{{ number_format($netAR, 2) }}</div>
            <div class="text-[10px] {{ $muted }}">Still owed by client</div>
        @else
            <div class="text-base font-bold text-sky-500">{{ $sym($currency) }}{{ number_format(abs($netAR), 2) }} cr</div>
            <div class="text-[10px] {{ $muted }}">Client in credit</div>
        @endif
    </div>
</div>

{{-- Ledger entries --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-sm font-semibold {{ $fg }}">Ledger entries</div>
        <div class="text-xs {{ $muted }}">{{ $entries->total() }} {{ $entries->total() === 1 ? 'entry' : 'entries' }}</div>
    </div>

    @if($entries->isEmpty())
        <div class="px-5 py-12 text-center">
            <div class="text-xs {{ $muted }}">No entries yet. Invoices appear automatically when sales are posted with this client linked.</div>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="{{ $muted }} border-b {{ $border }} {{ $surface2 }}">
                        <th class="text-left py-2.5 pl-5 pr-3 font-semibold">Date</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Type</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Description</th>
                        <th class="text-left py-2.5 pr-3 font-semibold">Reference</th>
                        <th class="text-right py-2.5 pr-5 font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $meta    = $typeMeta[$entry->type] ?? ['label' => ucfirst($entry->type), 'color' => 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border];
                            $isDebit = $entry->amount > 0;
                            $linkKey = $entry->ref_type && $entry->ref_id ? $entry->ref_type . ':' . $entry->ref_id : null;
                            $refUrl  = $linkKey ? ($refLinks[$linkKey] ?? null) : null;
                            $refLabel = $entry->ref_type ? (class_basename($entry->ref_type) . ' #' . $entry->ref_id) : null;
                        @endphp
                        <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                            <td class="py-2.5 pl-5 pr-3 {{ $muted }} whitespace-nowrap">
                                {{ $entry->entry_date->format('d M Y') }}
                            </td>
                            <td class="py-2.5 pr-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $meta['color'] }}">
                                    {{ $meta['label'] }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-3 {{ $fg }} max-w-xs">{{ $entry->description }}</td>
                            <td class="py-2.5 pr-3 whitespace-nowrap">
                                @if($refLabel)
                                    @if($refUrl)
                                        <a href="{{ $refUrl }}" class="font-mono text-[10px] text-[color:var(--tw-accent)] hover:underline">{{ $refLabel }}</a>
                                    @else
                                        <span class="font-mono text-[10px] {{ $muted }}">{{ $refLabel }}</span>
                                    @endif
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-5 text-right font-semibold whitespace-nowrap">
                                @if($isDebit)
                                    <span class="text-amber-500">{{ $sym($entry->currency) }}{{ number_format($entry->amount, 2) }}</span>
                                @else
                                    <span class="{{ $fg }}">− {{ $sym($entry->currency) }}{{ number_format(abs($entry->amount), 2) }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="px-5 py-3 border-t {{ $border }} {{ $surface2 }}">
                {{ $entries->links() }}
            </div>
        @endif
    @endif
</div>

{{-- ── Payment modal ── --}}
<div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
     onclick="if(event.target===this)document.getElementById('paymentModal').classList.add('hidden')">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden"
         onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
            <div class="text-sm font-semibold {{ $fg }}">Record payment received</div>
            <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('clients.payments.store', $client) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Amount received</label>
                <div class="flex items-center gap-2">
                    <span class="h-10 px-3 flex items-center rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} whitespace-nowrap select-none">
                        {{ $sym($currency) }}{{ $currency }}
                    </span>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Payment date</label>
                <input type="date" name="entry_date" required value="{{ date('Y-m-d') }}"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Note <span class="{{ $muted }}">(optional)</span></label>
                <input type="text" name="description" placeholder="e.g. Wire transfer, Ref #789"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>

            {{-- Deposit into: bank or petty cash --}}
            <div class="rounded-xl border {{ $border }} p-3 space-y-3" style="background:var(--tw-surface-2)">
                <div class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">Deposit into <span class="font-normal normal-case opacity-60">(optional — links receipt to your accounts)</span></div>

                @if(isset($bankAccounts) && $bankAccounts->isNotEmpty())
                <div>
                    <label class="text-xs font-semibold {{ $fg }} mb-1 block">Bank account</label>
                    <select name="bank_account_id" id="cli-pay-bank"
                            class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                            onchange="if(this.value)document.getElementById('cli-pay-pca').value=''">
                        <option value="">— none —</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->name }} ({{ $ba->currency }})</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if(isset($pettyCashAccounts) && $pettyCashAccounts->isNotEmpty())
                <div>
                    <label class="text-xs font-semibold {{ $fg }} mb-1 block">Petty cash</label>
                    <select name="petty_cash_account_id" id="cli-pay-pca"
                            class="w-full h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40"
                            onchange="if(this.value)document.getElementById('cli-pay-bank').value=''">
                        <option value="">— none —</option>
                        @foreach($pettyCashAccounts as $pca)
                            <option value="{{ $pca->id }}">{{ $pca->name }} ({{ $pca->currency }})</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if((!isset($bankAccounts) || $bankAccounts->isEmpty()) && (!isset($pettyCashAccounts) || $pettyCashAccounts->isEmpty()))
                    <p class="text-xs {{ $muted }}">No bank or petty cash accounts set up yet.</p>
                @endif
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">Cancel</button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save payment
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Credit note modal ── --}}
<div id="creditModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
            <div>
                <div class="text-sm font-semibold {{ $fg }}">Issue credit note</div>
                <div class="text-[10px] {{ $muted }}">Reduces the client's outstanding balance</div>
            </div>
            <button type="button" onclick="document.getElementById('creditModal').classList.add('hidden')"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('clients.credits.store', $client) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Amount</label>
                <div class="flex items-center gap-2">
                    <span class="h-10 px-3 flex items-center rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} whitespace-nowrap select-none">
                        {{ $sym($currency) }}{{ $currency }}
                    </span>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-emerald-500/40" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Date</label>
                <input type="date" name="entry_date" required value="{{ date('Y-m-d') }}"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-emerald-500/40" />
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Reason <span class="text-rose-400">*</span></label>
                <input type="text" name="description" required placeholder="e.g. Short delivery on SO-2026-00012"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-emerald-500/40" />
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="document.getElementById('creditModal').classList.add('hidden')"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">Cancel</button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 text-xs font-semibold text-emerald-600 dark:text-emerald-300 hover:bg-emerald-500/20 transition">
                    Issue credit note
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Adjustment modal ── --}}
<div id="adjustmentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
            <div>
                <div class="text-sm font-semibold {{ $fg }}">Record adjustment</div>
                <div class="text-[10px] {{ $muted }}">Debit = adds to what they owe · Credit = reduces it</div>
            </div>
            <button type="button" onclick="document.getElementById('adjustmentModal').classList.add('hidden')"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('clients.adjustments.store', $client) }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Direction</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} cursor-pointer text-xs font-semibold {{ $fg }}">
                        <input type="radio" name="direction" value="debit" checked class="accent-amber-500"> Debit (owe more)
                    </label>
                    <label class="flex items-center gap-2 h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} cursor-pointer text-xs font-semibold {{ $fg }}">
                        <input type="radio" name="direction" value="credit" class="accent-emerald-500"> Credit (owe less)
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Amount</label>
                <div class="flex items-center gap-2">
                    <span class="h-10 px-3 flex items-center rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} whitespace-nowrap select-none">
                        {{ $sym($currency) }}{{ $currency }}
                    </span>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Date</label>
                <input type="date" name="entry_date" required value="{{ date('Y-m-d') }}"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Reason <span class="text-rose-400">*</span></label>
                <input type="text" name="description" required placeholder="e.g. Pricing correction on SO-00010"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="document.getElementById('adjustmentModal').classList.add('hidden')"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">Cancel</button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save adjustment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
['paymentModal','creditModal','adjustmentModal'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>
@endpush

@endsection
