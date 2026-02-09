{{-- resources/views/purchases/show.blade.php --}}

@php
  /** @var \App\Models\Purchase $purchase */
  $purchase = $purchase;

  // Theme tokens (from your app.css)
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  // Labels
  $typeLabel = match($purchase->type) {
    'import' => 'Import',
    'local_depot' => 'Local depot',
    'cross_dock' => 'Cross dock',
    default => ucfirst(str_replace('_',' ', (string) $purchase->type)),
  };

  // Status pill (keep your good dark look; only tokenise draft)
  $statusPill = match($purchase->status) {
    'draft' => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'confirmed' => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-100',
    default => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };

  $qty   = (float) ($purchase->qty ?? 0);
  $unit  = (float) ($purchase->unit_price ?? 0);
  $total = $qty * $unit;

  // Supplier display (adjust if you store it differently)
  $supplierName = $purchase->supplier_name ?? ($purchase->supplier?->name ?? ($purchase->supplier ?? '—'));
@endphp

@extends('layouts.app')

@section('title', 'Purchase')
@section('subtitle', 'Review and confirm')

@section('content')

<div class="flex flex-col gap-4">

  {{-- Header --}}
  <div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="flex items-center gap-3">
        <h1 class="text-xl font-semibold {{ $fg }}">
          Purchase #{{ $purchase->display_ref ?? $purchase->id }}
        </h1>

        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold border-emerald-600 bg-emerald-500 text-white">
          {{ ucfirst($purchase->status) }}
        </span>

        @if($purchase->batch_id)
          <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }} px-2.5 py-1 text-xs font-semibold {{ $fg }}">
            Batch #{{ $purchase->batch_id }}
          </span>
        @endif
      </div>

      <p class="mt-1 text-sm {{ $muted }}">
        Review key details and confirm when ready. Confirming creates a Batch and routes it into the correct workflow.
      </p>

     
    </div>

    <div class="shrink-0 flex items-center gap-2">
      <a href="{{ route('purchases.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $fg }}
                hover:bg-[color:var(--tw-surface)]">
        <span class="text-base">←</span>
        Back
      </a>

      @if($purchase->status === 'draft')
        <form method="POST" action="{{ route('purchases.confirm', $purchase) }}">
          @csrf
          <button type="submit"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600
                         text-sm font-semibold text-white hover:bg-emerald-500/20">
            Confirm
            <span class="text-emerald-200/90">→</span>
          </button>
        </form>
      @else
        <span class="inline-flex items-center h-10 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }}">
          Confirmed
        </span>
      @endif
    </div>
  </div>

  @if(session('status'))
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 dark:bg-emerald-500/20 p-3 text-sm text-emerald-900 dark:text-emerald-100">
      {!! nl2br(e(session('status'))) !!}
    </div>
  @endif

  {{-- Main card --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
    <div class="grid gap-4 sm:grid-cols-2">

      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Supplier</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $supplierName }}</div>
      </div>

      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Type</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $typeLabel }}</div>
      </div>

      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Date</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->purchase_date?->format('Y-m-d') ?? '—' }}</div>
      </div>

      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Quantity</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }}">
          {{ number_format($qty, 3) }} <span class="text-xs {{ $muted }}">L</span>
        </div>
      </div>

      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Unit price</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }}">
          <span class="{{ $muted }}">{{ strtoupper($purchase->currency ?? 'USD') }}</span>
          {{ number_format($unit, 6) }}
        </div>
      </div>

      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Estimated total</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }}">
          <span class="{{ $muted }}">{{ strtoupper($purchase->currency ?? 'USD') }}</span>
          {{ number_format($total, 2) }}
        </div>
      </div>

      @if($purchase->type === 'local_depot')
        <div class="sm:col-span-2 rounded-xl border {{ $border }} {{ $surface2 }} p-3">
          <div class="text-[11px] {{ $muted }}">Depot</div>
          <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->depot_id ?? '—' }}</div>
          {{-- Later: show depot name via relationship --}}
        </div>
      @endif

      <div class="sm:col-span-2 rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Notes</div>
        <div class="mt-1 text-sm {{ $fg }}">{{ $purchase->notes ?: '—' }}</div>
      </div>
    </div>

    {{-- Workflow hint --}}
    <div class="mt-4 rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
      @if($purchase->type === 'import')
        <span class="{{ $muted }}">After confirmation:</span>
        this purchase waits for nominations/offload to be received into a depot.
      @elseif($purchase->type === 'local_depot')
        <span class="{{ $muted }}">After confirmation:</span>
        receive it into the selected depot from Depot Stock.
      @else
        <span class="{{ $muted }}">After confirmation:</span>
        receipt into <span class="font-semibold">CROSS DOCK</span> immediately, ready for direct sale.
      @endif
    </div>

    {{-- Secondary actions --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">
      <a href="{{ route('purchases.index') }}"
         class="inline-flex items-center h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $fg }}
                hover:bg-[color:var(--tw-surface)]">
        ← Back to list
      </a>

   
    </div>
  </div>

</div>
@endsection