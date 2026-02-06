

<?php
    $title    = 'Depot stock';
    $subtitle = 'See live AGO position by depot and start receive / sale / adjustments.';
?>

<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startSection('content'); ?>

    <?php if(session('status')): ?>
        <div class="mb-4 rounded-lg bg-emerald-900/30 border border-emerald-500/60 px-3 py-2 text-xs text-emerald-100">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-3 gap-6">

        
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-sm font-semibold">Depots</h2>
                    <p class="text-[11px] text-slate-400">
                        Choose where you want to work today.
                    </p>
                </div>
            </div>

            <?php if($depots->isEmpty()): ?>
                <p class="text-xs text-slate-500">
                    No depots configured yet. Go to Settings → Depots to add one.
                </p>
            <?php else: ?>
                <ul class="space-y-1 text-xs">
                    <?php $__currentLoopData = $depots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $depot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <a href="<?php echo e(route('depot-stock.index', ['depot' => $depot->id])); ?>"
                               class="flex items-center justify-between px-3 py-2 rounded-xl
                               <?php echo e($currentDepot && $currentDepot->id === $depot->id
                                   ? 'bg-slate-800 text-slate-50'
                                   : 'bg-slate-950/40 text-slate-300 hover:bg-slate-900'); ?>">
                                <div class="min-w-0">
                                    <div class="font-semibold text-[13px] truncate"><?php echo e($depot->name); ?></div>
                                    <div class="text-[10px] text-slate-500 truncate">
                                        <?php echo e($depot->city ?: 'City not set'); ?>

                                    </div>
                                </div>

                                <span class="text-[9px] px-2 py-0.5 rounded-full
                                    <?php echo e($depot->is_active
                                        ? 'bg-emerald-900/50 text-emerald-200 border border-emerald-500/60'
                                        : 'bg-slate-800 text-slate-300 border border-slate-500/60'); ?>">
                                    <?php echo e($depot->is_active ? 'Active' : 'Inactive'); ?>

                                </span>
                            </a>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>

        
        <div class="md:col-span-2 space-y-4">

            <?php if(!$currentDepot): ?>
                <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/50 p-6 text-center">
                    <p class="text-sm text-slate-300 mb-1">No depots available yet.</p>
                    <p class="text-xs text-slate-500">
                        First configure your depots under <strong>Settings → Depots</strong>, then come back here.
                    </p>
                </div>
            <?php else: ?>
                
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Working depot</div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold truncate"><?php echo e($currentDepot->name); ?></h2>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                                <?php echo e($currentDepot->is_active ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/50'
                                                            : 'bg-slate-800 text-slate-300 border border-slate-700'); ?>">
                                <?php echo e($currentDepot->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            <?php echo e($currentDepot->city ?: 'City not set'); ?>

                        </p>
                    </div>

                    
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-500/90 text-slate-950 hover:bg-emerald-400 disabled:opacity-40"
                            disabled
                        >
                            Receive AGO
                            <span class="ml-1 text-[9px] uppercase tracking-wide">Soon</span>
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-cyan-500/90 text-slate-950 hover:bg-cyan-400 disabled:opacity-40"
                            disabled
                        >
                            New sale
                            <span class="ml-1 text-[9px] uppercase tracking-wide">Soon</span>
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1.5 rounded-xl text-[11px] font-semibold bg-slate-800 text-slate-100 hover:bg-slate-700 disabled:opacity-40"
                            disabled
                        >
                            Adjustment
                            <span class="ml-1 text-[9px] uppercase tracking-wide">Soon</span>
                        </button>
                    </div>
                </div>

                
                <div class="grid sm:grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">On hand</div>
                        <div class="mt-1 text-lg font-semibold">
                            <?php echo e(number_format($metrics['on_hand_l'], 0)); ?> L
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Physical stock in this depot
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">In transit</div>
                        <div class="mt-1 text-lg font-semibold">
                            <?php echo e(number_format($metrics['in_transit_l'], 0)); ?> L
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Trucks not yet offloaded here
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3">
                        <div class="text-[11px] uppercase tracking-wide text-slate-500">Reserved</div>
                        <div class="mt-1 text-lg font-semibold">
                            <?php echo e(number_format($metrics['reserved_l'], 0)); ?> L
                        </div>
                        <div class="text-[11px] text-slate-500">
                            Linked to open sales / clients
                        </div>
                    </div>
                </div>

                
                <div class="rounded-2xl border border-slate-800 bg-slate-950/80 px-4 py-3 mt-2">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold">Recent movements</h3>
                        <span class="text-[11px] text-slate-500">Coming soon</span>
                    </div>
                    <p class="text-[11px] text-slate-400">
                        Once we wire purchase offloads, sales and adjustments, you’ll see a live ledger
                        of all AGO movements for <strong><?php echo e($currentDepot->name); ?></strong> here.
                    </p>
                </div>
            <?php endif; ?>

        </div>
    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/depot-stock/index.blade.php ENDPATH**/ ?>