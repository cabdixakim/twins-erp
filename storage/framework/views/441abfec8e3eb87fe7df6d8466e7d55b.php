

<?php
    $title = 'Depots';
    $subtitle = 'Configure where your AGO is stored and how storage fees apply.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost   = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
    $btnPrimary = "inline-flex items-center justify-center rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
?>

<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('status')): ?>
    <div class="mb-4 rounded-xl bg-emerald-600 text-white border border-emerald-500/50 px-3 py-2 text-xs font-semibold">
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-6">

    
    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold <?php echo e($fg); ?>">Depots</h2>

            
            <button onclick="openModal('createDepotModal')"
                    class="<?php echo e($btnPrimary); ?> px-3 py-1.5 text-xs">
                + New depot
            </button>
        </div>

        <ul class="space-y-1 text-xs">
            <?php $__currentLoopData = $depots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $depot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <a href="<?php echo e(route('settings.depots.index', ['depot' => $depot->id])); ?>"
                       class="flex items-center justify-between px-3 py-2 rounded-xl border transition
                        <?php echo e($currentDepot && $currentDepot->id === $depot->id
                            ? 'border-emerald-500/45 bg-[color:var(--tw-surface-2)] shadow-sm'
                            : 'border-[color:var(--tw-border)] hover:bg-[color:var(--tw-surface-2)]'); ?>">
                        <div class="min-w-0">
                            <div class="font-semibold text-[13px] truncate <?php echo e($fg); ?>"><?php echo e($depot->name); ?></div>
                            <div class="text-[10px] <?php echo e($muted); ?> truncate">
                                <?php echo e($depot->city ?: 'No city set'); ?>

                            </div>
                        </div>

                        
                        <span class="text-[9px] px-2 py-0.5 rounded-full border font-semibold
                            <?php echo e($depot->is_active
                                ? 'bg-emerald-600 text-white border-emerald-500/50'
                                : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]'); ?>">
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
            <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4 text-xs <?php echo e($muted); ?>">
                No depots yet. Create one on the left.
            </div>
        <?php endif; ?>
    </div>
</div>


<div id="createDepotModal"
     class="fixed inset-0 bg-black/55 hidden flex items-end sm:items-center justify-center p-4 z-50"
     onclick="closeOnBg(event, 'createDepotModal')">

    <div class="w-full max-w-md rounded-2xl <?php echo e($surface); ?> border <?php echo e($border); ?> p-5 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold <?php echo e($fg); ?>">Create depot</h2>
            <button type="button"
                    class="<?php echo e($btnGhost); ?> h-9 w-9 text-lg leading-none"
                    onclick="closeModal('createDepotModal')">×</button>
        </div>

        <?php
            $label = "text-xs $muted";
            $input = "w-full mt-1 rounded-xl $bg border $border p-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
        ?>

        <form method="POST" action="<?php echo e(route('settings.depots.store')); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>

            <div>
                <label class="<?php echo e($label); ?>">Depot name</label>
                <input type="text" name="name" class="<?php echo e($input); ?>" required>
            </div>

            <div>
                <label class="<?php echo e($label); ?>">City</label>
                <input type="text" name="city" class="<?php echo e($input); ?>">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" value="1" checked
                       class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-600 focus:ring-emerald-500/40">
                <span class="text-xs <?php echo e($fg); ?>">Depot is active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('createDepotModal')"
                        class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]">
                    Cancel
                </button>

                <button class="<?php echo e($btnPrimary); ?> px-4 py-1.5 text-[11px] border border-emerald-500/50">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    function closeOnBg(event, id) {
        if (event.target && event.target.id === id) {
            closeModal(id);
        }
    }
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/depots/index.blade.php ENDPATH**/ ?>