

<?php
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
?>

<div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
  <div class="flex items-start justify-between gap-3">
    <div class="min-w-0">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Batches</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>"><?php echo e((int)($metrics['batches'] ?? 0)); ?></div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">FIFO layers in this depot</div>
    </div>

    <button type="button" id="btnSeeAllBatches"
            class="shrink-0 inline-flex items-center gap-2 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 py-2 text-xs font-semibold <?php echo e($fg); ?>

                   hover:bg-[color:var(--tw-surface)] transition">
      See all
      <svg class="w-4 h-4 <?php echo e($muted); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
      </svg>
    </button>
  </div>
</div>


<div id="batchesModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
  <div class="absolute inset-0 bg-black/60"></div>

  <div class="relative min-h-full w-full p-4 flex items-center justify-center">
    <div class="w-full <?php echo e($modalSize ?? 'max-w-7xl'); ?> rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-xl overflow-hidden">
      <div class="<?php echo e($modalHeight ?? 'max-h-[127.5vh]'); ?> overflow-y-auto overscroll-contain">
        <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?> sticky top-0 z-10">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-base font-semibold <?php echo e($fg); ?>">Batches in <?php echo e($currentDepot->name); ?></div>
              <div class="mt-1 text-xs <?php echo e($muted); ?>">
                Filter rows, export CSV, and drill into FIFO layers. (Export is client-side CSV for now.)
              </div>
            </div>

            <button type="button" id="closeBatchesModal"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> <?php echo e($fg); ?> hover:bg-(--tw-surface-2) transition">✕</button>
          </div>

          
          <div class="mt-4 grid gap-3 sm:grid-cols-12">
            <div class="sm:col-span-4">
              <label class="text-[11px] font-semibold <?php echo e($muted); ?>">Search</label>
              <input id="batchSearch" type="text" placeholder="batch code, product..."
                     class="mt-1 w-full rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-2 text-sm <?php echo e($fg); ?> outline-none focus:ring-2 focus:ring-emerald-500/30" />
            </div>

            <div class="sm:col-span-3">
              <label class="text-[11px] font-semibold <?php echo e($muted); ?>">Product</label>
              <select id="batchFilterProduct"
                      class="mt-1 w-full rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-2 text-sm <?php echo e($fg); ?> outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All</option>
                <?php $__currentLoopData = $productOpts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$pid, $pname]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($pid); ?>"><?php echo e($pname); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </div>

            <div class="sm:col-span-3">
              <label class="text-[11px] font-semibold <?php echo e($muted); ?>">Batch</label>
              <select id="batchFilterBatch"
                      class="mt-1 w-full rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-2 text-sm <?php echo e($fg); ?> outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All</option>
                <?php $__currentLoopData = $batchOpts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$bid, $bcode]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($bid); ?>"><?php echo e($bcode); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

        
        <div class="p-4">
          <?php if($stocks->isEmpty()): ?>
            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-4 text-sm <?php echo e($muted); ?>">
              No batch rows yet for this depot.
            </div>
          <?php else: ?>
            <div class="overflow-x-auto rounded-2xl border <?php echo e($border); ?>">
              <table class="w-full text-sm" id="batchesTable">
                <thead class="<?php echo e($surface2); ?> border-b <?php echo e($border); ?>">
                  <tr class="text-left">
                    <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Batch</th>
                    <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Purchased</th>
                    <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Product</th>
                    <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">On hand</th>
                    <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Reserved</th>
                    <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Unit cost</th>
                    <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Value</th>
                  </tr>
                </thead>
                <tbody class="divide-y <?php echo e($border); ?>">
                  <?php $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                      $batchId   = (int)($row->batch_id ?? 0);
                      $productId = (int)($row->product_id ?? 0);
                      $batchCode = $row->batch?->code ?? ('Batch #' . ($row->batch_id ?? '—'));
                      $purchased = $row->batch?->purchased_at?->format('Y-m-d') ?? '—';
                      $product   = $row->product?->name ?? ('Product #' . ($row->product_id ?? '—'));
                      $onHand    = (float)($row->qty_on_hand ?? 0);
                      $reserved  = (float)($row->qty_reserved ?? 0);
                      $unitCost  = (float)($row->unit_cost ?? 0);
                      $value     = $onHand * $unitCost;
                    ?>
                    <tr class="hover:bg-(--tw-surface-2)/60" data-batch="<?php echo e($batchId); ?>" data-product="<?php echo e($productId); ?>" data-search="<?php echo e(strtolower($batchCode.' '.$product.' '.$purchased)); ?>">
                      <td class="px-4 py-3">
                        <div class="font-semibold <?php echo e($fg); ?>"><?php echo e($batchCode); ?></div>
                        <div class="text-[11px] <?php echo e($muted); ?>">#<?php echo e($batchId); ?></div>
                      </td>
                      <td class="px-4 py-3 <?php echo e($muted); ?>"><?php echo e($purchased); ?></td>
                      <td class="px-4 py-3">
                        <div class="font-semibold <?php echo e($fg); ?>"><?php echo e($product); ?></div>
                        <div class="text-[11px] <?php echo e($muted); ?>">#<?php echo e($productId); ?></div>
                      </td>
                      <td class="px-4 py-3 font-semibold <?php echo e($fg); ?>"><?php echo e(number_format($onHand, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span></td>
                      <td class="px-4 py-3 <?php echo e($fg); ?>"><?php echo e(number_format($reserved, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span></td>
                      <td class="px-4 py-3 <?php echo e($fg); ?>"><?php echo e(number_format($unitCost, 6)); ?></td>
                      <td class="px-4 py-3 font-semibold <?php echo e($fg); ?>"><?php echo e(number_format($value, 2)); ?></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
              </table>
            </div>
            <div class="mt-3 text-[11px] <?php echo e($muted); ?>">
              Tip: use Search + Product + Batch filters together.
            </div>
          <?php endif; ?>
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
    a.download = `batches_depot_<?php echo e((int)$currentDepot->id); ?>_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  };

  on(exportBtn, 'click', exportCsv);

  // Ensure initial state is clean
  applyFilters();
})();
</script><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/depot-stock/partials/batches-metric.blade.php ENDPATH**/ ?>