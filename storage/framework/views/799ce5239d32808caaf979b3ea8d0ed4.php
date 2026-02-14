



<?php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $selectedId = $selected?->id ?? null;

  $fieldBase = "mt-1 w-full rounded-xl border {$border} {$surface2} p-2 text-sm {$fg} outline-none focus:ring-2 focus:ring-emerald-500/30";
  $fieldErr  = "border-rose-500/40 ring-2 ring-rose-500/20";
  $errText   = "mt-1 text-[11px] text-rose-600 font-bold";

  $statusPill = fn($s) => match($s) {
    'draft'    => 'border-gray-300 bg-gray-100 text-gray-700',
    'posted'   => 'border-emerald-500/30 bg-emerald-600/15 text-emerald-700',
    default    => 'border-gray-300 bg-gray-100 text-gray-700',
  };
?>

<?php $__env->startSection('title', 'Sales'); ?>
<?php $__env->startSection('subtitle', 'Draft → Posted issues stock (FIFO)'); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('status')): ?>
  <div class="mb-4 rounded-xl border border-emerald-400/20 bg-emerald-100/60 text-emerald-900 px-4 py-3 text-sm font-semibold flex items-center gap-2 shadow-sm">
    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
    <span><?php echo e(session('status')); ?></span>
  </div>
<?php endif; ?>

<?php if(session('error')): ?>
  <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-100">
    <?php echo e(session('error')); ?>

  </div>
<?php endif; ?>

<div class="grid gap-6 md:grid-cols-3">

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="text-sm font-semibold <?php echo e($fg); ?>">Sales</div>
        <div class="mt-1 text-xs <?php echo e($muted); ?>">Select a sale to view details.</div>
      </div>

      <button type="button" id="btnNewSale"
        class="inline-flex items-center gap-2 h-9 px-3 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-xs font-semibold hover:bg-emerald-600 hover:border-emerald-700 transition">
        + New
      </button>
    </div>

    <div class="mt-4 space-y-2">
      <?php $__empty_1 = true; $__currentLoopData = $sales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $isActive = $selectedId === $s->id; $pill = $statusPill($s->status); ?>
        <a href="<?php echo e(route('sales.index', ['sale' => $s->id])); ?>"
           class="block rounded-xl border <?php echo e($border); ?> <?php echo e($isActive ? $surface2 : ''); ?> p-3 hover:bg-[color:var(--tw-surface-2)] transition">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs <?php echo e($muted); ?>">#<?php echo e($s->reference); ?></div>
              <div class="mt-0.5 text-sm font-semibold <?php echo e($fg); ?> truncate">
                <?php echo e($s->client_name ?: 'Client —'); ?>

              </div>
              <div class="mt-1 text-[11px] <?php echo e($muted); ?> truncate">
                <?php echo e($s->depot?->name ?? 'Depot'); ?> · <?php echo e($s->product?->name ?? 'Product'); ?>

              </div>
            </div>

            <span class="shrink-0 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold <?php echo e($pill); ?>">
              <?php echo e(ucfirst($s->status)); ?>

            </span>
          </div>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-xs <?php echo e($muted); ?>">No sales yet.</div>
      <?php endif; ?>
    </div>

    <div class="mt-4">
      <?php echo e($sales->links()); ?>

    </div>
  </div>

  
  <div class="md:col-span-2 space-y-4">
    <?php if(!$selected): ?>
      <div class="rounded-2xl border border-dashed <?php echo e($border); ?> <?php echo e($surface); ?> p-6 text-center">
        <div class="text-sm <?php echo e($fg); ?>">No sale selected.</div>
        <div class="mt-1 text-xs <?php echo e($muted); ?>">Create one using “New”.</div>
      </div>
    <?php else: ?>
      <?php echo $__env->make('sales.partials.details', ['sale' => $selected], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
  </div>

</div>


<?php echo $__env->make('sales.partials.sale-modal', [
  'border' => $border,
  'surface' => $surface,
  'surface2' => $surface2,
  'fg' => $fg,
  'muted' => $muted,
  'fieldBase' => $fieldBase,
  'fieldErr' => $fieldErr,
  'errText' => $errText,
  'depots' => $depots,
  'products' => $products,
  'transporters' => $transporters,
  'selected' => $selected,
], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/sales/index.blade.php ENDPATH**/ ?>