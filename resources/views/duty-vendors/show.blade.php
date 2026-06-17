@extends('layouts.app')

@section('title', $dutyVendor->name . ' — Customs Duty')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';
    $sym     = \App\Http\Controllers\DutyLedgerController::currencySymbol($currency);
@endphp

<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">
                <a href="{{ route('duty-vendors.index') }}" class="hover:underline">Customs Authorities</a> /
            </div>
            <h1 class="text-xl font-bold {{ $fg }}">{{ $dutyVendor->name }}</h1>
            @if($dutyVendor->code || $dutyVendor->country)
                <div class="text-sm {{ $muted }} mt-0.5">
                    {{ $dutyVendor->code }}{{ $dutyVendor->code && $dutyVendor->country ? ' · ' : '' }}{{ $dutyVendor->country }}
                </div>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('duty-vendors.statement', $dutyVendor) }}"
               class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
                Statement
            </a>
            <a href="{{ route('duty-vendors.export', $dutyVendor) }}"
               class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
                CSV Export
            </a>
            <button type="button" onclick="document.getElementById('paymentModal').classList.remove('hidden')"
                class="h-8 px-3 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10
                       text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                Record payment
            </button>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    {{-- Summary KPIs --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Total Duties Charged</div>
            <div class="text-xl font-bold {{ $fg }}">{{ $sym }}{{ number_format($chargesTotal, 2) }}</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Payments Made</div>
            <div class="text-xl font-bold text-sky-500">{{ $sym }}{{ number_format($paymentTotal, 2) }}</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Net Payable</div>
            <div class="text-xl font-bold {{ $netPayable > 0 ? 'text-amber-500' : 'text-emerald-500' }}">
                {{ $sym }}{{ number_format(abs($netPayable), 2) }}
                @if($netPayable < 0) <span class="text-sm font-normal">CR</span> @endif
            </div>
        </div>
    </div>

    {{-- Ledger entries --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Ledger Entries</span>
        </div>
        @if($entries->isEmpty())
            <div class="p-10 text-center text-sm {{ $muted }}">No entries yet.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="{{ $muted }} border-b {{ $border }} {{ $surface2 }}">
                    <tr>
                        <th class="text-left px-5 py-2.5 font-semibold">Date</th>
                        <th class="text-left px-3 py-2.5 font-semibold">Type</th>
                        <th class="text-left px-3 py-2.5 font-semibold">Description</th>
                        <th class="text-right px-3 py-2.5 font-semibold">Debit</th>
                        <th class="text-right px-3 py-2.5 font-semibold">Credit</th>
                        <th class="text-right px-5 py-2.5 font-semibold">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y {{ $border }}">
                    @foreach($entries as $e)
                    @php
                        $isDebit = (float) $e->amount > 0;
                        $refKey  = $e->ref_type . ':' . $e->ref_id;
                        $link    = $refLinks[$refKey] ?? null;
                    @endphp
                    <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                        <td class="px-5 py-3 whitespace-nowrap {{ $muted }}">{{ $e->entry_date->format('d M Y') }}</td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border
                                @if($e->type === 'duty_charge') bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30
                                @elseif($e->type === 'payment') bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-500/30
                                @else bg-slate-500/10 text-slate-500 border-slate-500/20 @endif">
                                {{ $e->type === 'duty_charge' ? 'Duty' : ucfirst($e->type) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 {{ $fg }}">
                            @if($link)
                                <a href="{{ $link }}" class="hover:underline">{{ $e->description }}</a>
                            @else
                                {{ $e->description }}
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-amber-600 dark:text-amber-400">
                            {{ $isDebit ? number_format(abs((float)$e->amount), 2) : '' }}
                        </td>
                        <td class="px-3 py-3 text-right font-mono text-sky-600 dark:text-sky-400">
                            {{ !$isDebit ? number_format(abs((float)$e->amount), 2) : '' }}
                        </td>
                        <td class="px-5 py-3 text-right font-semibold font-mono {{ $e->running_balance >= 0 ? 'text-amber-500' : 'text-emerald-500' }}">
                            {{ number_format(abs((float)$e->running_balance), 2) }}
                            @if($e->running_balance < 0) <span class="text-xs font-normal">CR</span> @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t {{ $border }} {{ $surface2 }}">
            {{ $entries->links() }}
        </div>
        @endif
    </div>

    {{-- Adjustment button --}}
    <div class="flex justify-end">
        <button type="button" onclick="document.getElementById('adjModal').classList.remove('hidden')"
            class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
            Record adjustment
        </button>
    </div>
</div>

{{-- Payment modal --}}
<div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5)">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} p-6 shadow-2xl">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">Record duty payment</h3>
        <form method="POST" action="{{ route('duty-vendors.payments.store', $dutyVendor) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Amount *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Currency</label>
                <input type="text" name="currency" value="{{ $currency }}" maxlength="8"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Date *</label>
                <input type="date" name="entry_date" value="{{ now()->toDateString() }}" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Description</label>
                <input type="text" name="description" placeholder="Payment reference or note"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')"
                    class="h-9 px-4 rounded-xl border {{ $border }} text-sm {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">Cancel</button>
                <button type="submit"
                    class="h-9 px-5 rounded-xl bg-sky-500 text-white text-sm font-semibold hover:opacity-90 transition">Record payment</button>
            </div>
        </form>
    </div>
</div>

{{-- Adjustment modal --}}
<div id="adjModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5)">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} p-6 shadow-2xl">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">Record adjustment</h3>
        <form method="POST" action="{{ route('duty-vendors.adjustments.store', $dutyVendor) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Direction *</label>
                <select name="direction"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
                    <option value="debit">Debit (increases payable)</option>
                    <option value="credit">Credit (reduces payable)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Amount *</label>
                <input type="number" name="amount" step="0.01" min="0.01" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Date *</label>
                <input type="date" name="entry_date" value="{{ now()->toDateString() }}" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Description *</label>
                <input type="text" name="description" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('adjModal').classList.add('hidden')"
                    class="h-9 px-4 rounded-xl border {{ $border }} text-sm {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">Cancel</button>
                <button type="submit"
                    class="h-9 px-5 rounded-xl bg-[color:var(--tw-accent)] text-white text-sm font-semibold hover:opacity-90 transition">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Compute running balance from latest to oldest for pagination display
let runningBal = {{ $netPayable }};
</script>
@endsection
