

<?php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  // Your “nice green pill/button” style (like screenshot)
  $pillGreen = 'border-emerald-600 bg-emerald-500 text-white';
  $btnGreen  = 'border-emerald-600 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-700';
?>



<?php $__env->startSection('title', 'Depot stock'); ?>
<?php $__env->startSection('subtitle', 'Live position by depot (batch-aware / FIFO-ready)'); ?>

<?php $__env->startSection('content'); ?>

<div class="grid md:grid-cols-12 gap-4">

  
  <aside class="md:col-span-4 lg:col-span-3 rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3">
    <div class="flex items-center justify-between gap-3 px-2 pt-2 pb-3">
      <div class="min-w-0">
        <div class="text-sm font-semibold <?php echo e($fg); ?>">Depots</div>
        <div class="mt-0.5 text-xs <?php echo e($muted); ?>">Pick a depot to view stock</div>
      </div>

      
      <span class="inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold <?php echo e($border); ?> <?php echo e($surface2); ?> <?php echo e($muted); ?>">
        <?php echo e($depots->count()); ?> total
      </span>
    </div>

    <?php if($depots->isEmpty()): ?>
      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($muted); ?>">
        No depots yet. Go to <span class="<?php echo e($fg); ?> font-semibold">Settings → Depots</span> and add one.
      </div>
    <?php else: ?>
      <div class="space-y-1">
        <?php $__currentLoopData = $depots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $active = $currentDepot && $currentDepot->id === $d->id;
          ?>

          <a href="<?php echo e(route('depot-stock.index', ['depot' => $d->id])); ?>"
             class="group flex items-center justify-between gap-3 rounded-xl border px-3 py-2 transition
                    <?php echo e($active ? $pillGreen : $border . ' ' . $surface2 . ' ' . $fg); ?>

                    <?php echo e($active ? '' : 'hover:border-emerald-500/40 hover:bg-[color:var(--tw-surface)]'); ?>">
            <div class="min-w-0">
              <div class="text-sm font-semibold truncate <?php echo e($active ? 'text-white' : $fg); ?>">
                <?php echo e($d->name); ?>

              </div>
              <div class="text-[11px] truncate <?php echo e($active ? 'text-white/80' : $muted); ?>">
                <?php echo e($d->city ?: 'City not set'); ?>

              </div>
            </div>

            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border
                         <?php echo e($d->is_active ? ($active ? 'border-white/30 text-white/90' : 'border-emerald-500/30 text-emerald-300')
                                         : 'border-slate-500/30 text-slate-400'); ?>">
              <?php echo e($d->is_active ? 'Active' : 'Inactive'); ?>

            </span>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    <?php endif; ?>
  </aside>

  
  <main class="md:col-span-8 lg:col-span-9">
    <?php echo $__env->make('depot-stock._details', [
      'border' => $border,
      'surface' => $surface,
      'surface2' => $surface2,
      'fg' => $fg,
      'muted' => $muted,
      'btnGreen' => $btnGreen,
      'pillGreen' => $pillGreen,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </main>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/depot-stock/index.blade.php ENDPATH**/ ?>