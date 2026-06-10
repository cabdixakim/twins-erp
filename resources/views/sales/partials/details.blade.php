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
    'draft'     => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'posted'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
    'delivered' => 'border-sky-500/30 bg-sky-500/10 text-sky-400',
    'cancelled' => 'border-rose-500/30 bg-rose-500/10 text-rose-400',
    default     => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };

  $editPayload = [
    'id'              => (int) $sale->id,
    'depot_id'        => (int) $sale->depot_id,
    'product_id'      => (int) $sale->product_id,
    'client_name'     => (string) ($sale->client_name ?? ''),
    'sale_date'       => $sale->sale_date?->format('Y-m-d'),
    'currency'        => (string) ($sale->currency ?? 'USD'),
    'qty'             => (string) ($sale->qty ?? ''),
    'unit_price'      => (string) ($sale->unit_price ?? ''),
    'client_id'       => $sale->client_id ? (int) $sale->client_id : null,
    'delivery_mode'   => (string) ($sale->delivery_mode ?? 'ex_depot'),
    'transporter_id'  => $sale->transporter_id ? (int) $sale->transporter_id : null,
    'truck_no'        => (string) ($sale->truck_no ?? ''),
    'trailer_no'      => (string) ($sale->trailer_no ?? ''),
    'waybill_no'      => (string) ($sale->waybill_no ?? ''),
    'delivery_notes'  => (string) ($sale->delivery_notes ?? ''),
    'freight_amount'  => $sale->freight_amount ? (string) $sale->freight_amount : '',
    'freight_currency'=> (string) ($sale->freight_currency ?? 'USD'),
    'driver_name'     => (string) ($sale->driver_name ?? ''),
    'seal_numbers'    => (string) ($sale->seal_numbers ?? ''),
    'temperature'     => $sale->temperature !== null ? (string) $sale->temperature : '20',
    'density'         => $sale->density !== null ? (string) $sale->density : '',
  ];

  $sid         = $sale->id;
  $canEdit     = $sale->status === 'draft';
  $canPost     = $sale->status === 'draft';
  $canDn       = in_array($sale->status, ['posted','delivered']) && $sale->delivery_mode === 'delivered';
  $canPod      = $sale->status === 'posted';
  $canCancel   = in_array($sale->status, ['draft','posted']);
  $isDelivered = $sale->status === 'delivered';
@endphp

<div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
  {{-- Header row --}}
  <div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="flex items-center gap-2 flex-wrap">
        <div class="text-xs {{ $muted }}">#{{ $sale->reference }}</div>
        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusPill }}">
          {{ ucfirst($sale->status) }}
        </span>
        @if($sale->inventory_movement_id)
          <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }} px-2.5 py-1 text-xs font-semibold {{ $fg }}">
            Mvmt #{{ $sale->inventory_movement_id }}
          </span>
        @endif
      </div>
      <div class="mt-2 text-lg font-semibold {{ $fg }} truncate">{{ $sale->client_name ?: '—' }}</div>
      <div class="mt-1 text-xs {{ $muted }}">
        {{ $sale->depot?->name ?? 'Depot' }} · {{ $sale->product?->name ?? 'Product' }} · {{ $sale->sale_date?->format('Y-m-d') ?? '—' }}
      </div>
    </div>

    <div class="shrink-0 flex items-center gap-2 flex-wrap justify-end">

      {{-- Edit draft --}}
      @if($canEdit)
        <button type="button" id="btnEditSale" data-sale='@json($editPayload)'
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/>
          </svg>
          Edit
        </button>
      @endif

      {{-- Print Delivery Note --}}
      @if($canDn)
        <a href="{{ route('sales.delivery-note', $sale) }}" target="_blank"
           class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"/>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
            <rect x="6" y="14" width="12" height="8"/>
          </svg>
          Print DN
        </a>
      @endif

      {{-- Record POD --}}
      @if($canPod)
        <button type="button" id="btnPod_{{ $sid }}"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-sky-500/40 bg-sky-600/20 text-sky-300 text-xs font-semibold hover:bg-sky-500/20 transition">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          Record POD
        </button>
      @endif

      {{-- Cancel --}}
      @if($canCancel)
        <button type="button" id="btnCancel_{{ $sid }}"
                class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-rose-500/30 bg-rose-500/10 text-rose-400 text-xs font-semibold hover:bg-rose-500/20 transition">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
          Cancel
        </button>
      @endif

      {{-- Post --}}
      @if($canPost)
        @php $pFormId = 'postSaleForm_'.$sid; $pBtnId = 'btnPostSale_'.$sid; @endphp
        <form method="POST" action="{{ route('sales.post', $sale) }}" id="{{ $pFormId }}">
          @csrf
          <button type="button" id="{{ $pBtnId }}"
                  class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border border-emerald-500/40 bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-500 transition">
            Post <span class="opacity-70">→</span>
          </button>
        </form>
      @endif

    </div>
  </div>

  {{-- Financials --}}
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
      <div class="text-[11px] {{ $muted }}">COGS</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $cur }} {{ number_format((float)$sale->cogs_total, 2) }}</div>
    </div>
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Gross profit</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $cur }} {{ number_format((float)$sale->gross_profit, 2) }}</div>
    </div>
    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Delivery</div>
      <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $sale->delivery_mode === 'delivered' ? 'Delivered' : 'Ex-depot' }}</div>
    </div>
  </div>

  {{-- Transport block --}}
  @if($sale->transporter_id || $sale->delivery_mode === 'delivered')
    <div class="mt-4 rounded-xl border {{ $border }} {{ $surface2 }} p-3">
      <div class="text-[11px] {{ $muted }}">Transport details</div>
      <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs {{ $fg }}">
        <span><span class="{{ $muted }}">Transporter:</span> <span class="font-semibold">{{ $sale->transporter?->name ?? '—' }}</span></span>
        @if($sale->truck_no)<span><span class="{{ $muted }}">Truck:</span> <span class="font-semibold">{{ $sale->truck_no }}</span></span>@endif
        @if($sale->trailer_no)<span><span class="{{ $muted }}">Trailer:</span> <span class="font-semibold">{{ $sale->trailer_no }}</span></span>@endif
        @if($sale->waybill_no)<span><span class="{{ $muted }}">Waybill:</span> <span class="font-semibold">{{ $sale->waybill_no }}</span></span>@endif
        @if($sale->driver_name)<span><span class="{{ $muted }}">Driver:</span> <span class="font-semibold">{{ $sale->driver_name }}</span></span>@endif
        @if($sale->temperature !== null)<span><span class="{{ $muted }}">Temp:</span> <span class="font-semibold">{{ $sale->temperature }}°C</span></span>@endif
        @if($sale->density !== null)<span><span class="{{ $muted }}">Density:</span> <span class="font-semibold">{{ number_format($sale->density, 3) }} t/m³</span></span>@endif
        @if($sale->seal_numbers)<span><span class="{{ $muted }}">Seals:</span> <span class="font-semibold">{{ \Illuminate\Support\Str::limit($sale->seal_numbers, 50) }}</span></span>@endif
      </div>
    </div>
  @endif

  {{-- Invoice badge --}}
  @php $inv = $sale->invoice; @endphp
  @if($inv)
    @php
      $invPill = match($inv->status) {
        'paid'    => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
        'overdue' => 'border-rose-500/30 bg-rose-500/10 text-rose-400',
        'sent'    => 'border-amber-500/30 bg-amber-500/10 text-amber-400',
        'void'    => 'border-gray-400/30 bg-gray-700/30 text-gray-400',
        default   => 'border-gray-400/30 bg-gray-700/30 text-gray-400',
      };
      $invIcon = match($inv->status) {
        'paid'  => '✓ Paid',
        'void'  => '✗ Void',
        default => ucfirst($inv->status),
      };
    @endphp
    <div class="mt-4 rounded-xl border {{ $border }} {{ $surface2 }} p-3 flex items-center justify-between gap-3 flex-wrap">
      <div class="flex items-center gap-2">
        <svg class="h-4 w-4 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
        </svg>
        <div>
          <div class="text-[11px] {{ $muted }}">Invoice</div>
          <div class="text-xs font-semibold {{ $fg }}">{{ $inv->invoice_number }}</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $invPill }}">
          {{ $invIcon }}
        </span>
        <a href="{{ route('invoices.show', $inv) }}"
           class="inline-flex items-center gap-1 h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
          View invoice
          <svg class="h-3 w-3 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
    </div>
  @endif

  {{-- POD summary (delivered) --}}
  @if($isDelivered)
    <div class="mt-4 rounded-xl border border-sky-500/30 bg-sky-500/10 p-3">
      <div class="text-[11px] text-sky-400 font-semibold mb-2">POD Confirmed / Livraison confirmée</div>
      <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs {{ $fg }}">
        <span><span class="{{ $muted }}">Qty delivered:</span> <span class="font-semibold">{{ number_format((float)$sale->qty_delivered, 3) }} L</span></span>
        <span><span class="{{ $muted }}">Date:</span> <span class="font-semibold">{{ $sale->pod_received_at?->format('Y-m-d') }}</span></span>
        @if($sale->pod_notes)<span><span class="{{ $muted }}">Notes:</span> <span class="font-semibold">{{ $sale->pod_notes }}</span></span>@endif
      </div>
    </div>
  @endif
</div>

{{-- ════ POST CONFIRM MODAL ════ --}}
@if($canPost)
@php $pModalId = 'postSaleModal_'.$sid; $pBtnId2 = 'btnPostSale_'.$sid; $pCloseId = 'closePostSale_'.$sid; $pCancelId = 'cancelPostSale_'.$sid; $pConfirmId = 'confirmPostSale_'.$sid; $pFormId2 = 'postSaleForm_'.$sid; @endphp
<div id="{{ $pModalId }}" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60"></div>
  <div class="relative h-full w-full p-4 flex items-center justify-center">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-xl overflow-hidden">
      <div class="max-h-[85vh] overflow-y-auto">
        <div class="p-5 border-b {{ $border }} {{ $surface2 }} flex items-start justify-between gap-4">
          <div>
            <div class="text-base font-semibold {{ $fg }}">Post sale</div>
            <div class="mt-1 text-xs {{ $muted }}">Issues stock from depot — cannot be undone without cancellation.</div>
          </div>
          <button type="button" id="{{ $pCloseId }}" class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
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
            <div class="font-semibold mb-2">What happens</div>
            <ul class="list-disc pl-5 {{ $muted }} space-y-1">
              <li>Creates an <span class="{{ $fg }}">ISSUE</span> inventory movement</li>
              <li>Reduces depot stock + batch remaining</li>
              <li>Writes COGS consumption rows</li>
              @if($sale->freight_amount > 0)<li>Posts freight charge to transporter ledger</li>@endif
            </ul>
          </div>
        </div>
        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" id="{{ $pCancelId }}" class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">Cancel</button>
          <button type="button" id="{{ $pConfirmId }}" class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-500 transition">Yes, post</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const on=(el,ev,fn)=>el&&el.addEventListener(ev,fn);
  const modal=document.getElementById(@json($pModalId));
  const lockBody=l=>{document.documentElement.classList.toggle('overflow-hidden',!!l);document.body.classList.toggle('overflow-hidden',!!l);};
  const open=()=>{modal&&modal.classList.remove('hidden');lockBody(true);};
  const close=()=>{modal&&modal.classList.add('hidden');lockBody(false);};
  on(document.getElementById(@json($pBtnId2)),'click',open);
  on(document.getElementById(@json($pCloseId)),'click',close);
  on(document.getElementById(@json($pCancelId)),'click',close);
  on(modal,'click',e=>{if(e.target===modal||e.target===modal.firstElementChild)close();});
  on(document.getElementById(@json($pConfirmId)),'click',()=>{close();document.getElementById(@json($pFormId2))?.submit();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal&&!modal.classList.contains('hidden'))close();});
})();
</script>
@endif

{{-- ════ POD MODAL ════ --}}
@if($canPod)
@php $podModalId = 'podModal_'.$sid; $podFormId = 'podForm_'.$sid; $podCloseId = 'podClose_'.$sid; $podBtnId = 'btnPod_'.$sid; @endphp
<div id="{{ $podModalId }}" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60"></div>
  <div class="relative h-full w-full p-4 flex items-center justify-center">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-xl overflow-hidden">
      <div class="max-h-[85vh] overflow-y-auto">
        <div class="p-5 border-b {{ $border }} {{ $surface2 }} flex items-start justify-between gap-4">
          <div>
            <div class="text-base font-semibold {{ $fg }}">Record POD / Confirmer livraison</div>
            <div class="mt-1 text-xs {{ $muted }}">Record actual quantity delivered and date of proof of delivery.</div>
          </div>
          <button type="button" id="{{ $podCloseId }}" class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
        </div>
        <form id="{{ $podFormId }}" method="POST" action="{{ route('sales.pod', $sale) }}">
          @csrf
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-[11px] {{ $muted }} mb-1">Quantity delivered / Quantité livrée *</label>
              <input type="number" name="qty_delivered" step="0.001" min="0"
                     value="{{ old('qty_delivered', number_format($qty, 3, '.', '')) }}"
                     required
                     class="w-full rounded-xl border {{ $border }} bg-[color:var(--tw-bg)] px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-sky-500/30">
              <div class="mt-1 text-[11px] {{ $muted }}">Ordered: {{ number_format($qty, 3) }} L — enter actual quantity received at destination.</div>
            </div>
            <div>
              <label class="block text-[11px] {{ $muted }} mb-1">POD date / Date de réception *</label>
              <input type="date" name="pod_received_at" required
                     value="{{ old('pod_received_at', now()->format('Y-m-d')) }}"
                     class="w-full rounded-xl border {{ $border }} bg-[color:var(--tw-bg)] px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-sky-500/30">
            </div>
            <div>
              <label class="block text-[11px] {{ $muted }} mb-1">Notes (optional)</label>
              <textarea name="pod_notes" rows="2"
                        class="w-full rounded-xl border {{ $border }} bg-[color:var(--tw-bg)] px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-sky-500/30 resize-none"
                        placeholder="Remarks, shortfall reason..."></textarea>
            </div>
          </div>
          <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
            <button type="button" onclick="document.getElementById('{{ $podModalId }}').classList.add('hidden');document.documentElement.classList.remove('overflow-hidden');document.body.classList.remove('overflow-hidden');"
                    class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">Cancel</button>
            <button type="submit" class="h-10 px-4 rounded-xl border border-sky-500/30 bg-sky-600 text-sm font-semibold text-white hover:bg-sky-500 transition">Confirm delivery</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const on=(el,ev,fn)=>el&&el.addEventListener(ev,fn);
  const modal=document.getElementById(@json($podModalId));
  const lockBody=l=>{document.documentElement.classList.toggle('overflow-hidden',!!l);document.body.classList.toggle('overflow-hidden',!!l);};
  const open=()=>{modal&&modal.classList.remove('hidden');lockBody(true);};
  const close=()=>{modal&&modal.classList.add('hidden');lockBody(false);};
  on(document.getElementById(@json($podBtnId)),'click',open);
  on(document.getElementById(@json($podCloseId)),'click',close);
  on(modal,'click',e=>{if(e.target===modal||e.target===modal.firstElementChild)close();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal&&!modal.classList.contains('hidden'))close();});
})();
</script>
@endif

{{-- ════ CANCEL MODAL ════ --}}
@if($canCancel)
@php $cModalId = 'cancelModal_'.$sid; $cFormId = 'cancelForm_'.$sid; $cCloseId = 'cancelClose_'.$sid; $cBtnId = 'btnCancel_'.$sid; @endphp
<div id="{{ $cModalId }}" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60"></div>
  <div class="relative h-full w-full p-4 flex items-center justify-center">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-xl overflow-hidden">
      <div class="max-h-[85vh] overflow-y-auto">
        <div class="p-5 border-b {{ $border }} {{ $surface2 }} flex items-start justify-between gap-4">
          <div>
            <div class="text-base font-semibold {{ $fg }}">Cancel sale</div>
            <div class="mt-1 text-xs {{ $muted }}">
              @if($sale->status === 'posted')
                This will <strong class="text-rose-400">reverse the inventory issue</strong> and delete the freight ledger entry.
              @else
                The draft will be cancelled — no inventory was affected.
              @endif
            </div>
          </div>
          <button type="button" id="{{ $cCloseId }}" class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
        </div>
        <form id="{{ $cFormId }}" method="POST" action="{{ route('sales.cancel', $sale) }}">
          @csrf
          <div class="p-5">
            <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
              Sale <strong>{{ $sale->reference }}</strong> — {{ number_format($qty, 3) }} L — {{ $cur }} {{ number_format($total, 2) }}
            </div>
          </div>
          <div class="px-5 pb-5 flex items-center justify-end gap-2">
            <button type="button" onclick="document.getElementById('{{ $cModalId }}').classList.add('hidden');document.documentElement.classList.remove('overflow-hidden');document.body.classList.remove('overflow-hidden');"
                    class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">Keep sale</button>
            <button type="submit" class="h-10 px-4 rounded-xl border border-rose-500/30 bg-rose-600 text-sm font-semibold text-white hover:bg-rose-500 transition">Yes, cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  const on=(el,ev,fn)=>el&&el.addEventListener(ev,fn);
  const modal=document.getElementById(@json($cModalId));
  const lockBody=l=>{document.documentElement.classList.toggle('overflow-hidden',!!l);document.body.classList.toggle('overflow-hidden',!!l);};
  const open=()=>{modal&&modal.classList.remove('hidden');lockBody(true);};
  const close=()=>{modal&&modal.classList.add('hidden');lockBody(false);};
  on(document.getElementById(@json($cBtnId)),'click',open);
  on(document.getElementById(@json($cCloseId)),'click',close);
  on(modal,'click',e=>{if(e.target===modal||e.target===modal.firstElementChild)close();});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal&&!modal.classList.contains('hidden'))close();});
})();
</script>
@endif
