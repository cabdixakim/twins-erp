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
    'draft'     => 'border-gray-400/30 bg-gray-100 text-gray-600 dark:bg-gray-700/40 dark:text-gray-300',
    'posted'    => 'border-emerald-500/30 bg-emerald-600/15 text-emerald-700 dark:text-emerald-400',
    'delivered' => 'border-sky-500/30 bg-sky-500/15 text-sky-700 dark:text-sky-400',
    'cancelled' => 'border-rose-500/30 bg-rose-500/15 text-rose-700 dark:text-rose-400',
    default     => 'border-gray-400/30 bg-gray-100 text-gray-600',
  };

  $invoicePill = fn($s) => match($s) {
    'paid'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    'overdue' => 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-400',
    'sent'    => 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-400',
    'void'    => 'border-gray-400/30 bg-gray-100 text-gray-500',
    default   => 'border-gray-400/30 bg-gray-100 text-gray-500',
  };
@endphp

@section('title', 'Sales')
@section('subtitle', 'Track and manage fuel sales')

@section('content')

@if(session('status'))
  <div class="alert-ok mb-4 rounded-xl px-4 py-3 text-sm font-semibold flex items-center gap-2">
    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif
@if(session('error'))
  <div class="alert-err mb-4 rounded-xl p-3 text-sm font-medium">{{ session('error') }}</div>
@endif

{{-- ══ SPLIT VIEW when a sale is selected ══ --}}
@if($selected)
<div class="grid gap-6 md:grid-cols-3">

  {{-- LEFT: compact list --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
    <div class="flex items-center justify-between gap-3 mb-4">
      <div class="text-sm font-semibold {{ $fg }}">All Sales</div>
      <div class="flex items-center gap-2">
        <a href="{{ route('sales.export') }}"
           class="inline-flex items-center gap-1 h-8 px-2.5 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
          <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
          CSV
        </a>
        <button type="button" id="btnNewSale"
          class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 transition">
          + New
        </button>
      </div>
    </div>

    <div class="space-y-1.5">
      @forelse($sales as $s)
        @php $isActive = $selectedId === $s->id; $pill = $statusPill($s->status); @endphp
        <a href="{{ route('sales.index', ['sale' => $s->id]) }}"
           class="block rounded-xl border {{ $border }} {{ $isActive ? 'ring-2 ring-emerald-500/40 '.$surface2 : '' }} p-2.5 hover:bg-[color:var(--tw-surface-2)] transition">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <div class="text-[11px] {{ $muted }}">#{{ $s->reference }}</div>
              <div class="mt-0.5 text-xs font-semibold {{ $fg }} truncate">
                {{ $s->client_name ?: ($s->client?->name ?: '—') }}
              </div>
              <div class="mt-0.5 text-[10px] {{ $muted }}">{{ $s->product?->name }} · {{ number_format((float)$s->qty, 0) }} L</div>
            </div>
            <span class="shrink-0 inline-flex items-center rounded-full border px-1.5 py-0.5 text-[9px] font-semibold {{ $pill }}">
              {{ ucfirst($s->status) }}
            </span>
          </div>
        </a>
      @empty
        <div class="text-xs {{ $muted }}">No sales yet.</div>
      @endforelse
    </div>

    <div class="mt-3">{{ $sales->links() }}</div>
  </div>

  {{-- RIGHT: detail panel --}}
  <div class="md:col-span-2 space-y-4">
    @include('sales.partials.details', ['sale' => $selected])
  </div>

</div>

{{-- ══ TABLE VIEW by default (no selection) ══ --}}
@else
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">

  {{-- Header --}}
  <div class="px-5 py-4 border-b {{ $border }} flex items-center justify-between gap-4 flex-wrap">
    <div>
      <div class="text-sm font-semibold {{ $fg }}">Sales</div>
      <div class="text-xs {{ $muted }} mt-0.5">{{ $sales->total() }} total · click a row to view details</div>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('sales.export') }}"
         class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
        Export CSV
      </a>
      <button type="button" id="btnNewSale"
        class="inline-flex items-center gap-2 h-9 px-3 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 hover:border-emerald-700 transition">
        + New Sale
      </button>
    </div>
  </div>

  {{-- Desktop table --}}
  <div class="hidden md:block overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b {{ $border }} {{ $surface2 }} text-[11px] {{ $muted }} uppercase tracking-wide">
          <th class="px-4 py-3 text-left font-semibold">Reference</th>
          <th class="px-4 py-3 text-left font-semibold">Date</th>
          <th class="px-4 py-3 text-left font-semibold">Client</th>
          <th class="px-4 py-3 text-left font-semibold">Product · Depot</th>
          <th class="px-4 py-3 text-right font-semibold">Qty (L)</th>
          <th class="px-4 py-3 text-right font-semibold">Total</th>
          <th class="px-4 py-3 text-center font-semibold">Status</th>
          <th class="px-4 py-3 text-center font-semibold">Invoice</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-[color:var(--tw-border)]">
        @forelse($sales as $s)
          @php $pill = $statusPill($s->status); $inv = $s->invoice; @endphp
          <tr class="hover:bg-[color:var(--tw-surface-2)] transition cursor-pointer"
              onclick="window.location='{{ route('sales.index', ['sale' => $s->id]) }}'">
            <td class="px-4 py-3">
              <div class="font-mono text-xs {{ $fg }}">{{ $s->reference }}</div>
            </td>
            <td class="px-4 py-3 text-xs {{ $muted }} whitespace-nowrap">
              {{ $s->sale_date?->format('d M Y') ?? '—' }}
            </td>
            <td class="px-4 py-3 text-xs {{ $fg }}">
              {{ $s->client_name ?: ($s->client?->name ?: '—') }}
            </td>
            <td class="px-4 py-3 text-xs {{ $muted }}">
              <div>{{ $s->product?->name ?? '—' }}</div>
              <div class="text-[10px]">{{ $s->depot?->name ?? '—' }}</div>
            </td>
            <td class="px-4 py-3 text-right text-xs {{ $fg }} font-semibold whitespace-nowrap">
              {{ number_format((float)$s->qty, 0) }}
            </td>
            <td class="px-4 py-3 text-right text-xs {{ $fg }} font-semibold whitespace-nowrap">
              {{ strtoupper($s->currency) }} {{ number_format((float)$s->total, 2) }}
            </td>
            <td class="px-4 py-3 text-center">
              <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $pill }}">
                {{ ucfirst($s->status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              @if($inv)
                <a href="{{ route('invoices.show', $inv) }}"
                   onclick="event.stopPropagation()"
                   class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold hover:opacity-80 transition {{ $invoicePill($inv->status) }}">
                  {{ $inv->invoice_number }}
                </a>
              @else
                <span class="text-[10px] {{ $muted }}">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="px-4 py-8 text-center text-sm {{ $muted }}">No sales yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Mobile card list --}}
  <div class="md:hidden divide-y divide-[color:var(--tw-border)]">
    @forelse($sales as $s)
      @php $pill = $statusPill($s->status); $inv = $s->invoice; @endphp
      <a href="{{ route('sales.index', ['sale' => $s->id]) }}"
         class="block px-4 py-3 hover:bg-[color:var(--tw-surface-2)] transition">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="text-[11px] {{ $muted }}">{{ $s->reference }}</div>
            <div class="mt-0.5 text-sm font-semibold {{ $fg }} truncate">
              {{ $s->client_name ?: ($s->client?->name ?: '—') }}
            </div>
            <div class="mt-1 text-xs {{ $muted }}">
              {{ $s->product?->name }} · {{ $s->depot?->name }} · {{ $s->sale_date?->format('d M Y') }}
            </div>
            <div class="mt-1 text-xs font-semibold {{ $fg }}">
              {{ strtoupper($s->currency) }} {{ number_format((float)$s->total, 2) }}
              <span class="{{ $muted }} font-normal">({{ number_format((float)$s->qty, 0) }} L)</span>
            </div>
          </div>
          <div class="shrink-0 flex flex-col items-end gap-1.5">
            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $pill }}">
              {{ ucfirst($s->status) }}
            </span>
            @if($inv)
              <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $invoicePill($inv->status) }}">
                {{ $inv->invoice_number }}
              </span>
            @endif
          </div>
        </div>
      </a>
    @empty
      <div class="px-4 py-8 text-center text-sm {{ $muted }}">No sales yet.</div>
    @endforelse
  </div>

  @if($sales->hasPages())
    <div class="px-5 py-3 border-t {{ $border }}">{{ $sales->links() }}</div>
  @endif
</div>
@endif

{{-- Extracted modal --}}
@include('sales.partials.sale-modal', [
  'border' => $border,
  'surface' => $surface,
  'surface2' => $surface2,
  'fg' => $fg,
  'muted' => $muted,
  'fieldBase' => $fieldBase,
  'fieldErr' => $fieldErr,
  'errText' => $errText,
  'depots' => $depots,
  'products' => $products,
  'transporters' => $transporters,
  'clients' => $clients ?? collect(),
  'selected' => $selected,
])

@endsection
