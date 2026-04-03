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

  // Status pill (tokenised + consistent)
  $statusPill = match($purchase->status) {
    'draft'       => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'confirmed'   => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100',
    'received'    => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100',
    'transferred' => 'border-blue-500/30 bg-blue-500/10 text-blue-900 dark:text-blue-100',
    'dispatched'  => 'border-purple-500/30 bg-purple-500/10 text-purple-900 dark:text-purple-100',
    'cancelled'   => 'border-red-500/30 bg-red-500/10 text-red-900 dark:text-red-100',
    default       => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
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

      {{-- ACTIONS --}}
      @if($purchase->status === 'draft')
        {{-- Confirm button (opens modal) --}}
        <form method="POST" action="{{ route('purchases.confirm', $purchase) }}" id="confirmForm">
          @csrf
          <button type="button"
                  id="btnConfirm"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600
                         text-sm font-semibold text-white hover:bg-emerald-500/20 transition">
            Confirm
            <span class="text-emerald-200/90">→</span>
          </button>
        </form>

      @else
        {{-- Confirmed/Received pill --}}
        <span class="inline-flex items-center h-10 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }}">
          {{ ucfirst((string)$purchase->status) }}
        </span>

        {{-- Receive button (ONLY local_depot + confirmed) --}}
        @if($purchase->type === 'local_depot' && $purchase->status === 'confirmed')
          <form method="POST" action="{{ route('purchases.receive', $purchase) }}" id="receiveForm">
            @csrf
            <button type="button"
                    id="btnReceive"
                    class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30
                           bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100
                           text-sm font-semibold hover:bg-emerald-500/20 transition">
              Receive into depot
              <span class="opacity-80">↓</span>
            </button>
          </form>
        @endif

        {{-- Undo Receipt (ONLY local_depot + received) --}}
        @if($purchase->type === 'local_depot' && $purchase->status === 'received')
          <button type="button"
                  id="btnUndoReceipt"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-orange-500/30
                         bg-orange-500/10 text-orange-900 dark:text-orange-100
                         text-sm font-semibold hover:bg-orange-500/20 transition">
            Undo Receipt
            <span class="opacity-80">↩</span>
          </button>
        @endif

        {{-- Cross-dock actions (confirmed cross_dock only) --}}
        @if($purchase->type === 'cross_dock' && $purchase->status === 'confirmed')
          <button type="button"
                  id="btnCrossDockTransfer"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-blue-500/30
                         bg-blue-500/10 text-blue-900 dark:text-blue-100
                         text-sm font-semibold hover:bg-blue-500/20 transition">
            Transfer to depot
            <span class="opacity-80">→</span>
          </button>
          <button type="button"
                  id="btnCrossDockDispatch"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-purple-500/30
                         bg-purple-500/10 text-purple-900 dark:text-purple-100
                         text-sm font-semibold hover:bg-purple-500/20 transition">
            Dispatch out
            <span class="opacity-80">↗</span>
          </button>
        @endif
      @endif

    </div>
  </div>

  @if(session('status'))
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 dark:bg-emerald-500/20 p-3 text-sm text-emerald-900 dark:text-emerald-100">
      {!! nl2br(e(session('status'))) !!}
    </div>
  @endif

  @if(session('error'))
    <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 dark:bg-rose-500/20 p-3 text-sm text-rose-900 dark:text-rose-100">
      {{ session('error') }}
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
        <div class="text-[11px] {{ $muted }}">Product</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $productName }}</div>
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
          <span class="{{ $muted }}">{{ $currency }}</span>
          {{ number_format($unit, 6) }}
        </div>
      </div>

      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[11px] {{ $muted }}">Estimated total</div>
        <div class="mt-1 text-sm font-semibold {{ $fg }}">
          <span class="{{ $muted }}">{{ $currency }}</span>
          {{ number_format($total, 2) }}
        </div>
      </div>

      @if($purchase->type === 'local_depot')
        <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
          <div class="text-[11px] {{ $muted }}">Depot</div>
          <div class="mt-1 text-sm font-semibold {{ $fg }} truncate">{{ $depotName }}</div>
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

{{-- =========================
     CONFIRM MODAL (draft)
   ========================= --}}
@if($purchase->status === 'draft')
  <div id="confirmModal" class="fixed inset-0 z-50 hidden">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/60" data-close="confirm"></div>

    {{-- Center wrapper --}}
    <div class="relative h-full w-full flex items-center justify-center p-4">
      <div class="w-full max-w-xl rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
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

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $muted }}">
            Tip: if you confirm twice by mistake, the backend should stay idempotent (no duplicate receipts).
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

    {{-- Center wrapper --}}
    <div class="relative h-full w-full flex items-center justify-center p-4">
      <div class="w-full max-w-xl rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
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
  <div id="undoReceiptModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl flex flex-col overflow-hidden">
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
@endif

{{-- CROSS-DOCK TRANSFER MODAL (cross_dock + confirmed only) --}}
@if($purchase->type === 'cross_dock' && $purchase->status === 'confirmed')
  <div id="crossDockTransferModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl flex flex-col overflow-hidden">
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

  {{-- CROSS-DOCK DISPATCH MODAL --}}
  <div id="crossDockDispatchModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl flex flex-col overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }}">
        <div class="text-base font-semibold {{ $fg }}">Dispatch straight out</div>
        <button type="button" data-close="cross-dock-dispatch" class="text-lg {{ $muted }} hover:{{ $fg }}">✕</button>
      </div>
      <form method="POST" action="{{ route('purchases.cross-dock-dispatch', $purchase) }}" id="crossDockDispatchForm">
        @csrf
        <div class="p-5 space-y-4 text-sm">
          <p class="{{ $muted }}">Issue stock directly from <strong class="{{ $fg }}">Cross Dock</strong> to the customer without going into a depot.</p>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Quantity (L)</label>
            <input type="number" name="qty" step="0.001" min="0.001" value="{{ number_format($qty, 3, '.', '') }}"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                          focus:outline-none focus:ring-2 focus:ring-purple-500/40" />
          </div>

          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Note (optional)</label>
            <input type="text" name="note" placeholder="e.g. delivery note, customer ref…"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }}
                          focus:outline-none focus:ring-2 focus:ring-purple-500/40" />
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
            <div class="font-semibold">What will happen</div>
            <ul class="mt-2 list-disc pl-5 {{ $muted }} space-y-1">
              <li>Issue movement posted from Cross Dock (stock leaves inventory)</li>
              <li>Purchase status becomes <strong class="{{ $fg }}">dispatched</strong></li>
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

    // ESC closes any open modal
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      closeConfirm();
      closeReceive();
      closeUndoReceipt();
      closeCrossDockTransfer();
      closeCrossDockDispatch();
    });
  })();
</script>

@endsection