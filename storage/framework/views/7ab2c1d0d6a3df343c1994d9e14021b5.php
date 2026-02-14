
<?php
  // Expect: $recentMovements, $border, $surface, $surface2, $fg, $muted, $pillGreen
?>

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
            <tr class="hover:bg-[color:var(--tw-surface-2)]/60">
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
</div><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/depot-stock/partials/recent-movements.blade.php ENDPATH**/ ?>