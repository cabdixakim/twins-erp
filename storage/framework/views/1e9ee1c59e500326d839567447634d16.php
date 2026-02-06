<?php
    $purchases = $purchases ?? null;
?>



<?php $__env->startSection('title', 'Purchases'); ?>
<?php $__env->startSection('subtitle', 'Draft → Confirm → Batch → Workflow'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-220">

    
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-100">Purchases</h1>
            <p class="mt-0.5 text-[12px] text-slate-400">
                Draft purchases first. Confirm to create batches.
            </p>
        </div>

        <a href="<?php echo e(route('purchases.create')); ?>"
           class="h-8 px-3 inline-flex items-center rounded-lg text-[12px] font-semibold
                  bg-slate-800 hover:bg-slate-700 ring-1 ring-slate-700 transition">
            + New purchase
        </a>
    </div>

    
    <?php if(session('status')): ?>
        <div class="mb-3 text-[12px] text-emerald-300">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    
    <div class="border border-slate-800 rounded-lg divide-y divide-slate-800">

        <?php $__empty_1 = true; $__currentLoopData = ($purchases?->items() ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="<?php echo e(route('purchases.show', $p)); ?>"
               class="block px-3 py-2 hover:bg-slate-900 transition">

                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[13px] font-semibold text-slate-100">
                                Purchase #<?php echo e($p->id); ?>

                            </span>

                            <?php if($p->status === 'confirmed'): ?>
                                <span class="text-[11px] text-emerald-300">
                                    confirmed
                                </span>
                            <?php else: ?>
                                <span class="text-[11px] text-slate-400">
                                    draft
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-0.5 text-[11px] text-slate-500">
                            <?php echo e(ucfirst($p->type)); ?>

                            • <?php echo e(number_format((float)$p->qty, 3)); ?>

                             <?php echo e($p->Product?->base_uom); ?>

                            • <?php echo e($p->currency); ?>

                            • <?php echo e($p->purchase_date?->format('Y-m-d') ?? 'no date'); ?>

                        </div>
                           <span class="text-[11px] text-slate-500">- Created by: <?php echo e($p->creator?->name ?? ''); ?></span>
                    </div>

                    <div class="text-[11px] text-slate-500">
                        Open →
                    </div>
                </div>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="px-4 py-6 text-center">
                <div class="text-[13px] text-slate-300">No purchases yet</div>
                <div class="text-[12px] text-slate-500 mt-1">
                    Create your first draft purchase.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if(method_exists($purchases, 'links')): ?>
        <div class="mt-3">
            <?php echo e($purchases->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/index.blade.php ENDPATH**/ ?>