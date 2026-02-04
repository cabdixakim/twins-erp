<?php
    /** @var \App\Models\Purchase $purchase */
    $purchase = $purchase ?? null;
?>



<?php $__env->startSection('title', 'Purchase'); ?>
<?php $__env->startSection('subtitle', 'Review and confirm to create a batch'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-[1000px]">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight text-slate-100">
                Purchase #<?php echo e($purchase->id); ?>

            </h1>
            <p class="mt-1 text-[12px] text-slate-400">
                Status: <span class="text-slate-200 font-semibold"><?php echo e($purchase->status); ?></span>
                <?php if($purchase->batch_id): ?>
                    • Batch: <span class="text-slate-200 font-semibold">#<?php echo e($purchase->batch_id); ?></span>
                <?php endif; ?>
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="<?php echo e(route('purchases.index')); ?>"
               class="h-9 px-3 inline-flex items-center rounded-xl text-[12px] font-semibold
                      bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                Back
            </a>

            <?php if(($purchase->status ?? '') === 'draft'): ?>
                <form method="POST" action="<?php echo e(route('purchases.confirm', $purchase)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                            class="h-9 px-3 rounded-xl text-[12px] font-semibold
                                   bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20 transition">
                        Confirm (create batch)
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="mb-4 rounded-2xl bg-emerald-500/10 ring-1 ring-emerald-500/20 px-4 py-3 text-[12px] text-emerald-200">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="mb-4 rounded-2xl bg-rose-500/10 ring-1 ring-rose-500/20 px-4 py-3 text-[12px] text-rose-200">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="rounded-2xl bg-slate-950 ring-1 ring-slate-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800">
            <div class="text-[13px] font-semibold text-slate-100">Details</div>
            <div class="text-[11px] text-slate-400 mt-0.5">This is what will be locked into the batch on confirm.</div>
        </div>

        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-[12px]">
            <div class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-800 p-3">
                <div class="text-slate-500 text-[11px]">Type</div>
                <div class="text-slate-100 font-semibold mt-0.5"><?php echo e($purchase->type); ?></div>
            </div>

            <div class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-800 p-3">
                <div class="text-slate-500 text-[11px]">Date</div>
                <div class="text-slate-100 font-semibold mt-0.5">
                    <?php echo e($purchase->purchase_date ? $purchase->purchase_date->format('Y-m-d') : '—'); ?>

                </div>
            </div>

            <div class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-800 p-3">
                <div class="text-slate-500 text-[11px]">Quantity</div>
                <div class="text-slate-100 font-semibold mt-0.5"><?php echo e(number_format((float)$purchase->qty, 3)); ?></div>
            </div>

            <div class="rounded-2xl bg-slate-900/40 ring-1 ring-slate-800 p-3">
                <div class="text-slate-500 text-[11px]">Unit price</div>
                <div class="text-slate-100 font-semibold mt-0.5">
                    <?php echo e($purchase->currency); ?> <?php echo e(number_format((float)$purchase->unit_price, 6)); ?>

                </div>
            </div>

            <div class="sm:col-span-2 rounded-2xl bg-slate-900/40 ring-1 ring-slate-800 p-3">
                <div class="text-slate-500 text-[11px]">Notes</div>
                <div class="text-slate-100 mt-0.5"><?php echo e($purchase->notes ?: '—'); ?></div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/show.blade.php ENDPATH**/ ?>