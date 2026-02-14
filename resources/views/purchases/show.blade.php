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
    'draft' => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'confirmed' => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100',
    'received' => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100',
    default => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
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

    // ESC closes any open modal
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      closeConfirm();
      closeReceive();
    });
  })();
</script>

@endsection