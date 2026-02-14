{{-- resources/views/depot-stock/partials/batches-metric.blade.php --}}

@php
  /**
   * Expect:
   *  $metrics, $currentDepot, $stocks,
   *  $border, $surface, $surface2, $fg, $muted, $pillGreen
   */

  // derive filter options from current $stocks (keeps it controller-free for now)
  $productOpts = $stocks
    ->map(fn($r) => [$r->product_id, $r->product?->name ?? ('Product #' . $r->product_id)])
    ->unique(fn($x) => $x[0])
    ->sortBy(fn($x) => strtolower($x[1] ?? ''))
    ->values();

  $batchOpts = $stocks
    ->map(fn($r) => [$r->batch_id, $r->batch?->code ?? ('Batch #' . $r->batch_id)])
    ->unique(fn($x) => $x[0])
    ->values();
@endphp

<div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
  <div class="flex items-start justify-between gap-3">
    <div class="min-w-0">
      <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Batches</div>
      <div class="mt-1 text-xl font-semibold {{ $fg }}">{{ (int)($metrics['batches'] ?? 0) }}</div>
      <div class="mt-1 text-[11px] {{ $muted }}">FIFO layers in this depot</div>
    </div>

    <button type="button" id="btnSeeAllBatches"
            class="shrink-0 inline-flex items-center gap-2 rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-xs font-semibold {{ $fg }}
                   hover:bg-[color:var(--tw-surface)] transition">
      See all
      <svg class="w-4 h-4 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
      </svg>
    </button>
  </div>
</div>

{{-- See all batches modal --}}
<div id="batchesModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
  <div class="absolute inset-0 bg-black/60"></div>

  <div class="relative min-h-full w-full p-4 flex items-center justify-center">
    <div class="w-full {{ $modalSize ?? 'max-w-7xl' }} rounded-2xl border {{ $border }} {{ $surface }} shadow-xl overflow-hidden">
      <div class="{{ $modalHeight ?? 'max-h-[127.5vh]' }} overflow-y-auto overscroll-contain">
        <div class="p-5 border-b {{ $border }} {{ $surface2 }} sticky top-0 z-10">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-base font-semibold {{ $fg }}">Batches in {{ $currentDepot->name }}</div>
              <div class="mt-1 text-xs {{ $muted }}">
                Filter rows, export CSV, and drill into FIFO layers. (Export is client-side CSV for now.)
              </div>
            </div>

            <button type="button" id="closeBatchesModal"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-(--tw-surface-2) transition">✕</button>
          </div>

          {{-- Filters + actions --}}
          <div class="mt-4 grid gap-3 sm:grid-cols-12">
            <div class="sm:col-span-4">
              <label class="text-[11px] font-semibold {{ $muted }}">Search</label>
              <input id="batchSearch" type="text" placeholder="batch code, product..."
                     class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} p-2 text-sm {{ $fg }} outline-none focus:ring-2 focus:ring-emerald-500/30" />
            </div>

            <div class="sm:col-span-3">
              <label class="text-[11px] font-semibold {{ $muted }}">Product</label>
              <select id="batchFilterProduct"
                      class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} p-2 text-sm {{ $fg }} outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All</option>
                @foreach($productOpts as [$pid, $pname])
                  <option value="{{ $pid }}">{{ $pname }}</option>
                @endforeach
              </select>
            </div>

            <div class="sm:col-span-3">
              <label class="text-[11px] font-semibold {{ $muted }}">Batch</label>
              <select id="batchFilterBatch"
                      class="mt-1 w-full rounded-xl border {{ $border }} {{ $surface }} p-2 text-sm {{ $fg }} outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All</option>
                @foreach($batchOpts as [$bid, $bcode])
                  <option value="{{ $bid }}">{{ $bcode }}</option>
                @endforeach
              </select>
            </div>

            <div class="sm:col-span-2 flex items-end gap-2">
              <button type="button" id="btnExportBatches"
                      class="inline-flex w-full items-center justify-center gap-2 h-10 px-4 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600 hover:border-emerald-700 transition">
                Export
              </button>
            </div>
          </div>
        </div>

        {{-- Table --}}
        <div class="p-4">
          @if($stocks->isEmpty())
            <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-4 text-sm {{ $muted }}">
              No batch rows yet for this depot.
            </div>
          @else
            <div class="overflow-x-auto rounded-2xl border {{ $border }}">
              <table class="w-full text-sm" id="batchesTable">
                <thead class="{{ $surface2 }} border-b {{ $border }}">
                  <tr class="text-left">
                    <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Batch</th>
                    <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Purchased</th>
                    <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Product</th>
                    <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">On hand</th>
                    <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Reserved</th>
                    <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Unit cost</th>
                    <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Value</th>
                  </tr>
                </thead>
                <tbody class="divide-y {{ $border }}">
                  @foreach($stocks as $row)
                    @php
                      $batchId   = (int)($row->batch_id ?? 0);
                      $productId = (int)($row->product_id ?? 0);
                      $batchCode = $row->batch?->code ?? ('Batch #' . ($row->batch_id ?? '—'));
                      $purchased = $row->batch?->purchased_at?->format('Y-m-d') ?? '—';
                      $product   = $row->product?->name ?? ('Product #' . ($row->product_id ?? '—'));
                      $onHand    = (float)($row->qty_on_hand ?? 0);
                      $reserved  = (float)($row->qty_reserved ?? 0);
                      $unitCost  = (float)($row->unit_cost ?? 0);
                      $value     = $onHand * $unitCost;
                    @endphp
                    <tr class="hover:bg-(--tw-surface-2)/60" data-batch="{{ $batchId }}" data-product="{{ $productId }}" data-search="{{ strtolower($batchCode.' '.$product.' '.$purchased) }}">
                      <td class="px-4 py-3">
                        <div class="font-semibold {{ $fg }}">{{ $batchCode }}</div>
                        <div class="text-[11px] {{ $muted }}">#{{ $batchId }}</div>
                      </td>
                      <td class="px-4 py-3 {{ $muted }}">{{ $purchased }}</td>
                      <td class="px-4 py-3">
                        <div class="font-semibold {{ $fg }}">{{ $product }}</div>
                        <div class="text-[11px] {{ $muted }}">#{{ $productId }}</div>
                      </td>
                      <td class="px-4 py-3 font-semibold {{ $fg }}">{{ number_format($onHand, 3) }} <span class="text-xs {{ $muted }}">L</span></td>
                      <td class="px-4 py-3 {{ $fg }}">{{ number_format($reserved, 3) }} <span class="text-xs {{ $muted }}">L</span></td>
                      <td class="px-4 py-3 {{ $fg }}">{{ number_format($unitCost, 6) }}</td>
                      <td class="px-4 py-3 font-semibold {{ $fg }}">{{ number_format($value, 2) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="mt-3 text-[11px] {{ $muted }}">
              Tip: use Search + Product + Batch filters together.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  const modal = document.getElementById('batchesModal');
  const openBtn = document.getElementById('btnSeeAllBatches');
  const closeBtn = document.getElementById('closeBatchesModal');
  const overlay = modal?.firstElementChild;

  const searchEl = document.getElementById('batchSearch');
  const prodEl = document.getElementById('batchFilterProduct');
  const batchEl = document.getElementById('batchFilterBatch');
  const exportBtn = document.getElementById('btnExportBatches');

  const table = document.getElementById('batchesTable');
  const tbody = table?.querySelector('tbody');

  const lockBody = (locked) => {
    document.documentElement.classList.toggle('overflow-hidden', !!locked);
    document.body.classList.toggle('overflow-hidden', !!locked);
  };

  const open = () => { if (!modal) return; modal.classList.remove('hidden'); lockBody(true); };
  const close = () => { if (!modal) return; modal.classList.add('hidden'); lockBody(false); };

  on(openBtn, 'click', open);
  on(closeBtn, 'click', close);
  on(overlay, 'click', close);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) close();
  });

  const applyFilters = () => {
    if (!tbody) return;

    const q = (searchEl?.value || '').trim().toLowerCase();
    const prod = (prodEl?.value || '').trim();
    const bat = (batchEl?.value || '').trim();

    Array.from(tbody.children).forEach(tr => {
      const s = (tr.getAttribute('data-search') || '');
      const trProd = (tr.getAttribute('data-product') || '');
      const trBat = (tr.getAttribute('data-batch') || '');

      const okQ = !q || s.includes(q);
      const okP = !prod || trProd === prod;
      const okB = !bat || trBat === bat;

      tr.classList.toggle('hidden', !(okQ && okP && okB));
    });
  };

  on(searchEl, 'input', applyFilters);
  on(prodEl, 'change', applyFilters);
  on(batchEl, 'change', applyFilters);

  const rowsForExport = () => {
    if (!tbody) return [];
    return Array.from(tbody.querySelectorAll('tr'))
      .filter(tr => !tr.classList.contains('hidden'));
  };

  const csvEscape = (v) => {
    const s = String(v ?? '');
    if (/[",\n]/.test(s)) return '"' + s.replaceAll('"', '""') + '"';
    return s;
  };

  const exportCsv = () => {
    if (!table) return;

    const rows = rowsForExport();
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());

    const out = [];
    out.push(headers.map(csvEscape).join(','));

    rows.forEach(tr => {
      const cells = Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim());
      out.push(cells.map(csvEscape).join(','));
    });

    const blob = new Blob([out.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    const a = document.createElement('a');
    a.href = url;
    a.download = `batches_depot_{{ (int)$currentDepot->id }}_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  };

  on(exportBtn, 'click', exportCsv);

  // Ensure initial state is clean
  applyFilters();
})();
</script>