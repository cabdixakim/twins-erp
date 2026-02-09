

<?php
  /** @var \App\Models\Purchase $purchase */
  $purchase = $purchase;

  // Theme tokens (from your app.css)
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  // Labels
  $typeLabel = match($purchase->type) {
    'import' => 'Import',
    'local_depot' => 'Local depot',
    'cross_dock' => 'Cross dock',
    default => ucfirst(str_replace('_',' ', (string) $purchase->type)),
  };

  // Status pill (keep your good dark look; only tokenise draft)
  $statusPill = match($purchase->status) {
    'draft' => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'confirmed' => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-100',
    default => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };

  $qty   = (float) ($purchase->qty ?? 0);
  $unit  = (float) ($purchase->unit_price ?? 0);
  $total = $qty * $unit;

  // Supplier display (adjust if you store it differently)
  $supplierName = $purchase->supplier_name ?? ($purchase->supplier?->name ?? ($purchase->supplier ?? '—'));
?>



<?php $__env->startSection('title', 'Purchase'); ?>
<?php $__env->startSection('subtitle', 'Review and confirm'); ?>

<?php $__env->startSection('content'); ?>

<div class="flex flex-col gap-4">

  
  <div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="flex items-center gap-3">
        <h1 class="text-xl font-semibold <?php echo e($fg); ?>">
          Purchase #<?php echo e($purchase->display_ref ?? $purchase->id); ?>

        </h1>

        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold border-emerald-600 bg-emerald-500 text-white">
          <?php echo e(ucfirst($purchase->status)); ?>

        </span>

        <?php if($purchase->batch_id): ?>
          <span class="inline-flex items-center rounded-full border <?php echo e($border); ?> <?php echo e($surface2); ?> px-2.5 py-1 text-xs font-semibold <?php echo e($fg); ?>">
            Batch #<?php echo e($purchase->batch_id); ?>

          </span>
        <?php endif; ?>
      </div>

      <p class="mt-1 text-sm <?php echo e($muted); ?>">
        Review key details and confirm when ready. Confirming creates a Batch and routes it into the correct workflow.
      </p>

     
    </div>

    <div class="shrink-0 flex items-center gap-2">
      <a href="<?php echo e(route('purchases.index')); ?>"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($fg); ?>

                hover:bg-[color:var(--tw-surface)]">
        <span class="text-base">←</span>
        Back
      </a>

      <?php if($purchase->status === 'draft'): ?>
        <form method="POST" action="<?php echo e(route('purchases.confirm', $purchase)); ?>">
          <?php echo csrf_field(); ?>
          <button type="submit"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600
                         text-sm font-semibold text-white hover:bg-emerald-500/20">
            Confirm
            <span class="text-emerald-200/90">→</span>
          </button>
        </form>
      <?php else: ?>
        <span class="inline-flex items-center h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($muted); ?>">
          Confirmed
        </span>
      <?php endif; ?>
    </div>
  </div>

  <?php if(session('status')): ?>
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 dark:bg-emerald-500/20 p-3 text-sm text-emerald-900 dark:text-emerald-100">
      <?php echo nl2br(e(session('status'))); ?>

    </div>
  <?php endif; ?>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-5">
    <div class="grid gap-4 sm:grid-cols-2">

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Supplier</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($supplierName); ?></div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Type</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($typeLabel); ?></div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Date</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($purchase->purchase_date?->format('Y-m-d') ?? '—'); ?></div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Quantity</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
          <?php echo e(number_format($qty, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
        </div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Unit price</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
          <span class="<?php echo e($muted); ?>"><?php echo e(strtoupper($purchase->currency ?? 'USD')); ?></span>
          <?php echo e(number_format($unit, 6)); ?>

        </div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Estimated total</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
          <span class="<?php echo e($muted); ?>"><?php echo e(strtoupper($purchase->currency ?? 'USD')); ?></span>
          <?php echo e(number_format($total, 2)); ?>

        </div>
      </div>

      <?php if($purchase->type === 'local_depot'): ?>
        <div class="sm:col-span-2 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
          <div class="text-[11px] <?php echo e($muted); ?>">Depot</div>
          <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($purchase->depot_id ?? '—'); ?></div>
          
        </div>
      <?php endif; ?>

      <div class="sm:col-span-2 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Notes</div>
        <div class="mt-1 text-sm <?php echo e($fg); ?>"><?php echo e($purchase->notes ?: '—'); ?></div>
      </div>
    </div>

    
    <div class="mt-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($fg); ?>">
      <?php if($purchase->type === 'import'): ?>
        <span class="<?php echo e($muted); ?>">After confirmation:</span>
        this purchase waits for nominations/offload to be received into a depot.
      <?php elseif($purchase->type === 'local_depot'): ?>
        <span class="<?php echo e($muted); ?>">After confirmation:</span>
        receive it into the selected depot from Depot Stock.
      <?php else: ?>
        <span class="<?php echo e($muted); ?>">After confirmation:</span>
        receipt into <span class="font-semibold">CROSS DOCK</span> immediately, ready for direct sale.
      <?php endif; ?>
    </div>

    
    <div class="mt-4 flex flex-wrap items-center gap-2">
      <a href="<?php echo e(route('purchases.index')); ?>"
         class="inline-flex items-center h-9 px-3 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-xs font-semibold <?php echo e($fg); ?>

                hover:bg-[color:var(--tw-surface)]">
        ← Back to list
      </a>

   
    </div>
  </div>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/show.blade.php ENDPATH**/ ?>