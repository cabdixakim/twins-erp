

<?php
  $fmtL = fn ($v) => number_format((float)$v, 0);
  $fmtM = fn ($v) => number_format((float)$v, 2);

  // Buttons (stand out in BOTH light + dark)
  $btnGreen = "border-emerald-600 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-700 transition";
  $btnGhost = "inline-flex items-center gap-2 rounded-xl border $border $surface2 px-4 py-2 text-sm font-semibold $fg hover:bg-[color:var(--tw-surface)] transition";
  $btnLink  = "text-sm $muted hover:text-[color:var(--tw-fg)] transition";
?>

<?php if(!$currentDepot): ?>
  <div class="rounded-2xl border border-dashed <?php echo e($border); ?> <?php echo e($surface); ?> p-8 text-center">
    <div class="text-sm font-semibold <?php echo e($fg); ?>">No depot selected</div>
    <div class="mt-1 text-xs <?php echo e($muted); ?>">Pick a depot from the left to view stock.</div>
  </div>
<?php else: ?>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
      <div class="min-w-0">
        <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Working depot</div>
        <div class="mt-1 flex items-center gap-2 min-w-0">
          <div class="text-lg font-semibold truncate <?php echo e($fg); ?>"><?php echo e($currentDepot->name); ?></div>
          <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold <?php echo e($pillGreen); ?>">
            Active
          </span>
        </div>
        <div class="mt-1 text-xs <?php echo e($muted); ?>"><?php echo e($currentDepot->city ?: 'City not set'); ?></div>
      </div>

      
      <div class="flex flex-wrap items-center gap-2">
        <a href="#"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> <?php echo e($fg); ?>

                  hover:bg-[color:var(--tw-surface)] transition">
          Receive
        </a>

        <a href="<?php echo e(route('sales.index', ['open_sale' => 1, 'from_depot' => $currentDepot->id])); ?>"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($btnGreen); ?>">
          New sale
        </a>

        <button type="button" id="btnDepotAdjust"
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> <?php echo e($fg); ?>

                       hover:bg-[color:var(--tw-surface)] transition">
          Adjustment
        </button>
      </div>
    </div>
  </div>

  
  <div class="grid sm:grid-cols-4 gap-3 mb-4">
    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">On hand</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>">
        <?php echo e($fmtL($metrics['on_hand_l'] ?? 0)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
      </div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Physical available in depot</div>
    </div>

    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Reserved</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>">
        <?php echo e($fmtL($metrics['reserved_l'] ?? 0)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
      </div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Allocated to open sales</div>
    </div>

    
    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Batches</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>">
        <?php echo e((int)($metrics['batches'] ?? 0)); ?>

      </div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">FIFO layers in this depot</div>
    </div>

    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Stock value</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>">
        <?php echo e($fmtM($metrics['value'] ?? 0)); ?>

      </div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Qty × unit cost snapshot</div>
    </div>
  </div>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> overflow-hidden">
    <div class="p-4 border-b <?php echo e($border); ?> <?php echo e($surface2); ?> flex items-center justify-between gap-3">
      <div>
        <div class="text-sm font-semibold <?php echo e($fg); ?>">Depot stock</div>
        <div class="mt-0.5 text-xs <?php echo e($muted); ?>">Batch-aware rows (FIFO-ready)</div>
      </div>

      <div class="flex items-center gap-3">
        <span class="inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold <?php echo e($border); ?> <?php echo e($surface); ?> <?php echo e($muted); ?>">
          <?php echo e($stocks->count()); ?> rows
        </span>

        <button type="button"
                id="btnViewAllBatches"
                class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-lg border <?php echo e($border); ?> <?php echo e($muted); ?> hover:text-[color:var(--tw-fg)] hover:bg-[color:var(--tw-surface)] transition"
                onclick="document.getElementById('batchesModal').classList.remove('hidden'); document.documentElement.classList.add('overflow-hidden'); document.body.classList.add('overflow-hidden');">
          See all
        </button>
      </div>
    </div>

    <?php if($stocks->isEmpty()): ?>
      <div class="p-6 text-sm <?php echo e($muted); ?>">
        No stock recorded yet for this depot.
        <span class="<?php echo e($fg); ?> font-semibold">Receive</span> or
        <span class="<?php echo e($fg); ?> font-semibold">confirm cross dock</span> to populate.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="<?php echo e($surface2); ?> border-b <?php echo e($border); ?>">
            <tr class="text-left">
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Batch</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Product</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">On hand</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Reserved</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Unit cost</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Value</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y <?php echo e($border); ?>">
            <?php $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $batchCode = $row->batch?->code ?? ('Batch #' . ($row->batch_id ?? '—'));
                $product   = $row->product?->name ?? ('Product #' . ($row->product_id ?? '—'));
                $value     = ((float)$row->qty_on_hand) * ((float)$row->unit_cost);

                $saleHref = route('sales.index', [
                  'open_sale'   => 1,
                  'from_depot'  => (int) $currentDepot->id,
                  'from_product'=> (int) $row->product_id,
                ]);
              ?>
              <tr class="hover:bg-(--tw-surface-2)/60">
                <td class="px-4 py-3">
                  <div class="font-semibold <?php echo e($fg); ?>"><?php echo e($batchCode); ?></div>
                  <div class="text-[11px] <?php echo e($muted); ?>">
                    <?php echo e($row->batch?->purchased_at?->format('Y-m-d') ?? '—'); ?>

                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="font-semibold <?php echo e($fg); ?>"><?php echo e($product); ?></div>
                </td>
                <td class="px-4 py-3 font-semibold <?php echo e($fg); ?>">
                  <?php echo e(number_format((float)$row->qty_on_hand, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
                </td>
                <td class="px-4 py-3 <?php echo e($fg); ?>">
                  <?php echo e(number_format((float)$row->qty_reserved, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
                </td>
                <td class="px-4 py-3 <?php echo e($fg); ?>">
                  <?php echo e(number_format((float)$row->unit_cost, 6)); ?>

                </td>
                <td class="px-4 py-3 font-semibold <?php echo e($fg); ?>">
                  <?php echo e(number_format((float)$value, 2)); ?>

                </td>
                <td class="px-4 py-3">
                  <a href="<?php echo e($saleHref); ?>" class="<?php echo e($btnLink); ?> font-semibold">
                    New sale →
                  </a>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  
  <?php echo $__env->make('depot-stock.partials.adjustment-modal', [
    'currentDepot' => $currentDepot,
    'border' => $border,
    'surface' => $surface,
    'surface2' => $surface2,
    'fg' => $fg,
    'muted' => $muted,
  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('depot-stock.partials.recent-movements', [
    'recentMovements' => $recentMovements,
    'border' => $border,
    'surface' => $surface,
    'surface2' => $surface2,
    'fg' => $fg,
    'muted' => $muted,
    'pillGreen' => $pillGreen,
  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  
  <?php echo $__env->make('depot-stock.partials.batches-metric-modal', [
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
  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php endif; ?><?php /**PATH /home/runner/workspace/resources/views/depot-stock/_details.blade.php ENDPATH**/ ?>