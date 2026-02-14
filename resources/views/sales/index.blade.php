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
  <div class="mb-4 rounded-xl border border-emerald-400/20 bg-emerald-100/60 text-emerald-900 px-4 py-3 text-sm font-semibold flex items-center gap-2 shadow-sm">
    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
    <span>{{ session('status') }}</span>
  </div>
@endif

@if(session('error'))
  <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-100">
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

      <button type="button" id="btnNewSale"
        class="inline-flex items-center gap-2 h-9 px-3 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 hover:border-emerald-700 transition">
        + New
      </button>
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
  'selected' => $selected,
])

@endsection