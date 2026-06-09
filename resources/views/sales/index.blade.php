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
    'draft'    => 'border-gray-300 bg-gray-100 text-gray-700',
    'posted'   => 'border-emerald-500/30 bg-emerald-600/15 text-emerald-700',
    default    => 'border-gray-300 bg-gray-100 text-gray-700',
  };
@endphp

@section('title', 'Sales')
@section('subtitle', 'Draft → Posted issues stock (FIFO)')

@section('content')

@if(session('status'))
  <div class="alert-ok mb-4 rounded-xl px-4 py-3 text-sm font-semibold flex items-center gap-2">
    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

@if(session('error'))
  <div class="alert-err mb-4 rounded-xl p-3 text-sm font-medium">
    {{ session('error') }}
  </div>
@endif

<div class="grid gap-6 md:grid-cols-3">

  {{-- LEFT --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="text-sm font-semibold {{ $fg }}">Sales</div>
        <div class="mt-1 text-xs {{ $muted }}">Select a sale to view details.</div>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('sales.export') }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
          </svg>
          Export
        </a>
        <button type="button" id="btnNewSale"
          class="inline-flex items-center gap-2 h-9 px-3 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 hover:border-emerald-700 transition">
          + New
        </button>
      </div>
    </div>

    <div class="mt-4 space-y-2">
      @forelse($sales as $s)
        @php $isActive = $selectedId === $s->id; $pill = $statusPill($s->status); @endphp
        <a href="{{ route('sales.index', ['sale' => $s->id]) }}"
           class="block rounded-xl border {{ $border }} {{ $isActive ? $surface2 : '' }} p-3 hover:bg-[color:var(--tw-surface-2)] transition">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs {{ $muted }}">#{{ $s->reference }}</div>
              <div class="mt-0.5 text-sm font-semibold {{ $fg }} truncate">
                {{ $s->client_name ?: 'Client —' }}
              </div>
              <div class="mt-1 text-[11px] {{ $muted }} truncate">
                {{ $s->depot?->name ?? 'Depot' }} · {{ $s->product?->name ?? 'Product' }}
              </div>
            </div>

            <span class="shrink-0 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $pill }}">
              {{ ucfirst($s->status) }}
            </span>
          </div>
        </a>
      @empty
        <div class="text-xs {{ $muted }}">No sales yet.</div>
      @endforelse
    </div>

    <div class="mt-4">
      {{ $sales->links() }}
    </div>
  </div>

  {{-- RIGHT --}}
  <div class="md:col-span-2 space-y-4">
    @if(!$selected)
      <div class="rounded-2xl border border-dashed {{ $border }} {{ $surface }} p-6 text-center">
        <div class="text-sm {{ $fg }}">No sale selected.</div>
        <div class="mt-1 text-xs {{ $muted }}">Create one using “New”.</div>
      </div>
    @else
      @include('sales.partials.details', ['sale' => $selected])
    @endif
  </div>

</div>

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