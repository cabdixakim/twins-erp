{{-- resources/views/purchases/show.blade.php --}}

@php
  /** @var \App\Models\Purchase $purchase */
  $purchase = $purchase;

  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $typeLabel = match($purchase->type) {
    'import'      => 'Import',
    'local_depot' => 'Local depot',
    'cross_dock'  => 'Cross dock',
    default       => ucfirst(str_replace('_',' ', (string) $purchase->type)),
  };

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

  $qty      = (float) ($purchase->qty ?? 0);
  $unit     = (float) ($purchase->unit_price ?? 0);
  $total    = $qty * $unit;
  $currency = strtoupper($purchase->currency ?? 'USD');

  $supplierName = $purchase->supplier_name ?? ($purchase->supplier?->name ?? ($purchase->supplier ?? '—'));
  $productName  = data_get($purchase, 'product.name') ?: ('Product #' . (int)($purchase->product_id ?? 0));
  $depotName    = data_get($purchase, 'depot.name') ?: ($purchase->depot_id ? ('Depot #' . (int)$purchase->depot_id) : '—');
  $ref          = $purchase->reference ?? ($purchase->display_ref ?? $purchase->id);

  // Two-column import layout (confirmed/nominated/received only — not draft/cancelled/voided)
  $importTwoCol = $purchase->type === 'import'
    && !in_array($purchase->status, ['draft', 'cancelled', 'voided']);
@endphp

@extends('layouts.app')

@section('title', (string) $ref)
@section('subtitle', $supplierName . ' · ' . $typeLabel . ' · ' . ucfirst((string)$purchase->status))

@section('content')

<div class="flex flex-col gap-4">

  {{-- ══ Action / nav bar ══════════════════════════════════════════════ --}}
  <div class="flex flex-wrap items-center justify-between gap-3">

    {{-- Left: breadcrumb + pills --}}
    <div class="flex flex-wrap items-center gap-2 min-w-0">
      <a href="{{ route('purchases.index') }}"
         class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg border {{ $border }} {{ $surface2 }}
                text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface)] transition shrink-0">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
        </svg>
        Purchases
      </a>

      <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusPill }}">
        {{ ucfirst((string)$purchase->status) }}
      </span>

      <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }}
                   px-2 py-0.5 text-[10px] font-semibold {{ $muted }}">
        {{ $typeLabel }}
      </span>

      @if($purchase->batch_id)
        <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }}
                     px-2 py-0.5 text-[10px] font-semibold {{ $muted }}">
          Batch #{{ $purchase->batch_id }}
        </span>
      @endif
    </div>

    {{-- Right: action buttons ──────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-2 shrink-0">

      {{-- DRAFT: Edit + Confirm --}}
      @if($purchase->status === 'draft')
        <a href="{{ route('purchases.edit', $purchase) }}"
           class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border {{ $border }} {{ $surface2 }}
                  text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface)] transition">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
          Edit
        </a>
        <form method="POST" action="{{ route('purchases.confirm', $purchase) }}" id="confirmForm">
          @csrf
          <button type="button" id="btnConfirm"
                  class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border border-emerald-500/40
                         bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-500 transition">
            Confirm
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
          </button>
        </form>
      @endif

      @if(!in_array($purchase->status, ['draft', 'cancelled', 'voided']))

        {{-- Receive (local_depot + confirmed) --}}
        @if($purchase->type === 'local_depot' && $purchase->status === 'confirmed')
          <form method="POST" action="{{ route('purchases.receive', $purchase) }}" id="receiveForm">
            @csrf
            <button type="button" id="btnReceive"
                    class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border btn-soft-green
                           text-xs font-semibold transition">
              Receive
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0 0l-4-4m4 4l4-4"/>
              </svg>
            </button>
          </form>
        @endif

        {{-- Undo receipt (local_depot + received) --}}
        @if($purchase->type === 'local_depot' && $purchase->status === 'received')
          <button type="button" id="btnUndoReceipt"
                  class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border {{ $border }} {{ $surface2 }}
                         text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface)] transition">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
            </svg>
            Undo receipt
          </button>
        @endif

        {{-- Cross-dock actions --}}
        @if($purchase->type === 'cross_dock' && $purchase->status === 'confirmed')
          <button type="button" id="btnCrossDockTransfer"
                  class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border btn-soft-blue
                         text-xs font-semibold transition">
            Transfer to depot
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
            </svg>
          </button>
          <button type="button" id="btnCrossDockDispatch"
                  class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border btn-soft-purple
                         text-xs font-semibold transition">
            Dispatch out
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-7-7l7 7-7 7"/>
            </svg>
          </button>
        @endif

      @endif

      {{-- Cancel --}}
      @if(in_array($purchase->status, ['draft', 'confirmed', 'nominated']))
        <button type="button" id="btnCancel"
                class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border btn-soft-rose
                       text-xs font-semibold transition">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
          Cancel
        </button>
      @endif

      {{-- Return to seller --}}
      @if($purchase->type === 'local_depot' && $purchase->status === 'received')
        <button type="button" id="btnVoid"
                class="inline-flex items-center gap-1.5 h-8 px-3 rounded-xl border btn-soft-rose
                       text-xs font-semibold transition">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
          </svg>
          Return to seller
        </button>
      @endif

    </div>
  </div>

  {{-- Flash messages --}}
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

  {{-- ══════════════════════════════════════════════════════════════════
       IMPORT — two-column: details sidebar + logistics main
       ══════════════════════════════════════════════════════════════════ --}}
  @if($importTwoCol)

  <div class="grid lg:grid-cols-12 gap-4 items-start">

    {{-- ── Sidebar (details) ─────────────────────────────────────── --}}
    <aside class="lg:col-span-4 xl:col-span-3 space-y-3 lg:sticky lg:top-4">

      {{-- Purchase facts --}}
      <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <div class="px-4 py-4 border-b {{ $border }}"
             style="background:linear-gradient(135deg,var(--tw-surface-2) 0%,var(--tw-surface) 100%)">
          <div class="text-[10px] uppercase tracking-wider {{ $muted }} font-semibold">{{ $typeLabel }}</div>
          <div class="mt-1 text-base font-bold {{ $fg }} leading-tight truncate">{{ $supplierName }}</div>
          <div class="mt-0.5 text-xs {{ $muted }}">{{ $productName }}</div>
        </div>

        <dl class="divide-y {{ $border }}">
          <div class="px-4 py-2.5 flex items-center justify-between gap-2">
            <dt class="text-xs {{ $muted }}">Quantity</dt>
            <dd class="text-sm font-semibold {{ $fg }}">{{ number_format($qty, 0) }}<span class="text-xs font-normal {{ $muted }} ml-1">L</span></dd>
          </div>
          <div class="px-4 py-2.5 flex items-center justify-between gap-2">
            <dt class="text-xs {{ $muted }}">Unit price</dt>
            <dd class="text-sm font-semibold {{ $fg }}">{{ number_format($unit, 4) }}<span class="text-xs font-normal {{ $muted }} ml-1">{{ $currency }}/L</span></dd>
          </div>
          <div class="px-4 py-2.5 flex items-center justify-between gap-2">
            <dt class="text-xs {{ $muted }}">Total value</dt>
            <dd class="text-base font-bold {{ $fg }}">{{ number_format($total, 2) }}<span class="text-xs font-normal {{ $muted }} ml-1">{{ $currency }}</span></dd>
          </div>
          <div class="px-4 py-2.5 flex items-center justify-between gap-2">
            <dt class="text-xs {{ $muted }}">Date</dt>
            <dd class="text-sm {{ $fg }}">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</dd>
          </div>
          <div class="px-4 py-2.5 flex items-center justify-between gap-2">
            <dt class="text-xs {{ $muted }}">Reference</dt>
            <dd class="text-xs font-mono {{ $fg }}">{{ $ref }}</dd>
          </div>
          @if($purchase->batch_id)
          <div class="px-4 py-2.5 flex items-center justify-between gap-2">
            <dt class="text-xs {{ $muted }}">Batch</dt>
            <dd class="text-xs font-mono font-semibold {{ $fg }}">#{{ $purchase->batch_id }}</dd>
          </div>
          @endif
          @if($purchase->notes)
          <div class="px-4 py-2.5">
            <dt class="text-[11px] {{ $muted }} mb-0.5">Notes</dt>
            <dd class="text-xs {{ $fg }}">{{ $purchase->notes }}</dd>
          </div>
          @endif
        </dl>
      </div>

      {{-- Delivery progress (inline in sidebar for import) --}}
      @if(in_array($purchase->status, ['nominated', 'received']))
        @php
          $qtyDelivered = (float) ($purchase->qty_delivered ?? 0);
          $pct = $qty > 0 ? min(100, round($qtyDelivered / $qty * 100)) : 0;
        @endphp
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
          <div class="px-4 py-3 {{ $surface2 }} border-b {{ $border }} flex items-center justify-between gap-2">
            <span class="text-xs font-semibold {{ $fg }}">Delivery progress</span>
            <span class="text-xs {{ $muted }}">{{ number_format($qtyDelivered, 0) }} / {{ number_format($qty, 0) }} L
              <span class="font-semibold {{ $fg }}">{{ $pct }}%</span>
            </span>
          </div>
          <div class="px-4 py-3">
            <div class="w-full rounded-full h-2 {{ $surface2 }} border {{ $border }} overflow-hidden">
              <div class="bg-emerald-500 h-full rounded-full transition-all" style="width:{{ $pct }}%"></div>
            </div>
          </div>
          @if(($importMovements ?? collect())->isNotEmpty())
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="{{ $muted }} border-t {{ $border }} {{ $surface2 }}">
                  <th class="text-left px-4 py-2 font-semibold">Depot</th>
                  <th class="text-right px-4 py-2 font-semibold">Qty</th>
                  <th class="text-right px-4 py-2 font-semibold">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y {{ $border }}">
                @foreach($importMovements as $mv)
                  <tr>
                    <td class="px-4 py-2 {{ $fg }}">{{ $mv->toDepot?->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-right {{ $fg }} font-semibold">{{ number_format($mv->qty, 0) }}</td>
                    <td class="px-4 py-2 text-right {{ $muted }}">{{ $mv->created_at->format('d M') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          @endif
        </div>
      @endif

      {{-- Vessel / shipping (collapsible, open if data exists) --}}
      @if($purchase->vessel_name || $purchase->loading_port || $purchase->bl_number)
      <details class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden group" open>
        <summary class="px-4 py-3 flex items-center justify-between gap-2 cursor-pointer
                        {{ $surface2 }} border-b {{ $border }} list-none select-none">
          <span class="text-xs font-semibold {{ $fg }}">Vessel / shipping</span>
          <svg class="w-3.5 h-3.5 {{ $muted }} group-open:rotate-180 transition-transform"
               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
          </svg>
        </summary>
        <dl class="divide-y {{ $border }}">
          @foreach([
            'Vessel'         => $purchase->vessel_name,
            'Voyage'         => $purchase->voyage_no,
            'BL number'      => $purchase->bl_number,
            'Loading port'   => $purchase->loading_port,
            'Discharge port' => $purchase->discharge_port,
            'BL date'        => $purchase->bl_date?->format('d M Y'),
            'ETA'            => $purchase->eta_date?->format('d M Y'),
          ] as $label => $value)
            @if($value)
            <div class="px-4 py-2 flex items-center justify-between gap-2">
              <dt class="text-xs {{ $muted }}">{{ $label }}</dt>
              <dd class="text-xs font-semibold {{ $fg }}">{{ $value }}</dd>
            </div>
            @endif
          @endforeach
        </dl>
      </details>
      @endif

    </aside>

    {{-- ── Main: logistics pipeline ──────────────────────────────── --}}
    <main class="lg:col-span-8 xl:col-span-9 min-w-0">
      @include('purchases._import_logistics', [
        'purchase'          => $purchase,
        'importNomination'  => $importNomination,
        'transporters'      => $transporters,
        'depots'            => $depots,
        'qty'               => $qty,
        'currency'          => $currency,
        'volumeUnit'        => $volumeUnit ?? 'L',
        'border'            => $border,
        'surface'           => $surface,
        'surface2'          => $surface2,
        'fg'                => $fg,
        'muted'             => $muted,
      ])
    </main>

  </div>

  {{-- ══════════════════════════════════════════════════════════════════
       ALL OTHER TYPES + import draft / cancelled / voided
       Compact two-panel card
       ══════════════════════════════════════════════════════════════════ --}}
  @else

  <div class="grid lg:grid-cols-12 gap-4 items-start">

    {{-- Purchase details card --}}
    <div class="lg:col-span-5 rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
      <div class="px-4 py-4 border-b {{ $border }}"
           style="background:linear-gradient(135deg,var(--tw-surface-2) 0%,var(--tw-surface) 100%)">
        <div class="text-[10px] uppercase tracking-wider {{ $muted }} font-semibold">{{ $typeLabel }}</div>
        <div class="mt-1 text-lg font-bold {{ $fg }} leading-tight truncate">{{ $supplierName }}</div>
        <div class="mt-0.5 text-xs {{ $muted }}">{{ $productName }}</div>
      </div>

      <dl class="divide-y {{ $border }}">
        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
          <dt class="text-xs {{ $muted }}">Quantity</dt>
          <dd class="text-sm font-semibold {{ $fg }}">{{ number_format($qty, 0) }}<span class="text-xs font-normal {{ $muted }} ml-1">L</span></dd>
        </div>
        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
          <dt class="text-xs {{ $muted }}">Unit price</dt>
          <dd class="text-sm font-semibold {{ $fg }}">{{ number_format($unit, 4) }}<span class="text-xs font-normal {{ $muted }} ml-1">{{ $currency }}/L</span></dd>
        </div>
        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
          <dt class="text-xs {{ $muted }}">Total value</dt>
          <dd class="text-xl font-bold {{ $fg }}">{{ number_format($total, 2) }}<span class="text-xs font-normal {{ $muted }} ml-1">{{ $currency }}</span></dd>
        </div>
        @if($purchase->type === 'local_depot' && $depotName !== '—')
        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
          <dt class="text-xs {{ $muted }}">Depot</dt>
          <dd class="text-sm font-medium {{ $fg }}">{{ $depotName }}</dd>
        </div>
        @endif
        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
          <dt class="text-xs {{ $muted }}">Date</dt>
          <dd class="text-sm {{ $fg }}">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</dd>
        </div>
        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
          <dt class="text-xs {{ $muted }}">Reference</dt>
          <dd class="text-xs font-mono {{ $fg }}">{{ $ref }}</dd>
        </div>
        @if($purchase->batch_id)
        <div class="px-4 py-2.5 flex items-center justify-between gap-2">
          <dt class="text-xs {{ $muted }}">Batch</dt>
          <dd class="text-xs font-mono font-semibold {{ $fg }}">#{{ $purchase->batch_id }}</dd>
        </div>
        @endif
        @if($purchase->notes)
        <div class="px-4 py-2.5">
          <dt class="text-[11px] {{ $muted }} mb-0.5">Notes</dt>
          <dd class="text-xs {{ $fg }}">{{ $purchase->notes }}</dd>
        </div>
        @endif
      </dl>
    </div>

    {{-- Workflow / status panel --}}
    <div class="lg:col-span-7 rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden flex flex-col">

      {{-- Status timeline --}}
      @php
        $allStatuses = match($purchase->type) {
          'local_depot' => ['draft','confirmed','received'],
          'cross_dock'  => ['draft','confirmed','transferred'],
          default       => ['draft','confirmed'],
        };
        $terminalStatuses = ['received','transferred','dispatched'];
        $terminalReached  = in_array($purchase->status, $terminalStatuses);
        $currentIdx       = array_search($purchase->status, $allStatuses);
        if ($currentIdx === false && $terminalReached) $currentIdx = count($allStatuses) - 1;
        $statusLabels = [
          'draft'       => 'Draft',
          'confirmed'   => 'Confirmed',
          'received'    => 'Received',
          'transferred' => 'Transferred',
          'dispatched'  => 'Dispatched',
        ];
      @endphp
      <div class="px-5 pt-5 pb-4 border-b {{ $border }}">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} font-semibold mb-3">Progress</div>
        <div class="flex items-center">
          @foreach($allStatuses as $i => $s)
            @php
              $done    = $currentIdx !== false && $i <= $currentIdx && !in_array($purchase->status, ['cancelled','voided']);
              $current = $i === $currentIdx && !in_array($purchase->status, ['cancelled','voided']);
            @endphp
            <div class="flex items-center {{ $i < count($allStatuses) - 1 ? 'flex-1' : '' }}">
              <div class="flex flex-col items-center gap-1">
                <div class="w-7 h-7 rounded-full flex items-center justify-center border text-[10px] font-bold shrink-0
                            {{ $done ? 'bg-emerald-500 border-emerald-500 text-white' : ($border . ' ' . $surface2 . ' ' . $muted) }}">
                  @if($done && !$current)
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                  @else
                    {{ $i + 1 }}
                  @endif
                </div>
                <div class="text-[9px] font-semibold whitespace-nowrap {{ $done ? 'text-emerald-500' : $muted }}">
                  {{ $statusLabels[$s] ?? ucfirst($s) }}
                </div>
              </div>
              @if($i < count($allStatuses) - 1)
                <div class="flex-1 h-px mx-2 mb-4 {{ $i < ($currentIdx ?? -1) && !in_array($purchase->status, ['cancelled','voided']) ? 'bg-emerald-500' : 'bg-[color:var(--tw-border)]' }}"></div>
              @endif
            </div>
          @endforeach
        </div>
      </div>

      {{-- What's next / guidance --}}
      <div class="px-5 py-4 border-b {{ $border }}">
        <div class="text-xs font-semibold {{ $fg }} mb-1">
          @if(in_array($purchase->status, ['cancelled','voided'])) Status
          @elseif(in_array($purchase->status, ['received','transferred','dispatched'])) Complete
          @else What's next
          @endif
        </div>
        <p class="text-sm {{ $muted }} leading-relaxed">
          @if($purchase->type === 'local_depot')
            @if($purchase->status === 'draft') Confirm to lock the draft and create a batch. Then receive it into <strong class="{{ $fg }}">{{ $depotName }}</strong>.
            @elseif($purchase->status === 'confirmed') Ready to receive into <strong class="{{ $fg }}">{{ $depotName }}</strong>. Click <strong class="{{ $fg }}">Receive</strong> above.
            @elseif($purchase->status === 'received') Stock received into <strong class="{{ $fg }}">{{ $depotName }}</strong>. Purchase is complete.
            @elseif($purchase->status === 'cancelled') Purchase was cancelled. No inventory changes were made.
            @elseif($purchase->status === 'voided') Purchase was voided. Stock has been returned.
            @else {{ ucfirst($purchase->status) }}
            @endif
          @elseif($purchase->type === 'cross_dock')
            @if($purchase->status === 'draft') Confirm to create a batch and receipt stock into Cross Dock immediately.
            @elseif($purchase->status === 'confirmed') Stock is in Cross Dock. <strong class="{{ $fg }}">Transfer</strong> to a depot or <strong class="{{ $fg }}">Dispatch</strong> directly to a client.
            @elseif($purchase->status === 'transferred') Stock transferred from Cross Dock into a depot. Complete.
            @elseif($purchase->status === 'dispatched') Dispatched to {{ $purchase->client?->name ?? 'client' }}. Complete.
            @elseif($purchase->status === 'cancelled') Purchase was cancelled. Cross Dock receipt automatically reversed.
            @else {{ ucfirst($purchase->status) }}
            @endif
          @else {{-- import draft / cancelled / voided --}}
            @if($purchase->status === 'draft') Confirm to create a batch, then set up the truck nomination to begin tracking.
            @elseif($purchase->status === 'cancelled') Purchase was cancelled.
            @elseif($purchase->status === 'voided') Purchase was voided.
            @else Complete.
            @endif
          @endif
        </p>
      </div>

      {{-- Cancelled / Voided note --}}
      @if(in_array($purchase->status, ['cancelled','voided']) && $purchase->actioned_at)
        <div class="mx-5 my-4 rounded-xl border border-rose-500/30 bg-rose-500/8 px-4 py-3 text-xs text-rose-700 dark:text-rose-300">
          {{ ucfirst($purchase->status) }} on {{ $purchase->actioned_at->format('d M Y, H:i') }}
          @if($purchase->action_note) · {{ $purchase->action_note }} @endif
        </div>
      @endif

      <div class="flex-1 min-h-8"></div>

    </div>

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
    <div class="absolute inset-0 bg-black/60" data-close="confirm"></div>

    <div class="tw-modal-wrap">
      <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-xl overflow-hidden">
        <div class="tw-modal-handle"></div>
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
                    aria-label="Close">✕</button>
          </div>
        </div>

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
              <div class="mt-1 {{ $muted }}">Batch is created. Stock is <span class="{{ $fg }} font-semibold">not received</span> yet — it will be received during offload.</div>
            @elseif($purchase->type === 'local_depot')
              <div class="mt-1 {{ $muted }}">Batch is created. Next step is receiving into: <span class="{{ $fg }} font-semibold">{{ $depotName }}</span>.</div>
            @else
              <div class="mt-1 {{ $muted }}">Batch is created and stock is receipted into <span class="{{ $fg }} font-semibold">CROSS DOCK</span> immediately.</div>
            @endif
          </div>
        </div>

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
    <div class="absolute inset-0 bg-black/60" data-close="receive"></div>

    <div class="tw-modal-wrap">
      <div class="tw-modal-inner bg-[color:var(--tw-surface)] border-t border-[color:var(--tw-border)] sm:rounded-2xl sm:border sm:shadow-2xl sm:max-w-xl overflow-hidden">
        <div class="tw-modal-handle"></div>
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
                    aria-label="Close">✕</button>
          </div>
        </div>

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
        </div>

        <div class="p-5 border-t {{ $border }} {{ $surface2 }} flex items-center justify-end gap-2">
          <button type="button" data-close="receive"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }}
                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
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

    function openConfirm() { if (!confirmModal) return; confirmModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeConfirm() { if (!confirmModal) return; confirmModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnConfirm, 'click', openConfirm);
    if (confirmModal) confirmModal.querySelectorAll('[data-close="confirm"]').forEach(el => on(el, 'click', closeConfirm));
    on(confirmConfirm, 'click', () => { closeConfirm(); confirmForm && confirmForm.submit(); });

    // Receive modal
    const btnReceive     = document.getElementById('btnReceive');
    const receiveModal   = document.getElementById('receiveModal');
    const confirmReceive = document.getElementById('confirmReceive');
    const receiveForm    = document.getElementById('receiveForm');

    function openReceive() { if (!receiveModal) return; receiveModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeReceive() { if (!receiveModal) return; receiveModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnReceive, 'click', openReceive);
    if (receiveModal) receiveModal.querySelectorAll('[data-close="receive"]').forEach(el => on(el, 'click', closeReceive));
    on(confirmReceive, 'click', () => { closeReceive(); receiveForm && receiveForm.submit(); });

    // Undo Receipt modal
    const btnUndoReceipt   = document.getElementById('btnUndoReceipt');
    const undoReceiptModal = document.getElementById('undoReceiptModal');

    function openUndoReceipt() { if (!undoReceiptModal) return; undoReceiptModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeUndoReceipt() { if (!undoReceiptModal) return; undoReceiptModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnUndoReceipt, 'click', openUndoReceipt);
    if (undoReceiptModal) undoReceiptModal.querySelectorAll('[data-close="undo-receipt"]').forEach(el => on(el, 'click', closeUndoReceipt));

    // Cross-dock transfer modal
    const btnCrossDockTransfer   = document.getElementById('btnCrossDockTransfer');
    const crossDockTransferModal = document.getElementById('crossDockTransferModal');

    function openCrossDockTransfer() { if (!crossDockTransferModal) return; crossDockTransferModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeCrossDockTransfer() { if (!crossDockTransferModal) return; crossDockTransferModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnCrossDockTransfer, 'click', openCrossDockTransfer);
    if (crossDockTransferModal) crossDockTransferModal.querySelectorAll('[data-close="cross-dock-transfer"]').forEach(el => on(el, 'click', closeCrossDockTransfer));

    // Cross-dock dispatch modal
    const btnCrossDockDispatch   = document.getElementById('btnCrossDockDispatch');
    const crossDockDispatchModal = document.getElementById('crossDockDispatchModal');

    function openCrossDockDispatch() { if (!crossDockDispatchModal) return; crossDockDispatchModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeCrossDockDispatch() { if (!crossDockDispatchModal) return; crossDockDispatchModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnCrossDockDispatch, 'click', openCrossDockDispatch);
    if (crossDockDispatchModal) crossDockDispatchModal.querySelectorAll('[data-close="cross-dock-dispatch"]').forEach(el => on(el, 'click', closeCrossDockDispatch));

    // Nominate vessel modal
    const btnNominate   = document.getElementById('btnNominate');
    const nominateModal = document.getElementById('nominateModal');

    function openNominate()  { if (!nominateModal) return; nominateModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeNominate() { if (!nominateModal) return; nominateModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnNominate, 'click', openNominate);
    if (nominateModal) nominateModal.querySelectorAll('[data-close="nominate"]').forEach(el => on(el, 'click', closeNominate));

    // Import deliver modal
    const btnImportDeliver   = document.getElementById('btnImportDeliver');
    const importDeliverModal = document.getElementById('importDeliverModal');

    function openImportDeliver()  { if (!importDeliverModal) return; importDeliverModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeImportDeliver() { if (!importDeliverModal) return; importDeliverModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnImportDeliver, 'click', openImportDeliver);
    if (importDeliverModal) importDeliverModal.querySelectorAll('[data-close="import-deliver"]').forEach(el => on(el, 'click', closeImportDeliver));

    // Cancel purchase modal
    const btnCancel   = document.getElementById('btnCancel');
    const cancelModal = document.getElementById('cancelModal');

    function openCancel()  { if (!cancelModal) return; cancelModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeCancel() { if (!cancelModal) return; cancelModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnCancel, 'click', openCancel);
    if (cancelModal) cancelModal.querySelectorAll('[data-close="cancel-purchase"]').forEach(el => on(el, 'click', closeCancel));

    // Void / Return to seller modal
    const btnVoid   = document.getElementById('btnVoid');
    const voidModal = document.getElementById('voidModal');

    function openVoid()  { if (!voidModal) return; voidModal.classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); }
    function closeVoid() { if (!voidModal) return; voidModal.classList.add('hidden'); document.documentElement.classList.remove('overflow-hidden'); }

    on(btnVoid, 'click', openVoid);
    if (voidModal) voidModal.querySelectorAll('[data-close="void-purchase"]').forEach(el => on(el, 'click', closeVoid));

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
    if (deleteCostModal) deleteCostModal.querySelectorAll('[data-close="delete-cost"]').forEach(el => on(el, 'click', closeDeleteCostModal));

    // ESC closes any open modal
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      closeConfirm(); closeReceive(); closeUndoReceipt();
      closeCrossDockTransfer(); closeCrossDockDispatch();
      closeNominate(); closeImportDeliver();
      closeCancel(); closeVoid();
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
