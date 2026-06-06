@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $typeMeta = [
        'freight_charge' => ['label' => 'Freight',      'color' => 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'],
        'advance'        => ['label' => 'Advance',      'color' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30'],
        'short_charge'   => ['label' => 'Short charge', 'color' => 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30'],
        'payment'        => ['label' => 'Payment',      'color' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border border-sky-500/30'],
        'recovery'       => ['label' => 'Recovery',     'color' => 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30'],
    ];

    // Fix #4 — currency symbol mapping
    $currencySymbols = [
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'ZAR' => 'R ', 'CDF' => 'FC ', 'ZMW' => 'K ', 'ZWL' => 'ZWL ',
    ];
    $sym = fn(string $code) => $currencySymbols[$code] ?? ($code . ' ');
@endphp

@extends('layouts.app')

@section('title', $transporter->name . ' — Ledger')
@section('subtitle', 'Freight, advances, short charges & payments')

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
<div class="flex items-center justify-between mb-5">
    <a href="{{ route('transporters.index') }}"
       class="inline-flex items-center gap-1.5 text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        All transporters
    </a>
    <button type="button" onclick="openPaymentModal()"
            class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Record payment
    </button>
</div>

{{-- Transporter name + meta --}}
<div class="mb-5">
    <div class="flex items-center gap-2 mb-0.5">
        <h1 class="text-xl font-bold {{ $fg }}">{{ $transporter->name }}</h1>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
            {{ $transporter->is_active
                ? 'bg-emerald-600/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30'
                : 'bg-[color:var(--tw-surface-2)] ' . $muted . ' border ' . $border }}">
            {{ $transporter->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
    <p class="text-xs {{ $muted }}">
        {{ $transporter->type === 'intl' ? 'International transporter' : ($transporter->type === 'local' ? 'Local transporter' : 'Transporter') }}
        @if($transporter->city || $transporter->country)
            · {{ $transporter->city }}{{ $transporter->city && $transporter->country ? ', ' : '' }}{{ $transporter->country }}
        @endif
        @if($transporter->contact_person)
            · {{ $transporter->contact_person }}
        @endif
    </p>
</div>

{{-- Balance summary --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Freight earned</div>
        <div class="text-base font-bold {{ $fg }}">{{ $sym($currency) }}{{ number_format($freightTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Gross from deliveries</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Advances paid</div>
        <div class="text-base font-bold text-amber-500">{{ $sym($currency) }}{{ number_format($advanceTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Upfront payments made</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Short charges</div>
        <div class="text-base font-bold text-rose-500">{{ $sym($currency) }}{{ number_format($shortChargeTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Deducted for excess loss</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Payments made</div>
        <div class="text-base font-bold text-sky-500">{{ $sym($currency) }}{{ number_format($paymentTotal, 2) }}</div>
        <div class="text-[10px] {{ $muted }}">Settled invoices</div>
    </div>
    <div class="rounded-2xl border {{ $netPayable > 0.005 ? 'border-amber-500/40' : $border }} {{ $surface }} p-4 sm:col-span-1 col-span-2">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide mb-1">Net payable</div>
        @if(abs($netPayable) < 0.005)
            <div class="text-base font-bold text-emerald-500">Settled</div>
            <div class="text-[10px] {{ $muted }}">Nothing outstanding</div>
        @elseif($netPayable > 0)
            <div class="text-base font-bold text-amber-500">{{ $sym($currency) }}{{ number_format($netPayable, 2) }}</div>
            <div class="text-[10px] {{ $muted }}">Still owed to transporter</div>
        @else
            <div class="text-base font-bold text-emerald-500">{{ $sym($currency) }}{{ number_format(abs($netPayable), 2) }} overpaid</div>
            <div class="text-[10px] {{ $muted }}">Credit on account</div>
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
            <div class="text-xs {{ $muted }}">No entries yet. Entries are created automatically when import trucks are delivered, or when you record a payment above.</div>
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
                            // Fix #3 — resolve clickable ref link
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
                            <td class="py-2.5 pr-3 {{ $fg }} max-w-xs">
                                {{ $entry->description }}
                            </td>
                            <td class="py-2.5 pr-3 whitespace-nowrap">
                                @if($refLabel)
                                    @if($refUrl)
                                        <a href="{{ $refUrl }}"
                                           class="font-mono text-[10px] text-[color:var(--tw-accent)] hover:underline">
                                            {{ $refLabel }}
                                        </a>
                                    @else
                                        <span class="font-mono text-[10px] {{ $muted }}">{{ $refLabel }}</span>
                                    @endif
                                @else
                                    <span class="{{ $muted }}">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-5 text-right font-semibold whitespace-nowrap">
                                @if($isDebit)
                                    <span class="{{ $fg }}">{{ $sym($entry->currency) }}{{ number_format($entry->amount, 2) }}</span>
                                @else
                                    <span class="text-rose-500">− {{ $sym($entry->currency) }}{{ number_format(abs($entry->amount), 2) }}</span>
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

{{-- ── Record payment modal ── --}}
<div id="paymentModal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">

        <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
            <div class="text-sm font-semibold {{ $fg }}">Record payment</div>
            <button type="button" onclick="closePaymentModal()"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('transporters.payments.store', $transporter) }}" class="p-5 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Amount</label>
                <div class="flex items-center gap-2">
                    <span class="h-10 px-3 flex items-center rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} whitespace-nowrap select-none">
                        {{ $sym($currency) }}{{ $currency }}
                    </span>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           placeholder="0.00"
                           class="flex-1 h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
                </div>
                <p class="text-[10px] {{ $muted }} mt-1">Currency locked to this transporter's default ({{ $currency }}).</p>
            </div>

            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Payment date</label>
                <input type="date" name="entry_date" required
                       value="{{ date('Y-m-d') }}"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>

            <div>
                <label class="block text-xs font-semibold {{ $fg }} mb-1">Note <span class="{{ $muted }}">(optional)</span></label>
                <input type="text" name="description"
                       placeholder="e.g. Bank transfer, Ref #12345"
                       class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>

            @if($errors->any())
                <div class="text-xs text-rose-500 space-y-0.5">
                    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-1">
                <button type="button" onclick="closePaymentModal()"
                        class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                        class="h-9 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    Save payment
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openPaymentModal() {
    document.getElementById('paymentModal').classList.remove('hidden');
}
function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}
document.getElementById('paymentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});
@if($errors->any())
openPaymentModal();
@endif
</script>
@endpush

@endsection
