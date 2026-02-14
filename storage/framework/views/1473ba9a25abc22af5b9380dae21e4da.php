

<?php
  $fmtL = fn ($v) => number_format((float)$v, 0);
  $fmtM = fn ($v) => number_format((float)$v, 2);

  // Buttons (stand out in BOTH light + dark)
  $btnGreen = "border-emerald-600 bg-emerald-500 text-white";
  $btnGhost = "inline-flex items-center gap-2 rounded-xl border $border $surface2 px-4 py-2 text-sm font-semibold $fg hover:bg-(--tw-surface)";
  $btnLink  = "text-sm $muted hover:text-[color:var(--tw-fg)]";
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
        <button type="button" disabled
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($btnGreen); ?> opacity-40 cursor-not-allowed">
          Receive
        </button>
        <button type="button" disabled
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> <?php echo e($fg); ?> opacity-40 cursor-not-allowed">
          New sale
        </button>
        <button type="button" disabled
                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> <?php echo e($fg); ?> opacity-40 cursor-not-allowed">
          Adjustment
        </button>
      </div>
    </div>
  </div>

  
  <div class="grid sm:grid-cols-4 gap-3 mb-4">
    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">On hand</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>"><?php echo e($fmtL($metrics['on_hand_l'] ?? 0)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span></div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Physical available in depot</div>
    </div>

    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Reserved</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>"><?php echo e($fmtL($metrics['reserved_l'] ?? 0)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span></div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Allocated to open sales</div>
    </div>

    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Batches</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>"><?php echo e((int)($metrics['batches'] ?? 0)); ?></div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">FIFO layers in this depot</div>
    </div>

    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
      <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Stock value</div>
      <div class="mt-1 text-xl font-semibold <?php echo e($fg); ?>"><?php echo e($fmtM($metrics['value'] ?? 0)); ?></div>
      <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Qty × unit cost snapshot</div>
    </div>
  </div>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> overflow-hidden">
    <div class="p-4 border-b <?php echo e($border); ?> <?php echo e($surface2); ?> flex items-center justify-between gap-3">
      <div>
        <div class="text-sm font-semibold <?php echo e($fg); ?>">Depot stock</div>
        <div class="mt-0.5 text-xs <?php echo e($muted); ?>">Batch-aware rows (FIFO-ready)</div>
      </div>

      <span class="inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold <?php echo e($border); ?> <?php echo e($surface); ?> <?php echo e($muted); ?>">
        <?php echo e($stocks->count()); ?> rows
      </span>
    </div>

    <?php if($stocks->isEmpty()): ?>
      <div class="p-6 text-sm <?php echo e($muted); ?>">
        No stock recorded yet for this depot.
        <span class="<?php echo e($fg); ?> font-semibold">Receive</span> or <span class="<?php echo e($fg); ?> font-semibold">confirm cross dock</span> to populate.
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
            </tr>
          </thead>
          <tbody class="divide-y <?php echo e($border); ?>">
            <?php $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $batchCode = $row->batch?->code ?? ('Batch #' . ($row->batch_id ?? '—'));
                $product   = $row->product?->name ?? ('Product #' . ($row->product_id ?? '—'));
                $value     = ((float)$row->qty_on_hand) * ((float)$row->unit_cost);
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
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> overflow-hidden mt-4">
    <div class="p-4 border-b <?php echo e($border); ?> <?php echo e($surface2); ?> flex items-center justify-between gap-3">
      <div>
        <div class="text-sm font-semibold <?php echo e($fg); ?>">Recent movements</div>
        <div class="mt-0.5 text-xs <?php echo e($muted); ?>">Last 12 receipts into this depot</div>
      </div>
    </div>

    <?php if($recentMovements->isEmpty()): ?>
      <div class="p-6 text-sm <?php echo e($muted); ?>">No movements yet.</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="<?php echo e($surface2); ?> border-b <?php echo e($border); ?>">
            <tr class="text-left">
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">When</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Type</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Batch</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Product</th>
              <th class="px-4 py-3 text-[11px] font-semibold <?php echo e($muted); ?>">Qty</th>
            </tr>
          </thead>
          <tbody class="divide-y <?php echo e($border); ?>">
            <?php $__currentLoopData = $recentMovements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr class="hover:bg-(--tw-surface-2)/60">
                <td class="px-4 py-3 <?php echo e($muted); ?>"><?php echo e($m->created_at?->format('Y-m-d H:i')); ?></td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold <?php echo e($pillGreen); ?>">
                    <?php echo e(strtoupper($m->type)); ?>

                  </span>
                </td>
                <td class="px-4 py-3 <?php echo e($fg); ?>"><?php echo e($m->batch?->code ?? ('#' . ($m->batch_id ?? '—'))); ?></td>
                <td class="px-4 py-3 <?php echo e($fg); ?>"><?php echo e($m->product?->name ?? ('#' . ($m->product_id ?? '—'))); ?></td>
                <td class="px-4 py-3 font-semibold <?php echo e($fg); ?>"><?php echo e(number_format((float)$m->qty, 3)); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/depot-stock/_details.blade.php ENDPATH**/ ?>