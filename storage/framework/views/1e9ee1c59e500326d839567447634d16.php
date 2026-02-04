

<?php $__env->startSection('title','AGO Purchases'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div>
            <div class="text-lg font-semibold">AGO Purchases</div>
            <div class="text-sm text-slate-500">Draft → Confirmed creates a Batch.</div>
        </div>
        <a href="<?php echo e(route('purchases.create')); ?>"
           class="h-9 inline-flex items-center px-3 rounded-xl text-sm font-semibold bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800">
            New purchase
        </a>
    </div>

    <div class="rounded-2xl ring-1 ring-slate-200/10 bg-slate-950 overflow-hidden">
        <div class="divide-y divide-slate-800">
            <?php $__empty_1 = true; $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <a href="<?php echo e(route('ago-purchases.show',$p)); ?>" class="block px-4 py-3 hover:bg-slate-900/40">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-100 truncate">
                                <?php echo e($p->type === 'import' ? 'Import' : 'Local (in depot)'); ?> • <?php echo e(number_format($p->qty_liters,3)); ?> L
                            </div>
                            <div class="text-xs text-slate-500 truncate">
                                <?php echo e($p->purchase_date?->format('Y-m-d') ?? '—'); ?> • <?php echo e(strtoupper($p->currency)); ?> <?php echo e(number_format($p->unit_price,2)); ?> • <?php echo e($p->status); ?>

                            </div>
                        </div>
                        <div class="text-xs px-2 py-1 rounded-lg ring-1 <?php echo e($p->status==='confirmed' ? 'text-emerald-300 bg-emerald-500/10 ring-emerald-500/20' : 'text-amber-200 bg-amber-500/10 ring-amber-500/20'); ?>">
                            <?php echo e(ucfirst($p->status)); ?>

                        </div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="p-6 text-sm text-slate-400">No purchases yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <?php echo e($purchases->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/index.blade.php ENDPATH**/ ?>