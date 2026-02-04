<?php
    $purchases = $purchases ?? null;
?>



<?php $__env->startSection('title', 'Purchases'); ?>
<?php $__env->startSection('subtitle', 'Draft → Confirm → Batch → Next workflow'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-[1100px]">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight text-slate-100">Purchases</h1>
            <p class="mt-1 text-[12px] text-slate-400">Create drafts, then confirm to open a batch.</p>
        </div>

        <a href="<?php echo e(route('purchases.create')); ?>"
           class="h-9 px-3 inline-flex items-center rounded-xl text-[12px] font-semibold
                  bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
            New purchase
        </a>
    </div>

    <?php if(session('status')): ?>
        <div class="mb-4 rounded-2xl bg-emerald-500/10 ring-1 ring-emerald-500/20 px-4 py-3 text-[12px] text-emerald-200">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="rounded-2xl bg-slate-950 ring-1 ring-slate-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
            <div class="text-[11px] uppercase tracking-wide text-slate-500">Recent</div>
            <div class="text-[11px] text-slate-500">
                <?php echo e(method_exists($purchases, 'total') ? $purchases->total() : 0); ?> total
            </div>
        </div>

        <div class="divide-y divide-slate-800">
            <?php $__empty_1 = true; $__currentLoopData = ($purchases?->items() ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-4 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="text-[13px] font-semibold text-slate-100 truncate">
                                Purchase #<?php echo e($p->id); ?>

                            </div>

                            <?php if(($p->status ?? '') === 'confirmed'): ?>
                                <span class="text-[11px] text-emerald-300 bg-emerald-500/10 ring-1 ring-emerald-500/20 px-2 py-0.5 rounded-lg">
                                    Confirmed
                                </span>
                            <?php else: ?>
                                <span class="text-[11px] text-slate-300 bg-slate-800/60 ring-1 ring-slate-700/60 px-2 py-0.5 rounded-lg">
                                    Draft
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-1 text-[11px] text-slate-500 truncate">
                            Type: <?php echo e($p->type ?? '-'); ?> • Qty: <?php echo e(number_format((float)($p->qty ?? 0), 3)); ?> • <?php echo e($p->currency ?? 'USD'); ?>

                            • <?php echo e($p->purchase_date ? $p->purchase_date->format('Y-m-d') : 'No date'); ?>

                        </div>
                    </div>

                    <div class="shrink-0">
                        <a href="<?php echo e(route('purchases.show', $p)); ?>"
                           class="h-9 inline-flex items-center px-3 rounded-xl text-[12px] font-semibold
                                  bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                            Open
                        </a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="p-5">
                    <div class="text-[13px] font-semibold text-slate-200">No purchases yet</div>
                    <div class="text-[12px] text-slate-400 mt-1">Create your first purchase draft.</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if(method_exists($purchases, 'links')): ?>
            <div class="px-4 py-3 border-t border-slate-800">
                <?php echo e($purchases->links()); ?>

            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/index.blade.php ENDPATH**/ ?>