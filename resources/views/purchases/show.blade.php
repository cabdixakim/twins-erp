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

  // Status pill — uses semantic CSS classes from app.css (s-*)
  $statusPill = match($purchase->status) {
    'draft'       => 's-slate',
    'confirmed'   => 's-green',
    'nominated'   => 's-amber',
    'received'    => 's-green',
    'transferred' => 's-blue',
    'dispatched'  => 's-purple',
    'cancelled'   => 's-rose',
    'voided'      => 's-rose',
    default       => 's-slate',
  };

  $qty   = (float) ($purchase->qty ?? 0);
  $unit  = (float) ($purchase->unit_price ?? 0);
  $total = $qty * $unit;

  $currency     = strtoupper($purchase->currency ?? 'USD');

  // Supplier display
  $supplierName = $purchase->supplier_name ?? ($purchase->supplier?->name ?? ($purchase->supplier ?? '—'));

  // Safe display values (won't crash even if relations missing)
  $productName = data_get($purchase, 'product.name') ?: ('Product #' . (int)($purchase->product_id ?? 0));
  $depotName   = data_get($purchase, 'depot.name') ?: ($purchase->depot_id ? ('Depot #' . (int)$purchase->depot_id) : '—');

  $ref = $purchase->reference ?? ($purchase->display_ref ?? $purchase->id);
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
          Purchase #{{ $ref }}
        </h1>

        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusPill }}">
          {{ ucfirst((string)$purchase->status) }}
        </span>

        @if($purchase->batch_id)
          <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }} px-2.5 py-1 text-xs font-semibold {{ $fg }}">
            Batch #{{ $purchase->batch_id }}
          </span>
        @endif
      </div>

      <p class="mt-1 text-sm {{ $muted }}">
        {{ $typeLabel }} · {{ ucfirst((string)$purchase->status) }}
      </p>
    </div>

    <div class="shrink-0 flex flex-wrap items-center gap-2">
      <a href="{{ route('purchases.index') }}"
         class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $fg }}
                hover:bg-[color:var(--tw-surface)] transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
        Back
      </a>

      {{-- DRAFT ACTIONS --}}
      @if($purchase->status === 'draft')
        <a href="{{ route('purchases.edit', $purchase) }}"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $fg }}
                  hover:bg-[color:var(--tw-surface)] transition">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit
        </a>

        {{-- Confirm button --}}
        <form method="POST" action="{{ route('purchases.confirm', $purchase) }}" id="confirmForm">
          @csrf
          <button type="button" id="btnConfirm"
                  class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-emerald-500/40 bg-emerald-600
                         text-sm font-semibold text-white hover:bg-emerald-500 transition">
            Confirm
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </button>
        </form>
      @endif

      {{-- POST-DRAFT ACTIONS --}}
      @if(!in_array($purchase->status, ['draft', 'cancelled', 'voided']))

        {{-- Receive button (local_depot + confirmed) --}}
        @if($purchase->type === 'local_depot' && $purchase->status === 'confirmed')
          <form method="POST" action="{{ route('purchases.receive', $purchase) }}" id="receiveForm">
            @csrf
            <button type="button" id="btnReceive"
                    class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border btn-soft-green
                           text-sm font-semibold transition">
              Receive
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4"/></svg>
            </button>
          </form>
        @endif

        {{-- Import: old nominate-vessel / deliver-to-depot buttons replaced by inline logistics section --}}

        {{-- Cross-dock actions (confirmed cross_dock) --}}
        @if($purchase->type === 'cross_dock' && $purchase->status === 'confirmed')
          <button type="button" id="btnCrossDockTransfer"
                  class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border btn-soft-blue
                         text-sm font-semibold transition">
            Transfer to depot
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
          </button>
          <button type="button" id="btnCrossDockDispatch"
                  class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border btn-soft-purple
                         text-sm font-semibold transition">
            Dispatch out
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7l7 7-7 7"/></svg>
          </button>
        @endif

      @endif

      {{-- CANCEL button (draft / confirmed / nominated without deliveries) --}}
      @if(in_array($purchase->status, ['draft', 'confirmed', 'nominated']))
        <button type="button" id="btnCancel"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border btn-soft-rose
                       text-sm font-semibold transition">
          Cancel purchase
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      @endif

      {{-- VOID / Return to seller (received local_depot) --}}
      @if($purchase->type === 'local_depot' && $purchase->status === 'received')
        <button type="button" id="btnVoid"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border btn-soft-rose
                       text-sm font-semibold transition">
          Return to seller
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
        </button>
      @endif

    </div>
  </div>

  @if(session('status'))
    <div class="alert-ok rounded-xl p-3 text-sm font-medium">
      {!! nl2br(e(session('status'))) !!}
    </div>
  @endif

  @if(session('error'))
    <div class="alert-err rounded-xl p-3 text-sm font-medium">
      {{ session('error') }}
    </div>
  @endif

  {{-- ── Main purchase card ── --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">

    {{-- Hero row: supplier + value --}}
    <div class="px-6 py-5 flex flex-wrap items-start justify-between gap-4 border-b {{ $border }}" style="background:linear-gradient(135deg,var(--tw-surface-2) 0%,var(--tw-surface) 100%)">
      <div class="min-w-0">
        <div class="flex items-center gap-2 flex-wrap mb-1">
          <span class="inline-flex items-center px-2 py-0.5 rounded-full border text-[10px] font-semibold {{ $statusPill }}">{{ ucfirst((string)$purchase->status) }}</span>
          <span class="text-[10px] font-semibold uppercase tracking-wider tw-muted">{{ $typeLabel }}</span>
          @if($purchase->batch_id)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full border {{ $border }} text-[10px] font-semibold tw-muted" style="background:var(--tw-surface)">Batch #{{ $purchase->batch_id }}</span>
          @endif
        </div>
        <div class="text-lg font-bold tw-fg truncate">{{ $supplierName }}</div>
        <div class="text-xs tw-muted mt-0.5">{{ $productName }}</div>
      </div>
      <div class="text-right flex-shrink-0">
        <div class="text-[10px] tw-muted uppercase tracking-wide mb-1">Purchase value</div>
        <div class="text-2xl font-bold tw-fg leading-none">
          {{ number_format($total, 2) }}
          <span class="text-sm font-semibold ml-1 tw-muted">{{ $currency }}</span>
        </div>
        <div class="text-[11px] tw-muted mt-1">{{ number_format($qty, 0) }} L · {{ $currency }} {{ number_format($unit, 4) }}/L</div>
      </div>
    </div>

    {{-- Metadata strip --}}
    <div class="px-6 py-3 flex flex-wrap gap-x-6 gap-y-2 border-b {{ $border }} text-xs">
      <div>
        <span class="tw-muted">Date </span>
        <span class="tw-fg font-medium">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</span>
      </div>
      @if($purchase->type === 'local_depot' && $depotName !== '—')
        <div>
          <span class="tw-muted">Depot </span>
          <span class="tw-fg font-medium">{{ $depotName }}</span>
        </div>
      @endif
      <div>
        <span class="tw-muted">Ref </span>
        <span class="tw-fg font-mono font-medium">{{ $ref }}</span>
      </div>
      @if($purchase->notes)
        <div class="w-full">
          <span class="tw-muted">Notes </span>
          <span class="tw-fg">{{ $purchase->notes }}</span>
        </div>
      @endif
    </div>

    {{-- Workflow hint --}}
    <div class="px-6 py-3 text-xs flex items-start gap-2">
      <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0 tw-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="tw-muted">
        @if($purchase->type === 'import')
          @if($purchase->status === 'draft') Confirm to create a batch, then set up the import nomination below.
          @elseif($purchase->status === 'confirmed') Set up the transporter nomination below, then add trucks to begin tracking.
          @elseif($purchase->status === 'nominated') Record truck loadings, track transit → border clearance → delivery to depot.
          @else All trucks delivered. Purchase complete.
          @endif
        @elseif($purchase->type === 'local_depot')
          After confirmation, receive into <strong class="tw-fg">{{ $depotName }}</strong>.
        @else
          After confirmation, stock goes directly into <strong class="tw-fg">Cross Dock</strong> and is ready for dispatch.
        @endif
      </span>
    </div>

  </div>

  {{-- ================================================================
       IMPORT: Nomination details card (shown once nominated)
       ================================================================ --}}
  @if($purchase->type === 'import' && $purchase->vessel_name)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
      <div class="text-sm font-semibold {{ $fg }} mb-3">Vessel nomination</div>
      <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
          <div class="text-[11px] {{ $muted }}">Vessel</div>
          <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->vessel_name }}</div>
        </div>
        @if($purchase->voyage_no)
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
            <div class="text-[11px] {{ $muted }}">Voyage</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->voyage_no }}</div>
          </div>
        @endif
        @if($purchase->bl_number)
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
            <div class="text-[11px] {{ $muted }}">BL number</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->bl_number }}</div>
          </div>
        @endif
        @if($purchase->loading_port)
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
            <div class="text-[11px] {{ $muted }}">Loading port</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->loading_port }}</div>
          </div>
        @endif
        @if($purchase->discharge_port)
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
            <div class="text-[11px] {{ $muted }}">Discharge port</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->discharge_port }}</div>
          </div>
        @endif
        @if($purchase->bl_date)
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
            <div class="text-[11px] {{ $muted }}">BL date</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->bl_date->format('Y-m-d') }}</div>
          </div>
        @endif
        @if($purchase->eta_date)
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
            <div class="text-[11px] {{ $muted }}">ETA</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $purchase->eta_date->format('Y-m-d') }}</div>
          </div>
        @endif
      </div>
    </div>
  @endif

  {{-- ================================================================
       IMPORT: Truck nominations + logistics pipeline
       ================================================================ --}}
  @if($purchase->type === 'import' && in_array($purchase->status, ['confirmed', 'nominated', 'received']))
    @include('purchases._import_logistics', [
      'purchase'          => $purchase,
      'importNomination'  => $importNomination,
      'transporters'      => $transporters,
      'depots'            => $depots,
      'qty'               => $qty,
      'currency'          => $currency,
      'volumeUnit'        => $volumeUnit ?? 'L',
    ])
  @endif

  {{-- ================================================================
       IMPORT: Delivery progress + history card (legacy movements)
       ================================================================ --}}
  @if($purchase->type === 'import' && in_array($purchase->status, ['nominated', 'received']))
    @php
      $qtyDelivered = (float) ($purchase->qty_delivered ?? 0);
      $pct = $qty > 0 ? min(100, round($qtyDelivered / $qty * 100)) : 0;
    @endphp
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
      <div class="flex items-center justify-between gap-4 mb-3">
        <div class="text-sm font-semibold {{ $fg }}">Delivery progress</div>
        <div class="text-xs {{ $muted }}">
          {{ number_format($qtyDelivered, 3) }} / {{ number_format($qty, 3) }} L
          <span class="ml-1 font-semibold {{ $fg }}">{{ $pct }}%</span>
        </div>
      </div>

      {{-- Progress bar --}}
      <div class="w-full bg-[color:var(--tw-surface-2)] rounded-full h-2 mb-4 border {{ $border }}">
        <div class="bg-emerald-500 h-2 rounded-full transition-all"
             style="width: {{ $pct }}%"></div>
      </div>

      {{-- Delivery rows --}}
      @if($importMovements->isNotEmpty())
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="{{ $muted }} border-b {{ $border }}">
                <th class="text-left py-1.5 pr-3 font-semibold">Movement</th>
                <th class="text-left py-1.5 pr-3 font-semibold">Depot</th>
                <th class="text-right py-1.5 pr-3 font-semibold">Qty (L)</th>
                <th class="text-right py-1.5 font-semibold">Date</th>
              </tr>
            </thead>
            <tbody>
              @foreach($importMovements as $mv)
                <tr class="border-b {{ $border }} last:border-0">
                  <td class="py-1.5 pr-3 {{ $fg }} font-mono">#{{ $mv->id }}</td>
                  <td class="py-1.5 pr-3 {{ $fg }}">{{ $mv->toDepot?->name ?? '—' }}</td>
                  <td class="py-1.5 pr-3 text-right {{ $fg }} font-semibold">{{ number_format($mv->qty, 3) }}</td>
                  <td class="py-1.5 text-right {{ $muted }}">{{ $mv->created_at->format('Y-m-d H:i') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="text-xs {{ $muted }}">No deliveries posted yet.</p>
      @endif
    </div>
  @endif

</div>

{{-- =========================
     LANDED COSTS / BATCH COSTS
   ========================= --}}
@if($purchase->status !== 'draft' && $purchase->batch_id)
<div class="mt-6 rounded-2xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] overflow-hidden">
  <div class="px-5 py-3 border-b border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] flex items-center justify-between">
    <span class="text-xs font-semibold text-[color:var(--tw-fg)]">Landed Costs</span>
    <button type="button" onclick="document.getElementById('addCostModal').classList.remove('hidden')"
            class="inline-flex items-center gap-1 h-7 px-3 rounded-lg border border-[color:var(--tw-border)] text-[10px] font-semibold text-[color:var(--tw-fg)] hover:bg-[color:var(--tw-surface-2)] transition">
      <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Add cost
    </button>
  </div>
  @if($batchCosts->isEmpty())
    <div class="px-5 py-5 text-xs text-[color:var(--tw-muted)]">No landed costs recorded yet — freight, duty, border charges etc.</div>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-xs text-[color:var(--tw-muted)] border-b border-[color:var(--tw-border)]">
            <th class="text-left py-2.5 pl-5 pr-3 font-semibold">Date</th>
            <th class="text-left py-2.5 pr-3 font-semibold">Category</th>
            <th class="text-left py-2.5 pr-3 font-semibold">Description</th>
            <th class="text-right py-2.5 pr-3 font-semibold">Amount</th>
            <th class="py-2.5 pr-5 font-semibold"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($batchCosts as $bc)
            @php
              $catColor = match($bc->category) {
                'freight'        => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30',
                'duty'           => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30',
                'border_charge'  => 'bg-orange-500/15 text-orange-700 dark:text-orange-300 border border-orange-500/30',
                'storage'        => 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30',
                'penalty'        => 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30',
                default          => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border border-slate-500/30',
              };
              $catLabel = ucfirst(str_replace('_', ' ', $bc->category));
            @endphp
            <tr class="border-b border-[color:var(--tw-border)] last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
              <td class="py-2.5 pl-5 pr-3 text-xs text-[color:var(--tw-muted)] whitespace-nowrap">{{ $bc->entry_date->format('d M Y') }}</td>
              <td class="py-2.5 pr-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $catColor }}">{{ $catLabel }}</span>
                @if($bc->auto_posted ?? false)
                  <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold" style="background:rgba(99,102,241,.12);color:#818cf8;border:1px solid rgba(99,102,241,.25)">auto</span>
                @endif
              </td>
              <td class="py-2.5 pr-3 text-xs text-[color:var(--tw-fg)]">{{ $bc->description }}</td>
              <td class="py-2.5 pr-3 text-right text-xs font-semibold text-[color:var(--tw-fg)]">
                {{ number_format($bc->amount, 2) }} {{ $bc->currency }}
                @if($bc->currency !== 'USD' && $bc->exchange_rate != 1)
                  <span class="text-[10px] text-[color:var(--tw-muted)] ml-1">≈ {{ number_format($bc->amount_base, 2) }} base</span>
                @endif
              </td>
              <td class="py-2.5 pr-5 text-right">
                @if($bc->auto_posted ?? false)
                  <span class="text-[10px] text-[color:var(--tw-muted)] italic">system</span>
                @else
                  <button type="button"
                          data-cost-id="{{ $bc->id }}"
                          data-cost-desc="{{ $bc->description }}"
                          data-cost-action="{{ route('purchases.batch-costs.destroy', [$purchase, $bc]) }}"
                          onclick="openDeleteCostModal(this)"
                          class="text-[color:var(--tw-muted)] hover:text-rose-500 transition">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a1 1 0 011-1h6a1 1 0 011 1v2"/>
                    </svg>
                  </button>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr class="border-t border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)]">
            <td colspan="3" class="py-2.5 pl-5 text-xs font-semibold text-[color:var(--tw-muted)]">Total landed costs</td>
            <td class="py-2.5 pr-3 text-right text-xs font-bold text-[color:var(--tw-fg)]">
              {{ number_format($batchCosts->sum('amount_base'), 2) }} (base)
            </td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  @endif
</div>

{{-- Add Cost Modal --}}
<div id="addCostModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
     style="background:rgba(0,0,0,.55)">
  <div class="w-full max-w-md rounded-2xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] shadow-2xl p-6"
       onclick="event.stopPropagation()">
    <h3 class="text-sm font-bold text-[color:var(--tw-fg)] mb-4">Add landed cost</h3>
    <form method="POST" action="{{ route('purchases.batch-costs.store', $purchase) }}" class="space-y-4">
      @csrf
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Category</label>
          <select name="category" required
                  class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
            <option value="freight">Freight</option>
            <option value="duty">Duty / Tax</option>
            <option value="border_charge">Border charge</option>
            <option value="hospitality">Hospitality</option>
            <option value="storage">Storage</option>
            <option value="penalty">Penalty</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Date</label>
          <input name="entry_date" type="date" value="{{ now()->toDateString() }}" required
                 class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
        </div>
      </div>
      <div>
        <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Description</label>
        <input name="description" type="text" required
               class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
               placeholder="e.g. Freight from Dar to Lubumbashi">
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2">
          <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Amount</label>
          <input name="amount" type="number" step="0.01" min="0.01" required
                 class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]"
                 placeholder="0.00">
        </div>
        <div>
          <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Currency</label>
          <input name="currency" value="{{ $purchase->currency ?? 'USD' }}" maxlength="8" required
                 class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
        </div>
      </div>
      <div>
        <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Exchange rate to base (1 if same currency)</label>
        <input name="exchange_rate" type="number" step="0.000001" value="1"
               class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
      </div>

      {{-- Paid by routing --}}
      <div>
        <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Who paid this cost?</label>
        <select name="paid_by_type" id="costPaidByType" onchange="updatePaidByFields()"
                class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
          <option value="self">We paid directly (no secondary payable)</option>
          <option value="depot">Depot fronted it (creates depot charge)</option>
          <option value="transporter">Clearing agent / transporter (creates advance entry)</option>
          <option value="other">Other party (free text, no auto AP)</option>
        </select>
      </div>

      <div id="paidByDepotRow" class="hidden">
        <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Depot</label>
        <select name="paid_by_id_depot"
                class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
          <option value="">— select depot —</option>
          @foreach(\App\Models\Depot::where('company_id', auth()->user()->active_company_id)->where('is_active', true)->where('is_system', false)->orderBy('name')->get() as $d)
            <option value="{{ $d->id }}">{{ $d->name }}</option>
          @endforeach
        </select>
      </div>

      <div id="paidByTransporterRow" class="hidden">
        <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Transporter / clearing agent</label>
        <select name="paid_by_id_transporter"
                class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
          <option value="">— select transporter —</option>
          @foreach(\App\Models\Transporter::where('company_id', auth()->user()->active_company_id)->where('is_active', true)->orderBy('name')->get() as $t)
            <option value="{{ $t->id }}">{{ $t->name }}</option>
          @endforeach
        </select>
      </div>

      <div id="paidByOtherRow" class="hidden">
        <label class="text-xs font-semibold text-[color:var(--tw-muted)]">Name of paying party</label>
        <input name="paid_by_name" type="text" maxlength="200" placeholder="e.g. DHL, local agent…"
               class="mt-1 w-full rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-surface)] px-3 py-2 text-sm text-[color:var(--tw-fg)] focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]">
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="button" onclick="document.getElementById('addCostModal').classList.add('hidden')"
                class="flex-1 h-9 rounded-xl border border-[color:var(--tw-border)] text-xs font-semibold text-[color:var(--tw-fg)] hover:bg-[color:var(--tw-surface-2)] transition">
          Cancel
        </button>
        <button type="submit"
                class="flex-1 h-9 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
          Save cost
        </button>
      </div>
    </form>
  </div>
</div>
<script>
function updatePaidByFields() {
  const v = document.getElementById('costPaidByType').value;
  document.getElementById('paidByDepotRow').classList.toggle('hidden', v !== 'depot');
  document.getElementById('paidByTransporterRow').classList.toggle('hidden', v !== 'transporter');
  document.getElementById('paidByOtherRow').classList.toggle('hidden', v !== 'other');
}
</script>
@endif

{{-- =========================
     CONFIRM MODAL (draft)
   ========================= --}}
@if($purchase->status === 'draft')
  <div id="confirmModal" class="fixed inset-0 z-50 hidden">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60" data-close="confirm"></div>

    <div class="tw-modal-wrap">
      <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-xl overflow-hidden">
        <div class="tw-modal-handle"></div>
        {{-- Header --}}
        <div class="p-5 border-b {{ $border }} {{ $surface2 }}">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-base font-semibold {{ $fg }}">Confirm purchase</div>
              <div class="mt-1 text-xs {{ $muted }}">
                Locks the draft, creates/attaches a batch, and routes it into the correct workflow.
              </div>
            </div>

            <button type="button" data-close="confirm"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }}
                           {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition"
                    aria-label="Close">
              ✕
            </button>
          </div>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-4">
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Purchase</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $ref }}</div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Supplier</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $supplierName }}</div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Product</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $productName }}</div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Type</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $typeLabel }}</div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Quantity</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">
                {{ number_format($qty, 3) }} <span class="text-xs {{ $muted }}">L</span>
              </div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Cost</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">
                <span class="{{ $muted }}">{{ $currency }}</span> {{ number_format($unit, 6) }}
                <span class="mx-2 {{ $muted }}">·</span>
                <span class="{{ $muted }}">{{ $currency }}</span> {{ number_format($total, 2) }}
              </div>
            </div>
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
            <div class="font-semibold">What happens after confirm</div>

            @if($purchase->type === 'import')
              <div class="mt-1 {{ $muted }}">
                Batch is created. Stock is <span class="{{ $fg }} font-semibold">not received</span> yet — it will be received during offload.
              </div>
            @elseif($purchase->type === 'local_depot')
              <div class="mt-1 {{ $muted }}">
                Batch is created. Next step is receiving into: <span class="{{ $fg }} font-semibold">{{ $depotName }}</span>.
              </div>
            @else
              <div class="mt-1 {{ $muted }}">
                Batch is created and stock is receipted into <span class="{{ $fg }} font-semibold">CROSS DOCK</span> immediately.
              </div>
            @endif
          </div>

        </div>

        {{-- Footer --}}
        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="confirm"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>

          <button type="button" id="confirmConfirm"
                  class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white
                         hover:bg-emerald-500/20 transition">
            Yes, confirm
          </button>
        </div>
      </div>
    </div>
  </div>
@endif

{{-- =========================
     RECEIVE MODAL (local_depot + confirmed)
   ========================= --}}
@if($purchase->type === 'local_depot' && $purchase->status === 'confirmed')
  <div id="receiveModal" class="fixed inset-0 z-50 hidden">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60" data-close="receive"></div>

    <div class="tw-modal-wrap">
      <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-xl overflow-hidden">
        <div class="tw-modal-handle"></div>
        {{-- Header --}}
        <div class="p-5 border-b {{ $border }} {{ $surface2 }}">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-base font-semibold {{ $fg }}">Receive into depot</div>
              <div class="mt-1 text-xs {{ $muted }}">
                Posts a receipt movement, updates depot stock, and marks the purchase as received.
              </div>
            </div>

            <button type="button" data-close="receive"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }}
                           {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition"
                    aria-label="Close">
              ✕
            </button>
          </div>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-4">
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Depot</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $depotName }}</div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Product</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $productName }}</div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Quantity</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">
                {{ number_format($qty, 3) }} <span class="text-xs {{ $muted }}">L</span>
              </div>
            </div>

            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Cost impact</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">
                <span class="{{ $muted }}">{{ $currency }}</span> {{ number_format($unit, 6) }}
                <span class="mx-2 {{ $muted }}">·</span>
                <span class="{{ $muted }}">{{ $currency }}</span> {{ number_format($total, 2) }}
              </div>
            </div>
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
            <div class="font-semibold">What will be posted</div>
            <ul class="mt-2 list-disc pl-5 {{ $muted }} space-y-1">
              <li>Inventory movement: <span class="{{ $fg }}">receipt</span> to <span class="{{ $fg }}">{{ $depotName }}</span></li>
              <li>Depot stock row updated/created for this batch (FIFO-ready)</li>
              <li>Purchase status becomes <span class="{{ $fg }}">received</span></li>
            </ul>
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $muted }}">
            Tip: this is safe to retry — duplicates should be blocked by the receipt reference.
          </div>
        </div>

        {{-- Footer --}}
        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="receive"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>

          {{-- Match "Confirmed" pill look in light mode --}}
          <button type="button" id="confirmReceive"
                  class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-[color:var(--tw-accent-soft)]
                         text-sm font-semibold text-emerald-900 dark:text-emerald-100 hover:bg-emerald-500/20 transition">
            Yes, receive
          </button>
        </div>
      </div>
    </div>
  </div>
@endif

{{-- UNDO RECEIPT MODAL (local_depot + received only) --}}
@if($purchase->type === 'local_depot' && $purchase->status === 'received')
  <div id="undoReceiptModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="tw-modal-wrap">
    <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-md overflow-hidden">
      <div class="tw-modal-handle"></div>
      <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }}">
        <div class="text-base font-semibold {{ $fg }}">Undo depot receipt</div>
        <button type="button" data-close="undo-receipt" class="text-lg {{ $muted }} hover:{{ $fg }}">✕</button>
      </div>
      <div class="p-5 space-y-3 text-sm {{ $muted }}">
        <p>This will <strong class="{{ $fg }}">reverse</strong> the receipt movement for this purchase.</p>
        <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
          <div class="font-semibold">What will happen</div>
          <ul class="mt-2 list-disc pl-5 {{ $muted }} space-y-1">
            <li>The depot stock for batch #{{ $purchase->batch_id }} will be reduced by <strong class="{{ $fg }}">{{ number_format($qty, 3) }} L</strong></li>
            <li>Purchase status returns to <strong class="{{ $fg }}">confirmed</strong></li>
            <li>The original movement is flagged as reversed</li>
          </ul>
        </div>
        <div class="rounded-xl border border-orange-500/30 bg-orange-500/10 p-3 text-xs text-orange-900 dark:text-orange-200">
          Use this only to correct a wrongly posted receipt. If stock has already been issued from this batch, the undo may leave a negative balance.
        </div>
      </div>
      <form method="POST" action="{{ route('purchases.undo-receipt', $purchase) }}" id="undoReceiptForm">
        @csrf
        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="undo-receipt"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-orange-500/30 bg-orange-500/10
                         text-sm font-semibold text-orange-900 dark:text-orange-100 hover:bg-orange-500/20 transition">
            Yes, undo receipt
          </button>
        </div>
      </form>
    </div>
    </div>
  </div>
@endif

{{-- ================================================================
     NOMINATE VESSEL MODAL (import + confirmed only)
     ================================================================ --}}
@if($purchase->type === 'import' && $purchase->status === 'confirmed')
  <div id="nominateModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/60"></div>
    <div class="tw-modal-wrap">
    <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-lg overflow-hidden">
      <div class="tw-modal-handle"></div>
      <div class="flex items-start justify-between gap-4 px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
        <div class="min-w-0">
          <div class="text-base font-semibold {{ $fg }}">Nominate vessel</div>
          <div class="mt-1 text-xs {{ $muted }}">Record shipping details. Status will move to <strong>Nominated</strong>.</div>
        </div>
        <button type="button" data-close="nominate" class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
      </div>

      <form method="POST" action="{{ route('purchases.nominate', $purchase) }}" id="nominateForm">
        @csrf
        <div class="p-5 space-y-3">
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Vessel name <span class="text-rose-500">*</span></label>
              <input type="text" name="vessel_name" required placeholder="e.g. MV Atlantic Star"
                     value="{{ old('vessel_name') }}"
                     class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Voyage no.</label>
              <input type="text" name="voyage_no" placeholder="e.g. V2024-01"
                     value="{{ old('voyage_no') }}"
                     class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">BL number</label>
              <input type="text" name="bl_number" placeholder="e.g. BL-2024-0012"
                     value="{{ old('bl_number') }}"
                     class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Loading port</label>
              <input type="text" name="loading_port" placeholder="e.g. Rotterdam"
                     value="{{ old('loading_port') }}"
                     class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Discharge port</label>
              <input type="text" name="discharge_port" placeholder="e.g. Lagos"
                     value="{{ old('discharge_port') }}"
                     class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">BL date</label>
              <input type="date" name="bl_date" value="{{ old('bl_date') }}"
                     class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>

            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">ETA</label>
              <input type="date" name="eta_date" value="{{ old('eta_date') }}"
                     class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-amber-500/40" />
            </div>
          </div>
        </div>

        <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="nominate"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-amber-500/30 bg-amber-500/10 text-sm font-semibold text-amber-900 dark:text-amber-100 hover:bg-amber-500/20 transition">
            Nominate ⚓
          </button>
        </div>
      </form>
    </div>
    </div>
  </div>
@endif

{{-- ================================================================
     IMPORT DELIVER MODAL (import + nominated only)
     ================================================================ --}}
@if($purchase->type === 'import' && $purchase->status === 'nominated')
  <div id="importDeliverModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/60"></div>
    <div class="tw-modal-wrap">
    <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-md overflow-hidden">
      <div class="tw-modal-handle"></div>
      <div class="flex items-start justify-between gap-4 px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
        <div class="min-w-0">
          <div class="text-base font-semibold {{ $fg }}">Deliver to depot</div>
          <div class="mt-1 text-xs {{ $muted }}">Post a receipt movement into the selected depot. Repeatable for partial deliveries.</div>
        </div>
        <button type="button" data-close="import-deliver" class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
      </div>

      <form method="POST" action="{{ route('purchases.import-deliver', $purchase) }}" id="importDeliverForm">
        @csrf
        <div class="p-5 space-y-3">
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Destination depot <span class="text-rose-500">*</span></label>
            <select name="depot_id" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-emerald-500/40">
              <option value="">— select depot —</option>
              @foreach($depots as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Quantity delivered (L) <span class="text-rose-500">*</span></label>
            @php $remaining = max(0, $qty - (float)($purchase->qty_delivered ?? 0)); @endphp
            <input type="number" name="qty" step="0.001" min="0.001"
                   value="{{ number_format($remaining, 3, '.', '') }}"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-emerald-500/40" />
            <div class="mt-1 text-[11px] {{ $muted }}">
              Remaining: {{ number_format($remaining, 3) }} L of {{ number_format($qty, 3) }} L ordered
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Note (optional)</label>
            <input type="text" name="note" placeholder="e.g. truck manifest, weigh-bridge ref…"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-emerald-500/40" />
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
            <div class="font-semibold">What will happen</div>
            <ul class="mt-2 list-disc pl-5 {{ $muted }} space-y-1">
              <li>Receipt movement posted into the selected depot</li>
              <li>Batch stock (FIFO-ready) updated</li>
              <li>Purchase auto-closes when fully delivered</li>
            </ul>
          </div>
        </div>

        <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="import-deliver"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-sm font-semibold text-emerald-900 dark:text-emerald-100 hover:bg-emerald-500/20 transition">
            Post delivery ↓
          </button>
        </div>
      </form>
    </div>
    </div>
  </div>
@endif

{{-- CROSS-DOCK TRANSFER MODAL (cross_dock + confirmed only) --}}
@if($purchase->type === 'cross_dock' && $purchase->status === 'confirmed')
  <div id="crossDockTransferModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="tw-modal-wrap">
    <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-md overflow-hidden">
      <div class="tw-modal-handle"></div>
      <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }}">
        <div class="text-base font-semibold {{ $fg }}">Transfer to depot</div>
        <button type="button" data-close="cross-dock-transfer" class="text-lg {{ $muted }} hover:{{ $fg }}">✕</button>
      </div>
      <form method="POST" action="{{ route('purchases.cross-dock-transfer', $purchase) }}" id="crossDockTransferForm">
        @csrf
        <div class="p-5 space-y-4 text-sm">
          <p class="{{ $muted }}">Move stock from <strong class="{{ $fg }}">Cross Dock</strong> into a physical depot.</p>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Destination depot <span class="text-rose-500">*</span></label>
            <select name="depot_id" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                           focus:outline-none focus:ring-2 focus:ring-blue-500/40">
              <option value="">— select depot —</option>
              @foreach($depots as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Quantity (L)</label>
            <input type="number" name="qty" step="0.001" min="0.001" value="{{ number_format($qty, 3, '.', '') }}"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                          focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
          </div>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Note (optional)</label>
            <input type="text" name="note" placeholder="e.g. truck manifest ref…"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                          focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
            <div class="font-semibold">What will happen</div>
            <ul class="mt-2 list-disc pl-5 {{ $muted }} space-y-1">
              <li>Issue movement posted from Cross Dock</li>
              <li>Receipt movement posted into selected depot</li>
              <li>Purchase status becomes <strong class="{{ $fg }}">transferred</strong></li>
            </ul>
          </div>
        </div>

        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="cross-dock-transfer"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-blue-500/30 bg-blue-500/10
                         text-sm font-semibold text-blue-900 dark:text-blue-100 hover:bg-blue-500/20 transition">
            Transfer →
          </button>
        </div>
      </form>
    </div>
    </div>
  </div>

  {{-- CROSS-DOCK DISPATCH MODAL --}}
  <div id="crossDockDispatchModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50"></div>
    <div class="tw-modal-wrap">
    <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-md overflow-hidden">
      <div class="tw-modal-handle"></div>
      <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }}">
        <div class="text-base font-semibold {{ $fg }}">Dispatch straight out</div>
        <button type="button" data-close="cross-dock-dispatch" class="text-lg {{ $muted }} hover:{{ $fg }}">✕</button>
      </div>
      <form method="POST" action="{{ route('purchases.cross-dock-dispatch', $purchase) }}" id="crossDockDispatchForm">
        @csrf
        <div class="p-5 space-y-4 text-sm">
          <p class="{{ $muted }}">Issue stock directly from <strong class="{{ $fg }}">Cross Dock</strong> to the client without going into a depot.</p>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">
              Client
              <span class="ml-1 text-[10px] font-normal {{ $muted }}">optional — <a href="{{ route('settings.clients.index') }}" class="underline hover:text-[color:var(--tw-accent)]" target="_blank">add client</a></span>
            </label>
            <select name="client_id"
                    class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }}
                           focus:outline-none focus:ring-2 focus:ring-purple-500/40">
              <option value="">— No client —</option>
              @foreach($clients ?? [] as $cl)
                <option value="{{ $cl->id }}">{{ $cl->name }}{{ $cl->code ? ' ('.$cl->code.')' : '' }}</option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Quantity (L)</label>
            <input type="number" name="qty" step="0.001" min="0.001" value="{{ number_format($qty, 3, '.', '') }}"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                          focus:outline-none focus:ring-2 focus:ring-purple-500/40" />
          </div>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Note (optional)</label>
            <input type="text" name="note" placeholder="e.g. delivery note ref…"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                          focus:outline-none focus:ring-2 focus:ring-purple-500/40" />
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
            <div class="font-semibold">What will happen</div>
            <ul class="mt-2 list-disc pl-5 {{ $muted }} space-y-1">
              <li>Issue movement posted from Cross Dock (stock leaves inventory)</li>
              <li>Purchase status becomes <strong class="{{ $fg }}">dispatched</strong></li>
              <li>Client is recorded on this purchase</li>
            </ul>
          </div>
        </div>

        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="cross-dock-dispatch"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-purple-500/30 bg-purple-500/10
                         text-sm font-semibold text-purple-900 dark:text-purple-100 hover:bg-purple-500/20 transition">
            Dispatch ↗
          </button>
        </div>
      </form>
    </div>
    </div>
  </div>
@endif

{{-- ========================= CANCEL MODAL ========================= --}}
@if(in_array($purchase->status, ['draft', 'confirmed', 'nominated']))
<div id="cancelModal" class="hidden fixed inset-0 z-50">
  <div class="absolute inset-0 bg-black/60"></div>
  <div class="tw-modal-wrap">
  <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-md overflow-hidden">
    <div class="tw-modal-handle"></div>
    <div class="flex items-start justify-between gap-4 px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
      <div>
        <div class="text-base font-semibold {{ $fg }}">Cancel purchase</div>
        <div class="mt-1 text-xs {{ $muted }}">
          @if($purchase->type === 'cross_dock' && $purchase->status === 'confirmed')
            The cross-dock receipt will be automatically reversed.
          @else
            This purchase will be marked as cancelled. No inventory changes.
          @endif
        </div>
      </div>
      <button type="button" data-close="cancel-purchase"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }}
                     {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="{{ route('purchases.cancel', $purchase) }}" id="cancelForm">
      @csrf
      <div class="p-5 space-y-4">
        <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 grid gap-3 sm:grid-cols-2 text-sm">
          <div>
            <div class="text-[11px] {{ $muted }}">Reference</div>
            <div class="mt-0.5 font-semibold {{ $fg }}">{{ $ref }}</div>
          </div>
          <div>
            <div class="text-[11px] {{ $muted }}">Status</div>
            <div class="mt-0.5 font-semibold {{ $fg }}">{{ ucfirst((string)$purchase->status) }}</div>
          </div>
          <div>
            <div class="text-[11px] {{ $muted }}">Product</div>
            <div class="mt-0.5 font-semibold {{ $fg }} truncate">{{ $productName }}</div>
          </div>
          <div>
            <div class="text-[11px] {{ $muted }}">Quantity</div>
            <div class="mt-0.5 font-semibold {{ $fg }}">{{ number_format($qty, 3) }} L</div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Reason (optional)</label>
          <input type="text" name="reason" placeholder="e.g. supplier withdrew, wrong product…"
                 class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                        focus:outline-none focus:ring-2 focus:ring-rose-500/30" />
        </div>
      </div>

      <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
        <button type="button" data-close="cancel-purchase"
                class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                       hover:bg-[color:var(--tw-surface-2)] transition">
          Go back
        </button>
        <button type="submit"
                class="h-10 px-4 rounded-xl border border-rose-500/40 bg-rose-600 text-sm font-semibold text-white
                       hover:bg-rose-500 transition">
          Yes, cancel
        </button>
      </div>
    </form>
  </div>
  </div>
</div>
@endif

{{-- ========================= VOID MODAL ========================= --}}
@if($purchase->type === 'local_depot' && $purchase->status === 'received')
<div id="voidModal" class="hidden fixed inset-0 z-50">
  <div class="absolute inset-0 bg-black/60"></div>
  <div class="tw-modal-wrap">
  <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-md overflow-hidden">
    <div class="tw-modal-handle"></div>
    <div class="flex items-start justify-between gap-4 px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
      <div>
        <div class="text-base font-semibold {{ $fg }}">Return to seller</div>
        <div class="mt-1 text-xs {{ $muted }}">
          Reverses the depot receipt and marks this purchase as voided. Stock is removed from inventory.
        </div>
      </div>
      <button type="button" data-close="void-purchase"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }}
                     {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="{{ route('purchases.void', $purchase) }}" id="voidForm">
      @csrf
      <div class="p-5 space-y-4">
        <div class="alert-err rounded-xl p-3 text-xs">
          This action is irreversible. The batch stock for {{ number_format($qty, 3) }} L will be removed from <strong>{{ $depotName }}</strong>.
        </div>

        <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 grid gap-3 sm:grid-cols-2 text-sm">
          <div>
            <div class="text-[11px] {{ $muted }}">Product</div>
            <div class="mt-0.5 font-semibold {{ $fg }} truncate">{{ $productName }}</div>
          </div>
          <div>
            <div class="text-[11px] {{ $muted }}">Depot</div>
            <div class="mt-0.5 font-semibold {{ $fg }} truncate">{{ $depotName }}</div>
          </div>
          <div>
            <div class="text-[11px] {{ $muted }}">Quantity to reverse</div>
            <div class="mt-0.5 font-semibold {{ $fg }}">{{ number_format($qty, 3) }} L</div>
          </div>
          <div>
            <div class="text-[11px] {{ $muted }}">Batch</div>
            <div class="mt-0.5 font-semibold {{ $fg }}">#{{ $purchase->batch_id ?? '—' }}</div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Reason (optional)</label>
          <input type="text" name="reason" placeholder="e.g. off-spec product, supplier recall…"
                 class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                        focus:outline-none focus:ring-2 focus:ring-rose-700/30" />
        </div>
      </div>

      <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
        <button type="button" data-close="void-purchase"
                class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                       hover:bg-[color:var(--tw-surface-2)] transition">
          Go back
        </button>
        <button type="submit"
                class="h-10 px-4 rounded-xl border border-rose-700/50 bg-rose-700 text-sm font-semibold text-white
                       hover:bg-rose-600 transition">
          Yes, return to seller
        </button>
      </div>
    </form>
  </div>
  </div>
</div>
@endif

<script>
  (function () {
    const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

    // Confirm modal
    const btnConfirm     = document.getElementById('btnConfirm');
    const confirmModal   = document.getElementById('confirmModal');
    const confirmConfirm = document.getElementById('confirmConfirm');
    const confirmForm    = document.getElementById('confirmForm');

    function openConfirm() {
      if (!confirmModal) return;
      confirmModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    }
    function closeConfirm() {
      if (!confirmModal) return;
      confirmModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    }

    on(btnConfirm, 'click', openConfirm);
    if (confirmModal) {
      confirmModal.querySelectorAll('[data-close="confirm"]').forEach(el => on(el, 'click', closeConfirm));
    }
    on(confirmConfirm, 'click', () => { closeConfirm(); confirmForm && confirmForm.submit(); });

    // Receive modal
    const btnReceive     = document.getElementById('btnReceive');
    const receiveModal   = document.getElementById('receiveModal');
    const confirmReceive = document.getElementById('confirmReceive');
    const receiveForm    = document.getElementById('receiveForm');

    function openReceive() {
      if (!receiveModal) return;
      receiveModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    }
    function closeReceive() {
      if (!receiveModal) return;
      receiveModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    }

    on(btnReceive, 'click', openReceive);
    if (receiveModal) {
      receiveModal.querySelectorAll('[data-close="receive"]').forEach(el => on(el, 'click', closeReceive));
    }
    on(confirmReceive, 'click', () => { closeReceive(); receiveForm && receiveForm.submit(); });

    // Undo Receipt modal
    const btnUndoReceipt  = document.getElementById('btnUndoReceipt');
    const undoReceiptModal = document.getElementById('undoReceiptModal');

    function openUndoReceipt() {
      if (!undoReceiptModal) return;
      undoReceiptModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    }
    function closeUndoReceipt() {
      if (!undoReceiptModal) return;
      undoReceiptModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    }

    on(btnUndoReceipt, 'click', openUndoReceipt);
    if (undoReceiptModal) {
      undoReceiptModal.querySelectorAll('[data-close="undo-receipt"]').forEach(el => on(el, 'click', closeUndoReceipt));
    }

    // Cross-dock transfer modal
    const btnCrossDockTransfer   = document.getElementById('btnCrossDockTransfer');
    const crossDockTransferModal = document.getElementById('crossDockTransferModal');

    function openCrossDockTransfer() {
      if (!crossDockTransferModal) return;
      crossDockTransferModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    }
    function closeCrossDockTransfer() {
      if (!crossDockTransferModal) return;
      crossDockTransferModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    }

    on(btnCrossDockTransfer, 'click', openCrossDockTransfer);
    if (crossDockTransferModal) {
      crossDockTransferModal.querySelectorAll('[data-close="cross-dock-transfer"]').forEach(el => on(el, 'click', closeCrossDockTransfer));
    }

    // Cross-dock dispatch modal
    const btnCrossDockDispatch   = document.getElementById('btnCrossDockDispatch');
    const crossDockDispatchModal = document.getElementById('crossDockDispatchModal');

    function openCrossDockDispatch() {
      if (!crossDockDispatchModal) return;
      crossDockDispatchModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    }
    function closeCrossDockDispatch() {
      if (!crossDockDispatchModal) return;
      crossDockDispatchModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    }

    on(btnCrossDockDispatch, 'click', openCrossDockDispatch);
    if (crossDockDispatchModal) {
      crossDockDispatchModal.querySelectorAll('[data-close="cross-dock-dispatch"]').forEach(el => on(el, 'click', closeCrossDockDispatch));
    }

    // Nominate vessel modal
    const btnNominate   = document.getElementById('btnNominate');
    const nominateModal = document.getElementById('nominateModal');

    function openNominate()  { if (!nominateModal) return; nominateModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeNominate() { if (!nominateModal) return; nominateModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnNominate, 'click', openNominate);
    if (nominateModal) {
      nominateModal.querySelectorAll('[data-close="nominate"]').forEach(el => on(el, 'click', closeNominate));
    }

    // Import deliver modal
    const btnImportDeliver   = document.getElementById('btnImportDeliver');
    const importDeliverModal = document.getElementById('importDeliverModal');

    function openImportDeliver()  { if (!importDeliverModal) return; importDeliverModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeImportDeliver() { if (!importDeliverModal) return; importDeliverModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnImportDeliver, 'click', openImportDeliver);
    if (importDeliverModal) {
      importDeliverModal.querySelectorAll('[data-close="import-deliver"]').forEach(el => on(el, 'click', closeImportDeliver));
    }

    // Cancel purchase modal
    const btnCancel   = document.getElementById('btnCancel');
    const cancelModal = document.getElementById('cancelModal');

    function openCancel()  { if (!cancelModal) return; cancelModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeCancel() { if (!cancelModal) return; cancelModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnCancel, 'click', openCancel);
    if (cancelModal) {
      cancelModal.querySelectorAll('[data-close="cancel-purchase"]').forEach(el => on(el, 'click', closeCancel));
    }

    // Void / Return to seller modal
    const btnVoid   = document.getElementById('btnVoid');
    const voidModal = document.getElementById('voidModal');

    function openVoid()  { if (!voidModal) return; voidModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeVoid() { if (!voidModal) return; voidModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnVoid, 'click', openVoid);
    if (voidModal) {
      voidModal.querySelectorAll('[data-close="void-purchase"]').forEach(el => on(el, 'click', closeVoid));
    }

    // Delete cost modal
    const deleteCostModal = document.getElementById('deleteCostModal');
    const deleteCostForm  = document.getElementById('deleteCostForm');
    const deleteCostDesc  = document.getElementById('deleteCostDesc');

    window.openDeleteCostModal = function(btn) {
      if (!deleteCostModal) return;
      deleteCostForm.action = btn.dataset.costAction;
      deleteCostDesc.textContent = btn.dataset.costDesc || 'this cost';
      deleteCostModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    };
    window.closeDeleteCostModal = function() {
      if (!deleteCostModal) return;
      deleteCostModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    };
    if (deleteCostModal) {
      deleteCostModal.querySelectorAll('[data-close="delete-cost"]').forEach(el => on(el, 'click', closeDeleteCostModal));
    }

    // ESC closes any open modal
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      closeConfirm();
      closeReceive();
      closeUndoReceipt();
      closeCrossDockTransfer();
      closeCrossDockDispatch();
      closeNominate();
      closeImportDeliver();
      closeCancel();
      closeVoid();
      if (window.closeDeleteCostModal) closeDeleteCostModal();
    });
  })();
</script>

{{-- DELETE COST MODAL --}}
<div id="deleteCostModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.6)">
  <div class="w-full max-w-sm rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
    <div class="px-5 py-4 flex items-center gap-3 border-b {{ $border }}">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3)">
        <svg class="w-4 h-4" style="color:#ef4444" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a1 1 0 011-1h6a1 1 0 011 1v2"/>
        </svg>
      </div>
      <div>
        <div class="text-sm font-semibold tw-fg">Remove landed cost?</div>
        <div class="text-xs tw-muted mt-0.5" id="deleteCostDesc"></div>
      </div>
    </div>
    <div class="px-5 py-4">
      <p class="text-sm tw-muted">This will permanently delete this cost entry. The batch cost total will be updated.</p>
    </div>
    <form id="deleteCostForm" method="POST" action="">
      @csrf @method('DELETE')
      <div class="px-5 py-4 border-t {{ $border }} flex items-center gap-2 justify-end">
        <button type="button" data-close="delete-cost"
                class="h-9 px-4 rounded-xl border {{ $border }} text-sm font-semibold tw-fg hover:opacity-80 transition">
          Cancel
        </button>
        <button type="submit"
                class="h-9 px-4 rounded-xl border text-sm font-semibold text-white transition hover:opacity-90"
                style="background:#ef4444; border-color:rgba(239,68,68,.5)">
          Remove
        </button>
      </div>
    </form>
  </div>
</div>

@endsection