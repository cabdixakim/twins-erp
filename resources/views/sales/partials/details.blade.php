@php
  /** @var \App\Models\Sale $sale */

  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $qty   = (float) ($sale->qty ?? 0);
  $unit  = (float) ($sale->unit_price ?? 0);
  $total = (float) ($sale->total ?? ($qty * $unit));
  $cur   = strtoupper($sale->currency ?? 'USD');

  $statusPill = match($sale->status) {
    'draft'  => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'posted' => 'border-emerald-500/30 bg-emerald-600/15 text-emerald-100',
    default  => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };

  $editPayload = [
    'id'            => (int) $sale->id,
    'depot_id'      => (int) $sale->depot_id,
    'product_id'    => (int) $sale->product_id,
    'client_name'   => (string) ($sale->client_name ?? ''),
    'sale_date'     => $sale->sale_date?->format('Y-m-d'),
    'currency'      => (string) ($sale->currency ?? 'USD'),
    'qty'           => (string) ($sale->qty ?? ''),
    'unit_price'    => (string) ($sale->unit_price ?? ''),
    'delivery_mode' => (string) ($sale->delivery_mode ?? 'ex_depot'),
    'transporter_id'=> $sale->transporter_id ? (int) $sale->transporter_id : null,
    'truck_no'      => (string) ($sale->truck_no ?? ''),
    'trailer_no'    => (string) ($sale->trailer_no ?? ''),
    'waybill_no'    => (string) ($sale->waybill_no ?? ''),
    'delivery_notes'=> (string) ($sale->delivery_notes ?? ''),
  ];
@endphp

<div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
  <div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="flex items-center gap-3">
        <div class="text-xs {{ $muted }}">#{{ $sale->reference }}</div>
        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $sale->status === 'posted' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700' : 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]' }}">
          {{ ucfirst($sale->status) }}
        </span>
        @if($sale->inventory_movement_id)
          <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }} px-2.5 py-1 text-xs font-semibold {{ $fg }}">
            Movement #{{ $sale->inventory_movement_id }}
          </span>
        @endif
      </div>

      <div class="mt-2 text-lg font-semibold {{ $fg }} truncate">
        {{ $sale->client_name ?: 'Client —' }}
      </div>

      <div class="mt-1 text-xs {{ $muted }}">
        {{ $sale->depot?->name ?? 'Depot' }} · {{ $sale->product?->name ?? 'Product' }} · {{ $sale->sale_date?->format('Y-m-d') ?? '—' }}
      </div>
    </div>

    <div class="shrink-0 flex items-center gap-2">

      {{-- EDIT DRAFT --}}
      @if($sale->status === 'draft')
        <button type="button"
                id="btnEditSale"
                data-sale='@json($editPayload)'
                class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $fg }}
                       hover:bg-[color:var(--tw-surface)] transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 0 4 19.5z"/>
            <path d="M14.5 6.5l3 3"/>
            <path d="M9.5 14.5l-1 4 4-1 6.5-6.5-3-3z"/>
          </svg>
          Edit
        </button>
      @endif

      {{-- POST --}}
      @if($sale->status === 'draft')
        @php $modalId = 'postSaleModal_' . $sale->id; $btnId = 'btnPostSale_' . $sale->id; $closeId = 'closePostSale_' . $sale->id; $cancelId = 'cancelPostSale_' . $sale->id; $confirmId = 'confirmPostSale_' . $sale->id; $formId = 'postSaleForm_' . $sale->id; @endphp
        <form method="POST" action="{{ route('sales.post', $sale) }}" id="{{ $formId }}">
          @csrf
          <button type="button" id="{{ $btnId }}"
            class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500/20 transition">
            Post sale
            <span class="text-emerald-200/90">→</span>
          </button>
        </form>
      @else
        <span class="inline-flex items-center h-10 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }}">
          Posted
        </span>
      @endif

    </div>
  </div>

  <div class="mt-5 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Quantity</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ number_format($qty, 3) }} <span class="text-xs {{ $muted }}">L</span></div>
    </div>
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Unit price</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $cur }} {{ number_format($unit, 6) }}</div>
    </div>
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Total</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $cur }} {{ number_format($total, 2) }}</div>
    </div>
  </div>

  <div class="mt-3 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">COGS (FIFO)</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $cur }} {{ number_format((float)$sale->cogs_total, 2) }}</div>
    </div>
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Gross profit</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $cur }} {{ number_format((float)$sale->gross_profit, 2) }}</div>
    </div>
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Delivery</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">
        {{ $sale->delivery_mode === 'delivered' ? 'Delivered' : 'Ex-depot' }}
      </div>
    </div>
  </div>

  @if($sale->delivery_mode === 'delivered')
    <div class="mt-4 rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Transport details</div>
      <div class="mt-1 text-sm {{ $fg }}">
        <span class="font-semibold">Transporter:</span> {{ $sale->transporter?->name ?? '—' }}
        <span class="mx-2 {{ $muted }}">·</span>
        <span class="font-semibold">Truck:</span> {{ $sale->truck_no ?? '—' }}
        <span class="mx-2 {{ $muted }}">·</span>
        <span class="font-semibold">Trailer:</span> {{ $sale->trailer_no ?? '—' }}
      </div>
    </div>
  @endif
</div>

{{-- POST CONFIRM MODAL --}}
@if($sale->status === 'draft')
@php $modalId = 'postSaleModal_' . $sale->id; $btnId = 'btnPostSale_' . $sale->id; $closeId = 'closePostSale_' . $sale->id; $cancelId = 'cancelPostSale_' . $sale->id; $confirmId = 'confirmPostSale_' . $sale->id; $formId = 'postSaleForm_' . $sale->id; @endphp
<div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60"></div>

  <div class="relative h-full w-full p-4 flex items-center justify-center">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-xl overflow-hidden">
      <div class="max-h-[85vh] overflow-y-auto">
        <div class="p-5 border-b {{ $border }} {{ $surface2 }}">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-base font-semibold {{ $fg }}">Post sale</div>
              <div class="mt-1 text-xs {{ $muted }}">This will issue stock FIFO and cannot be undone.</div>
            </div>
            <button type="button" id="{{ $closeId }}"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-(--tw-surface-2) transition">✕</button>
          </div>
        </div>

        <div class="p-5 space-y-3">
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Depot</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $sale->depot?->name ?? '—' }}</div>
            </div>
            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Product</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $sale->product?->name ?? '—' }}</div>
            </div>
            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Qty to issue</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ number_format($qty, 3) }} <span class="text-xs {{ $muted }}">L</span></div>
            </div>
            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
              <div class="text-[11px] {{ $muted }}">Sale total</div>
              <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $cur }} {{ number_format($total, 2) }}</div>
            </div>
          </div>

          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
            <div class="font-semibold">What will happen</div>
            <ul class="mt-2 list-disc pl-5 {{ $muted }} space-y-1">
              <li>Creates an <span class="{{ $fg }}">ISSUE</span> movement</li>
              <li>Consumes depot stock by <span class="{{ $fg }}">FIFO</span> (per batch)</li>
              <li>Writes consumption rows (audit proof)</li>
              <li>Updates batch remaining + depot stock</li>
            </ul>
          </div>
        </div>

        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" id="{{ $cancelId }}"
            class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-(--tw-surface-2) transition">
            Cancel
          </button>
          <button type="button" id="{{ $confirmId }}"
            class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-500/20 transition">
            Yes, post
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  const btn = document.getElementById(@json($btnId));
  const modal = document.getElementById(@json($modalId));
  const closeBtn = document.getElementById(@json($closeId));
  const cancelBtn = document.getElementById(@json($cancelId));
  const confirmBtn = document.getElementById(@json($confirmId));
  const form = document.getElementById(@json($formId));

  const lockBody = (locked) => {
    document.documentElement.classList.toggle('overflow-hidden', !!locked);
    document.body.classList.toggle('overflow-hidden', !!locked);
  };

  const open = () => { modal && modal.classList.remove('hidden'); lockBody(true); };
  const close = () => { modal && modal.classList.add('hidden'); lockBody(false); };

  on(btn, 'click', open);
  on(closeBtn, 'click', close);
  on(cancelBtn, 'click', close);

  on(modal, 'click', (e) => {
    if (e.target === modal || e.target === modal.firstElementChild) close();
  });

  on(confirmBtn, 'click', () => { close(); form && form.submit(); });

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) close(); });
})();
</script>
@endif