{{-- resources/views/depot-stock/_details.blade.php --}}
@php
  $fmtL = fn ($v) => number_format((float)$v, 0, '.', ',');
  $fmtM = fn ($v) => number_format((float)$v, 2, '.', ',');

  $btnGreen = "border-emerald-600 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-700 transition";

  // Movement direction helpers
  $directionLabel = function($m) {
    if ($m->type === 'adjustment') return 'ADJ';
    if ($m->to_depot_id)   return 'IN';
    if ($m->from_depot_id) return 'OUT';
    return '—';
  };
  $directionStyle = function($m) {
    if ($m->type === 'adjustment') return 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400';
    if ($m->to_depot_id)   return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    if ($m->from_depot_id) return 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-400';
    return '';
  };
  $typeLabel = fn($t) => match($t) {
    'receipt'    => 'Receipt',
    'issue'      => 'Issue',
    'adjustment' => 'Adjustment',
    'transfer'   => 'Transfer',
    default      => ucfirst($t),
  };
@endphp

@if(!$currentDepot)
  <div class="rounded-2xl border border-dashed {{ $border }} {{ $surface }} p-8 text-center">
    <div class="text-sm font-semibold {{ $fg }}">No depot selected</div>
    <div class="mt-1 text-xs {{ $muted }}">Pick a depot from the left to view movements.</div>
  </div>
@else

  {{-- Header --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="min-w-0">
        <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Depot</div>
        <div class="mt-1 flex items-center gap-2 min-w-0">
          <div class="text-lg font-semibold truncate {{ $fg }}">{{ $currentDepot->name }}</div>
          <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $pillGreen }}">
            Active
          </span>
        </div>
        <div class="mt-1 text-xs {{ $muted }}">{{ $currentDepot->city ?: 'City not set' }}</div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('depot-stock.export', array_merge(['depot' => $currentDepot->id], request()->only('type','product','from','to'))) }}"
           class="inline-flex items-center gap-2 h-9 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $muted }} hover:{{ $fg }} transition">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
          </svg>
          Export CSV
        </a>
        <a href="{{ route('sales.index', ['open_sale' => 1, 'from_depot' => $currentDepot->id]) }}"
           class="inline-flex items-center gap-2 h-9 px-4 rounded-xl border {{ $btnGreen }} text-sm font-semibold">
          New sale
        </a>
      </div>
    </div>
  </div>

  {{-- Stats --}}
  <div class="grid sm:grid-cols-3 gap-3 mb-4">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Total received</div>
      <div class="mt-1 text-xl font-semibold text-emerald-500">
        {{ $fmtL($stats['total_in'] ?? 0) }} <span class="text-xs {{ $muted }}">L</span>
      </div>
      <div class="mt-1 text-[11px] {{ $muted }}">All receipts into this depot</div>
    </div>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Total issued</div>
      <div class="mt-1 text-xl font-semibold text-rose-500">
        {{ $fmtL($stats['total_out'] ?? 0) }} <span class="text-xs {{ $muted }}">L</span>
      </div>
      <div class="mt-1 text-[11px] {{ $muted }}">All issues from this depot</div>
    </div>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Current balance</div>
      <div class="mt-1 text-xl font-semibold {{ $fg }}">
        {{ $fmtL($stats['net'] ?? 0) }} <span class="text-xs {{ $muted }}">L</span>
      </div>
      <div class="mt-1 text-[11px] {{ $muted }}">In − Out (all time)</div>
    </div>
  </div>

  {{-- Product balance (compact, no batches) --}}
  @if($balance->isNotEmpty())
  <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden mb-4">
    <div class="px-4 py-3 border-b {{ $border }} {{ $surface2 }}">
      <div class="text-sm font-semibold {{ $fg }}">Stock on hand</div>
      <div class="mt-0.5 text-xs {{ $muted }}">Current depot stock by product</div>
    </div>
    <div class="divide-y {{ $border }}">
      @foreach($balance as $row)
        @php
          $onHand    = (float)($row->total_on_hand ?? 0);
          $reserved  = (float)($row->total_reserved ?? 0);
          $available = max(0, $onHand - $reserved);
        @endphp
        <div class="px-4 py-3 flex items-center justify-between gap-4">
          <div class="font-semibold text-sm {{ $fg }}">{{ $row->product?->name ?? ('Product #' . $row->product_id) }}</div>
          <div class="flex items-center gap-4 text-sm">
            <div class="text-right">
              <div class="font-semibold {{ $fg }}">{{ $fmtL($onHand) }} <span class="text-xs {{ $muted }}">L</span></div>
              <div class="text-[10px] {{ $muted }}">on hand</div>
            </div>
            @if($reserved > 0)
            <div class="text-right">
              <div class="font-semibold text-amber-500">{{ $fmtL($reserved) }} <span class="text-xs {{ $muted }}">L</span></div>
              <div class="text-[10px] {{ $muted }}">reserved</div>
            </div>
            @endif
            <div class="text-right">
              <div class="font-semibold text-emerald-500">{{ $fmtL($available) }} <span class="text-xs {{ $muted }}">L</span></div>
              <div class="text-[10px] {{ $muted }}">available</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Filter bar --}}
  <form method="GET" action="{{ route('depot-stock.index', ['depot' => $currentDepot->id]) }}"
        class="rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-4">
    <div class="flex flex-wrap items-end gap-2">

      {{-- Type filter --}}
      <div class="flex-1 min-w-[130px]">
        <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Type</label>
        <select name="type"
                class="w-full h-9 rounded-xl border {{ $border }} {{ $surface2 }} text-sm px-3 {{ $fg }}">
          <option value="">All types</option>
          <option value="receipt"    {{ request('type') === 'receipt'    ? 'selected' : '' }}>Receipt (IN)</option>
          <option value="issue"      {{ request('type') === 'issue'      ? 'selected' : '' }}>Issue (OUT)</option>
          <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
          <option value="transfer"   {{ request('type') === 'transfer'   ? 'selected' : '' }}>Transfer</option>
        </select>
      </div>

      {{-- Product filter --}}
      <div class="flex-1 min-w-[140px]">
        <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Product</label>
        <select name="product"
                class="w-full h-9 rounded-xl border {{ $border }} {{ $surface2 }} text-sm px-3 {{ $fg }}">
          <option value="">All products</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}" {{ (string)request('product') === (string)$p->id ? 'selected' : '' }}>
              {{ $p->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Date from --}}
      <div class="flex-1 min-w-[130px]">
        <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">From</label>
        <input type="date" name="from" value="{{ request('from') }}"
               class="w-full h-9 rounded-xl border {{ $border }} {{ $surface2 }} text-sm px-3 {{ $fg }}">
      </div>

      {{-- Date to --}}
      <div class="flex-1 min-w-[130px]">
        <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">To</label>
        <input type="date" name="to" value="{{ request('to') }}"
               class="w-full h-9 rounded-xl border {{ $border }} {{ $surface2 }} text-sm px-3 {{ $fg }}">
      </div>

      {{-- Search --}}
      <div class="flex-1 min-w-[160px]">
        <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference or notes…"
               class="w-full h-9 rounded-xl border {{ $border }} {{ $surface2 }} text-sm px-3 {{ $fg }}">
      </div>

      {{-- Buttons --}}
      <div class="flex items-center gap-2 self-end">
        <button type="submit"
                class="h-9 px-4 rounded-xl text-sm font-semibold border {{ $btnGreen }}">
          Filter
        </button>
        @if(request()->hasAny(['type','product','from','to','search']))
          <a href="{{ route('depot-stock.index', ['depot' => $currentDepot->id]) }}"
             class="h-9 px-4 rounded-xl text-sm font-semibold border {{ $border }} {{ $surface2 }} {{ $muted }} hover:{{ $fg }} transition inline-flex items-center">
            Clear
          </a>
        @endif
      </div>
    </div>
  </form>

  {{-- Movements table --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="px-4 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between gap-3">
      <div>
        <div class="text-sm font-semibold {{ $fg }}">Movements</div>
        <div class="mt-0.5 text-xs {{ $muted }}">Receipts in, issues out and adjustments</div>
      </div>
      <span class="inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold {{ $border }} {{ $surface }} {{ $muted }}">
        {{ number_format($stats['count'] ?? 0) }} records
      </span>
    </div>

    @if($movements->isEmpty())
      <div class="p-8 text-center">
        <div class="text-sm {{ $muted }}">No movements found for the current filter.</div>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="{{ $surface2 }} border-b {{ $border }}">
            <tr class="text-left">
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Date</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Dir</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Type</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Product</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }} text-right">Qty (L)</th>
              <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Reference / Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y {{ $border }}">
            @foreach($movements as $m)
              @php
                $dir      = $directionLabel($m);
                $dirStyle = $directionStyle($m);
                $qty      = (float) $m->qty;
              @endphp
              <tr class="hover:bg-[color:var(--tw-surface-2)]/60">
                <td class="px-4 py-3 {{ $muted }} whitespace-nowrap">
                  {{ $m->created_at?->format('Y-m-d') }}<br>
                  <span class="text-[10px]">{{ $m->created_at?->format('H:i') }}</span>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold {{ $dirStyle }}">
                    {{ $dir }}
                  </span>
                </td>
                <td class="px-4 py-3 {{ $muted }} text-xs">{{ $typeLabel($m->type) }}</td>
                <td class="px-4 py-3 font-semibold {{ $fg }}">
                  {{ $m->product?->name ?? ('Product #' . $m->product_id) }}
                </td>
                <td class="px-4 py-3 text-right font-semibold {{ $fg }}">
                  {{ number_format($qty, 3) }}
                </td>
                <td class="px-4 py-3 {{ $muted }} text-xs max-w-xs">
                  @if($m->reference)
                    <span class="font-semibold {{ $fg }}">{{ $m->reference }}</span>
                    @if($m->notes) · @endif
                  @endif
                  {{ $m->notes }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if($movements->hasPages())
        <div class="px-4 py-3 border-t {{ $border }} {{ $surface2 }}">
          {{ $movements->links() }}
        </div>
      @endif
    @endif
  </div>

  {{-- Adjustment modal --}}
  @include('depot-stock.partials.adjustment-modal', [
    'currentDepot' => $currentDepot,
    'border'   => $border,
    'surface'  => $surface,
    'surface2' => $surface2,
    'fg'       => $fg,
    'muted'    => $muted,
  ])

@endif
