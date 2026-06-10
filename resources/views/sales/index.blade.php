{{-- resources/views/sales/index.blade.php --}}
@extends('layouts.app')

@php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $selectedId = $selected?->id ?? null;

  $fieldBase = "mt-1 w-full rounded-xl border {$border} {$surface2} p-2 text-sm {$fg} outline-none focus:ring-2 focus:ring-emerald-500/30";
  $fieldErr  = "border-rose-500/40 ring-2 ring-rose-500/20";
  $errText   = "mt-1 text-[11px] text-rose-600 font-bold";

  $statusPill = fn($s) => match($s) {
    'draft'     => 'border-gray-400/40 bg-gray-500/10 text-gray-500',
    'posted'    => 'border-emerald-500/40 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    'delivered' => 'border-sky-500/40 bg-sky-500/15 text-sky-600 dark:text-sky-400',
    'cancelled' => 'border-rose-500/40 bg-rose-500/15 text-rose-600 dark:text-rose-400',
    default     => 'border-gray-400/40 bg-gray-500/10 text-gray-500',
  };

  $invoicePill = fn($s) => match($s) {
    'paid'    => 'border-emerald-500/40 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    'overdue' => 'border-rose-500/40 bg-rose-500/15 text-rose-600 dark:text-rose-400',
    'sent'    => 'border-amber-500/40 bg-amber-500/15 text-amber-600 dark:text-amber-400',
    'void'    => 'border-gray-400/40 bg-gray-500/10 text-gray-500',
    default   => 'border-gray-400/40 bg-gray-500/10 text-gray-500',
  };

  $filterStatus = request('status', '');
  $filterSearch = request('q', '');
@endphp

@section('title', 'Sales')
@section('subtitle', 'Fuel sales — stock issues from depot')

@section('content')

@if(session('status'))
  <div class="alert-ok mb-3 rounded-xl px-4 py-2.5 text-sm font-semibold flex items-center gap-2">
    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    {{ session('status') }}
  </div>
@endif
@if(session('error'))
  <div class="alert-err mb-3 rounded-xl px-4 py-2.5 text-sm font-medium">{{ session('error') }}</div>
@endif

{{-- ═══════════════════════════════════════════════════════
     SPLIT VIEW — when a specific sale is selected
     ═══════════════════════════════════════════════════════ --}}
@if($selected)
<div class="grid gap-5 md:grid-cols-3">

  {{-- Left: compact sidebar list --}}
  <div class="rounded-xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="px-3 py-2.5 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between gap-2">
      <a href="{{ route('sales.index') }}" class="text-xs font-semibold {{ $fg }} hover:text-emerald-500 transition flex items-center gap-1">
        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        All sales
      </a>
      <button type="button" id="btnNewSale"
        class="inline-flex items-center gap-1 h-7 px-2.5 rounded-lg border border-emerald-600 bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 transition">
        + New
      </button>
    </div>
    <div class="divide-y divide-[color:var(--tw-border)]">
      @forelse($sales as $s)
        @php $isActive = $selectedId === $s->id; @endphp
        <a href="{{ route('sales.index', ['sale' => $s->id]) }}"
           class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-[color:var(--tw-surface-2)] transition {{ $isActive ? 'bg-[color:var(--tw-surface-2)]' : '' }}">
          <div class="min-w-0 flex-1">
            <div class="font-mono text-[10px] {{ $muted }}">{{ $s->reference }}</div>
            <div class="font-semibold {{ $fg }} truncate">{{ $s->client_name ?: ($s->client?->name ?: '—') }}</div>
            <div class="{{ $muted }} text-[10px]">{{ number_format((float)$s->qty, 0) }} L · {{ strtoupper($s->currency) }} {{ number_format((float)$s->total, 0) }}</div>
          </div>
          <span class="shrink-0 inline-flex items-center rounded-full border px-1.5 py-0.5 text-[9px] font-bold {{ $statusPill($s->status) }}">
            {{ ucfirst($s->status) }}
          </span>
        </a>
      @empty
        <div class="px-3 py-4 text-xs {{ $muted }}">No sales.</div>
      @endforelse
    </div>
    @if($sales->hasPages())
      <div class="px-3 py-2 border-t {{ $border }}">{{ $sales->links() }}</div>
    @endif
  </div>

  {{-- Right: detail panel --}}
  <div class="md:col-span-2">
    @include('sales.partials.details', ['sale' => $selected])
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     TABLE VIEW — default (no selection)
     ═══════════════════════════════════════════════════════ --}}
@else

{{-- Toolbar --}}
<div class="mb-3 flex flex-wrap items-center justify-between gap-2">

  {{-- Status filter tabs --}}
  <div class="flex items-center gap-1 flex-wrap">
    @foreach([''=>'All', 'draft'=>'Draft', 'posted'=>'Posted', 'delivered'=>'Delivered', 'cancelled'=>'Cancelled'] as $val => $label)
      @php
        $active = $filterStatus === $val;
        $tabClass = $active
          ? 'border-emerald-600 bg-emerald-500 text-white'
          : 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] text-[color:var(--tw-muted)] hover:bg-[color:var(--tw-surface-2)]';
      @endphp
      <a href="{{ route('sales.index', array_filter(['status' => $val ?: null, 'q' => $filterSearch ?: null])) }}"
         class="inline-flex h-8 items-center px-3 rounded-lg border text-xs font-semibold transition {{ $tabClass }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  {{-- Right controls --}}
  <div class="flex items-center gap-2">
    {{-- Search --}}
    <form method="GET" action="{{ route('sales.index') }}" class="flex items-center gap-1">
      @if($filterStatus)<input type="hidden" name="status" value="{{ $filterStatus }}">@endif
      <input type="text" name="q" value="{{ $filterSearch }}" placeholder="Search ref / client…"
             class="h-8 w-44 rounded-lg border {{ $border }} {{ $surface }} px-2.5 text-xs {{ $fg }} placeholder:{{ $muted }} focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
      <button type="submit" class="h-8 px-2 rounded-lg border {{ $border }} {{ $surface }} {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
    </form>
    <a href="{{ route('sales.export') }}"
       class="inline-flex items-center gap-1 h-8 px-2.5 rounded-lg border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
      CSV
    </a>
    <button type="button" id="btnNewSale"
      class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-emerald-600 bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 transition">
      + New Sale
    </button>
  </div>
</div>

{{-- Data table --}}
<div class="rounded-xl border {{ $border }} {{ $surface }} overflow-hidden">

  {{-- Stats bar --}}
  <div class="px-4 py-2 border-b {{ $border }} {{ $surface2 }} flex items-center gap-4 text-[11px] {{ $muted }}">
    <span><span class="font-semibold {{ $fg }}">{{ $sales->total() }}</span> sales</span>
    @if($filterStatus)
      <span>Filtered: <span class="font-semibold {{ $fg }}">{{ ucfirst($filterStatus) }}</span></span>
    @endif
    @if($filterSearch)
      <span>Search: <span class="font-semibold {{ $fg }}">"{{ $filterSearch }}"</span></span>
    @endif
  </div>

  {{-- ── DESKTOP TABLE ── --}}
  <div class="hidden md:block overflow-x-auto">
    <table class="w-full border-collapse">
      <thead>
        <tr class="{{ $surface2 }} border-b {{ $border }}">
          <th class="px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider {{ $muted }}" style="width:140px">Reference</th>
          <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider {{ $muted }}" style="width:85px">Date</th>
          <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider {{ $muted }}">Client</th>
          <th class="px-3 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider {{ $muted }}">Product / Depot</th>
          <th class="px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider {{ $muted }}" style="width:90px">Qty (L)</th>
          <th class="px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider {{ $muted }}" style="width:120px">Total</th>
          <th class="px-3 py-2.5 text-right text-[10px] font-bold uppercase tracking-wider {{ $muted }}" style="width:90px">Margin</th>
          <th class="px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider {{ $muted }}" style="width:90px">Status</th>
          <th class="px-3 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider {{ $muted }}" style="width:120px">Invoice</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sales as $i => $s)
          @php
            $inv      = $s->invoice;
            $margin   = (float)$s->total > 0 ? round((float)$s->gross_profit / (float)$s->total * 100, 1) : null;
            $rowBg    = $i % 2 === 0 ? '' : 'bg-[color:var(--tw-surface-2)]/50';
          @endphp
          <tr class="border-b {{ $border }} {{ $rowBg }} hover:bg-emerald-500/5 cursor-pointer transition-colors"
              onclick="window.location='{{ route('sales.index', ['sale' => $s->id, 'status' => $filterStatus ?: null, 'q' => $filterSearch ?: null]) }}'">

            <td class="px-4 py-2">
              <span class="font-mono text-[11px] font-semibold {{ $fg }}">{{ $s->reference }}</span>
            </td>

            <td class="px-3 py-2 whitespace-nowrap">
              <span class="text-xs {{ $muted }}">{{ $s->sale_date?->format('d M Y') ?? '—' }}</span>
            </td>

            <td class="px-3 py-2 max-w-[160px]">
              <div class="text-xs font-semibold {{ $fg }} truncate">{{ $s->client_name ?: ($s->client?->name ?: '—') }}</div>
            </td>

            <td class="px-3 py-2">
              <div class="text-xs {{ $fg }}">{{ $s->product?->name ?? '—' }}</div>
              <div class="text-[10px] {{ $muted }}">{{ $s->depot?->name ?? '—' }}</div>
            </td>

            <td class="px-3 py-2 text-right whitespace-nowrap">
              <span class="text-xs font-semibold {{ $fg }} tabular-nums">{{ number_format((float)$s->qty, 0) }}</span>
            </td>

            <td class="px-3 py-2 text-right whitespace-nowrap">
              <span class="text-xs font-semibold {{ $fg }} tabular-nums">{{ strtoupper($s->currency) }} {{ number_format((float)$s->total, 2) }}</span>
            </td>

            <td class="px-3 py-2 text-right whitespace-nowrap">
              @if($margin !== null)
                <span class="text-xs tabular-nums {{ $margin >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">{{ $margin }}%</span>
              @else
                <span class="text-xs {{ $muted }}">—</span>
              @endif
            </td>

            <td class="px-3 py-2 text-center">
              <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $statusPill($s->status) }}">
                {{ ucfirst($s->status) }}
              </span>
            </td>

            <td class="px-3 py-2 text-center" onclick="event.stopPropagation()">
              @if($inv)
                <a href="{{ route('invoices.show', $inv) }}"
                   class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold hover:opacity-75 transition {{ $invoicePill($inv->status) }}">
                  {{ $inv->invoice_number }}
                </a>
              @else
                <span class="text-[11px] {{ $muted }}">No invoice</span>
              @endif
            </td>

          </tr>
        @empty
          <tr>
            <td colspan="9" class="px-4 py-10 text-center text-sm {{ $muted }}">
              @if($filterStatus || $filterSearch)
                No sales match your filters.
                <a href="{{ route('sales.index') }}" class="ml-1 text-emerald-500 hover:underline">Clear filters</a>
              @else
                No sales yet. <button type="button" id="btnNewSaleEmpty" class="ml-1 text-emerald-500 hover:underline">Create the first one.</button>
              @endif
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ── MOBILE LIST ── compact, no cards --}}
  <div class="md:hidden">
    {{-- Mini column headers --}}
    <div class="flex items-center gap-2 px-3 py-1.5 border-b {{ $border }} {{ $surface2 }} text-[9px] font-bold uppercase tracking-wider {{ $muted }}">
      <span class="flex-1">Reference / Client</span>
      <span class="w-16 text-right">Total</span>
      <span class="w-16 text-center">Status</span>
    </div>
    <div class="divide-y divide-[color:var(--tw-border)]">
      @forelse($sales as $s)
        @php $inv = $s->invoice; @endphp
        <a href="{{ route('sales.index', ['sale' => $s->id, 'status' => $filterStatus ?: null, 'q' => $filterSearch ?: null]) }}"
           class="flex items-center gap-2 px-3 py-2 hover:bg-[color:var(--tw-surface-2)] transition">
          <div class="flex-1 min-w-0">
            <div class="flex items-baseline gap-1.5">
              <span class="font-mono text-[10px] {{ $muted }}">{{ $s->reference }}</span>
              @if($inv)
                <span class="inline-flex items-center rounded-full border px-1.5 py-0 text-[9px] font-bold {{ $invoicePill($inv->status) }}">
                  {{ strtoupper($inv->status) }}
                </span>
              @endif
            </div>
            <div class="text-xs font-semibold {{ $fg }} truncate">{{ $s->client_name ?: ($s->client?->name ?: '—') }}</div>
            <div class="text-[10px] {{ $muted }}">{{ $s->product?->name }} · {{ $s->sale_date?->format('d M Y') }}</div>
          </div>
          <div class="w-16 text-right shrink-0">
            <div class="text-xs font-semibold {{ $fg }} tabular-nums">{{ number_format((float)$s->total, 0) }}</div>
            <div class="text-[10px] {{ $muted }}">{{ strtoupper($s->currency) }}</div>
          </div>
          <div class="w-16 text-center shrink-0">
            <span class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-[9px] font-bold {{ $statusPill($s->status) }}">
              {{ ucfirst($s->status) }}
            </span>
          </div>
        </a>
      @empty
        <div class="px-3 py-6 text-center text-sm {{ $muted }}">
          @if($filterStatus || $filterSearch)
            No results. <a href="{{ route('sales.index') }}" class="text-emerald-500">Clear filters</a>
          @else
            No sales yet.
          @endif
        </div>
      @endforelse
    </div>
  </div>

  {{-- Pagination --}}
  @if($sales->hasPages())
    <div class="px-4 py-3 border-t {{ $border }} {{ $surface2 }}">{{ $sales->links() }}</div>
  @endif
</div>

@endif

{{-- ── MODAL ── --}}
@include('sales.partials.sale-modal', [
  'border'    => $border,
  'surface'   => $surface,
  'surface2'  => $surface2,
  'fg'        => $fg,
  'muted'     => $muted,
  'fieldBase' => $fieldBase,
  'fieldErr'  => $fieldErr,
  'errText'   => $errText,
  'depots'    => $depots,
  'products'  => $products,
  'transporters' => $transporters,
  'clients'   => $clients ?? collect(),
  'selected'  => $selected,
])

@push('scripts')
<script>
// Wire "btnNewSaleEmpty" (empty state button) to same modal trigger
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btnNewSaleEmpty');
  const main = document.getElementById('btnNewSale');
  if (btn && main) btn.addEventListener('click', () => main.click());
});
</script>
@endpush

@endsection
