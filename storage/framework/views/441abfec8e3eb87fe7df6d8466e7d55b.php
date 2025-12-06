

<?php
    $title = 'Depots';
    $subtitle = 'Configure where your AGO is stored and how storage fees apply.';
?>

<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('status')): ?>
    <div class="mb-4 rounded-lg bg-emerald-900/40 border border-emerald-500/60 px-3 py-2 text-xs text-emerald-100">
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">

    
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold">Depots</h2>

            
            <button onclick="openModal('createDepotModal')"
                    class="px-3 py-1.5 text-xs rounded-lg bg-emerald-600 hover:bg-emerald-500 text-slate-900 font-semibold">
                + New depot
            </button>
        </div>

        <ul class="space-y-1 text-xs">
            <?php $__currentLoopData = $depots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $depot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <a href="<?php echo e(route('settings.depots.index', ['depot' => $depot->id])); ?>"
                       class="flex items-center justify-between px-3 py-2 rounded-xl
                        <?php echo e($currentDepot && $currentDepot->id === $depot->id
                            ? 'bg-slate-800 text-slate-50'
                            : 'bg-slate-950/40 text-slate-300 hover:bg-slate-900'); ?>">
                        <div>
                            <div class="font-semibold text-[13px]"><?php echo e($depot->name); ?></div>
                            <div class="text-[10px] text-slate-500">
                                <?php echo e($depot->city ?: 'No city set'); ?>

                            </div>
                        </div>

                        <span class="text-[9px] px-2 py-0.5 rounded-full
                            <?php echo e($depot->is_active
                                ? 'bg-emerald-900/50 text-emerald-200 border border-emerald-500/60'
                                : 'bg-slate-800 text-slate-300 border border-slate-500/50'); ?>">
                            <?php echo e($depot->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>

    
    <div class="md:col-span-2">
        <?php if($currentDepot): ?>
            <?php echo $__env->make('settings.depots._details', ['depot' => $currentDepot], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-xs text-slate-400">
                No depots yet. Create one on the left.
            </div>
        <?php endif; ?>
    </div>
</div>


<div id="createDepotModal"
     class="fixed inset-0 bg-black/50 hidden flex items-center justify-center p-4 z-50"
     onclick="closeOnBg(event, 'createDepotModal')">

    <div class="bg-slate-900 border border-slate-700 rounded-xl p-5 w-full max-w-md"
         onclick="event.stopPropagation()">

        <h2 class="text-sm font-semibold mb-3">Create depot</h2>

        <form method="POST" action="<?php echo e(route('settings.depots.store')); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>

            <div>
                <label class="text-xs text-slate-400">Depot name</label>
                <input type="text" name="name"
                       class="w-full mt-1 rounded-lg bg-slate-800 border border-slate-700 p-2 text-xs"
                       required>
            </div>

            <div>
                <label class="text-xs text-slate-400">City</label>
                <input type="text" name="city"
                       class="w-full mt-1 rounded-lg bg-slate-800 border border-slate-700 p-2 text-xs">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" value="1" checked
                       class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500/60">
                <span class="text-xs text-slate-300">Depot is active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('createDepotModal')"
                        class="px-3 py-1.5 rounded-lg text-xs bg-slate-700 hover:bg-slate-600">
                    Cancel
                </button>

                <button class="px-4 py-1.5 rounded-lg text-xs bg-emerald-500 text-slate-900 font-semibold">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    function openModal(id) {
        document.getElementById(id)?.classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id)?.classList.add('hidden');
    }

    function closeOnBg(event, id) {
        if (event.target.id === id) {
            closeModal(id);
        }
    }
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/depots/index.blade.php ENDPATH**/ ?>