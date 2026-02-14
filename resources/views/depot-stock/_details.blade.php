{{-- resources/views/depot-stock/_details.blade.php --}}

@php
  $fmtL = fn ($v) => number_format((float)$v, 0);
  $fmtM = fn ($v) => number_format((float)$v, 2);

  // Buttons (stand out in BOTH light + dark)
  $btnGreen = "border-emerald-600 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-700 transition";
  $btnGhost = "inline-flex items-center gap-2 rounded-xl border $border $surface2 px-4 py-2 text-sm font-semibold $fg hover:bg-[color:var(--tw-surface)] transition";
  $btnLink  = "text-sm $muted hover:text-[color:var(--tw-fg)] transition";
@endphp

@if(!$currentDepot)
  <div class="rounded-2xl border border-dashed {{ $border }} {{ $surface }} p-8 text-center">
    <div class="text-sm font-semibold {{ $fg }}">No depot selected</div>
    <div class="mt-1 text-xs {{ $muted }}">Pick a depot from the left to view stock.</div>
  </div>
@else

  {{-- Header --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
      <div class="min-w-0">
        <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Working depot</div>
        <div class="mt-1 flex items-center gap-2 min-w-0">
          <div class="text-lg font-semibold truncate {{ $fg }}">{{ $currentDepot->name }}</div>
          <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $pillGreen }}">
            Active
          </span>
        </div>
        <div class="mt-1 text-xs {{ $muted }}">{{ $currentDepot->city ?: 'City not set' }}</div>
      </div>

      {{-- Actions --}}
      <div class="flex flex-wrap items-center gap-2">
        <a href="#"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border {{ $border }} {{ $surface2 }} {{ $fg }}
                  hover:bg-[color:var(--tw-surface)] transition">
          Receive
        </a>

        <a href="{{ route('sales.index', ['open_sale' => 1, 'from_depot' => $currentDepot->id]) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border {{ $btnGreen }}">
          New sale
        </a>

        <button type="button" id="btnDepotAdjust"
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border {{ $border }} {{ $surface2 }} {{ $fg }}
                       hover:bg-[color:var(--tw-surface)] transition">
          Adjustment
        </button>
      </div>
    </div>
  </div>

  {{-- Metrics --}}
  <div class="grid sm:grid-cols-4 gap-3 mb-4">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">On hand</div>
      <div class="mt-1 text-xl font-semibold {{ $fg }}">
        {{ $fmtL($metrics['on_hand_l'] ?? 0) }} <span class="text-xs {{ $muted }}">L</span>
      </div>
      <div class="mt-1 text-[11px] {{ $muted }}">Physical available in depot</div>
    </div>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Reserved</div>
      <div class="mt-1 text-xl font-semibold {{ $fg }}">
        {{ $fmtL($metrics['reserved_l'] ?? 0) }} <span class="text-xs {{ $muted }}">L</span>
      </div>
      <div class="mt-1 text-[11px] {{ $muted }}">Allocated to open sales</div>
    </div>

    {{-- Batches metric (clean, no button) --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Batches</div>
      <div class="mt-1 text-xl font-semibold {{ $fg }}">
        {{ (int)($metrics['batches'] ?? 0) }}
      </div>
      <div class="mt-1 text-[11px] {{ $muted }}">FIFO layers in this depot</div>
    </div>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Stock value</div>
      <div class="mt-1 text-xl font-semibold {{ $fg }}">
        {{ $fmtM($metrics['value'] ?? 0) }}
      </div>
      <div class="mt-1 text-[11px] {{ $muted }}">Qty × unit cost snapshot</div>
    </div>
  </div>

  {{-- Stock table --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="p-4 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between gap-3">
      <div>
        <div class="text-sm font-semibold {{ $fg }}">Depot stock</div>
        <div class="mt-0.5 text-xs {{ $muted }}">Batch-aware rows (FIFO-ready)</div>
      </div>

      <div class="flex items-center gap-3">
        <span class="inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold {{ $border }} {{ $surface }} {{ $muted }}">
          {{ $stocks->count() }} rows
        </span>

        <button type="button"
                id="btnViewAllBatches"
                class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-lg border {{ $border }} {{ $muted }} hover:text-[color:var(--tw-fg)] hover:bg-[color:var(--tw-surface)] transition"
                onclick="document.getElementById('batchesModal').classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); document.body.classList.add('overflow-hidden');">
          See all
        </button>
      </div>
    </div>

    @if($stocks->isEmpty())
      <div class="p-6 text-sm {{ $muted }}">
        No stock recorded yet for this depot.
        <span class="{{ $fg }} font-semibold">Receive</span> or
        <span class="{{ $fg }} font-semibold">confirm cross dock</span> to populate.
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="{{ $surface2 }} border-b {{ $border }}">
            <tr class="text-left">
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Batch</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Product</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">On hand</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Reserved</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Unit cost</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Value</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y {{ $border }}">
            @foreach($stocks as $row)
              @php
                $batchCode = $row->batch?->code ?? ('Batch #' . ($row->batch_id ?? '—'));
                $product   = $row->product?->name ?? ('Product #' . ($row->product_id ?? '—'));
                $value     = ((float)$row->qty_on_hand) * ((float)$row->unit_cost);

                $saleHref = route('sales.index', [
                  'open_sale'   => 1,
                  'from_depot'  => (int) $currentDepot->id,
                  'from_product'=> (int) $row->product_id,
                ]);
              @endphp
              <tr class="hover:bg-(--tw-surface-2)/60">
                <td class="px-4 py-3">
                  <div class="font-semibold {{ $fg }}">{{ $batchCode }}</div>
                  <div class="text-[11px] {{ $muted }}">
                    {{ $row->batch?->purchased_at?->format('Y-m-d') ?? '—' }}
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-semibold {{ $fg }}">{{ $product }}</div>
                </td>
                <td class="px-4 py-3 font-semibold {{ $fg }}">
                  {{ number_format((float)$row->qty_on_hand, 3) }} <span class="text-xs {{ $muted }}">L</span>
                </td>
                <td class="px-4 py-3 {{ $fg }}">
                  {{ number_format((float)$row->qty_reserved, 3) }} <span class="text-xs {{ $muted }}">L</span>
                </td>
                <td class="px-4 py-3 {{ $fg }}">
                  {{ number_format((float)$row->unit_cost, 6) }}
                </td>
                <td class="px-4 py-3 font-semibold {{ $fg }}">
                  {{ number_format((float)$value, 2) }}
                </td>
                <td class="px-4 py-3">
                  <a href="{{ $saleHref }}" class="{{ $btnLink }} font-semibold">
                    New sale →
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Adjustment modal --}}
  @include('depot-stock.partials.adjustment-modal', [
    'currentDepot' => $currentDepot,
    'border' => $border,
    'surface' => $surface,
    'surface2' => $surface2,
    'fg' => $fg,
    'muted' => $muted,
  ])

  {{-- Recent movements --}}
  @include('depot-stock.partials.recent-movements', [
    'recentMovements' => $recentMovements,
    'border' => $border,
    'surface' => $surface,
    'surface2' => $surface2,
    'fg' => $fg,
    'muted' => $muted,
    'pillGreen' => $pillGreen,
  ])

  {{-- Batches metric modal --}}
  @include('depot-stock.partials.batches-metric-modal', [
    'metrics' => $metrics,
    'currentDepot' => $currentDepot,
    'stocks' => $stocks,
    'border' => $border,
    'surface' => $surface,
    'surface2' => $surface2,
    'fg' => $fg,
    'muted' => $muted,
    'pillGreen' => $pillGreen,
    'modalSize' => 'max-w-6xl', // Make modal 3x bigger
  ])

@endif