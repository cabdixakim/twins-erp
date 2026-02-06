<?php
    $purchase = $purchase;
?>



<?php $__env->startSection('title', 'Purchase'); ?>
<?php $__env->startSection('subtitle', 'Review and confirm'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-220">

    
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-100">
                Purchase #<?php echo e($purchase->id); ?>

            </h1>
            <div class="text-[12px] text-slate-400">
                Status: <?php echo e($purchase->status); ?>

                <?php if($purchase->batch_id): ?>
                    • Batch #<?php echo e($purchase->batch_id); ?>

                <?php endif; ?>
            </div>
        </div>

        <a href="<?php echo e(route('purchases.index')); ?>"
           class="text-[12px] px-3 py-2 rounded-md border border-slate-700">
            Back
        </a>
    </div>

    
    <?php if(session('status')): ?>
        <div class="mb-3 text-[12px] text-emerald-300">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    
    <div class="border border-slate-800 rounded-lg p-4 text-[13px]">

        <div class="grid grid-cols-2 gap-3">
            <div>
                <div class="text-[11px] text-slate-500">Type</div>
                <div><?php echo e(ucfirst($purchase->type)); ?></div>
            </div>

            <div>
                <div class="text-[11px] text-slate-500">Date</div>
                <div><?php echo e($purchase->purchase_date?->format('Y-m-d') ?? '—'); ?></div>
            </div>

            <div>
                <div class="text-[11px] text-slate-500">Quantity</div>
                <div><?php echo e(number_format((float)$purchase->qty,3)); ?></div>
            </div>

            <div>
                <div class="text-[11px] text-slate-500">Unit price</div>
                <div><?php echo e($purchase->currency); ?> <?php echo e(number_format((float)$purchase->unit_price,6)); ?></div>
            </div>

            <div class="col-span-2">
                <div class="text-[11px] text-slate-500">Notes</div>
                <div><?php echo e($purchase->notes ?: '—'); ?></div>
            </div>
        </div>
    </div>

    
    <?php if($purchase->status === 'draft'): ?>
        <div class="mt-4 flex justify-end">
            <form method="POST" action="<?php echo e(route('purchases.confirm', $purchase)); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit"
                        class="text-[12px] px-4 py-2 rounded-md
                               bg-emerald-600/20 text-emerald-300 border border-emerald-500/30">
                    Confirm → Create batch
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/show.blade.php ENDPATH**/ ?>