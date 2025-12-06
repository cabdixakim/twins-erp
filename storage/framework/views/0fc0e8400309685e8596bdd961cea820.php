<?php
    $title = 'Depots';
    $subtitle = 'Configure where your AGO is stored and how storage fees & shrinkage rules are applied.';
?>


<?php
  $currentDepot = $currentDepot ?? null;
?>

<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startSection('content'); ?>
    
    <?php if(session('status')): ?>
        <div class="mb-4 rounded-xl bg-emerald-900/40 border border-emerald-500/60 px-3 py-2 text-xs text-emerald-100">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="grid gap-5 md:grid-cols-[260px,1fr]">
        
        <aside class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Depots
                </h2>

                
                <button
                    type="button"
                    onclick="openDepotModal('create')"
                    class="inline-flex items-center gap-1 rounded-lg bg-slate-800 px-2.5 py-1 text-[11px] font-semibold text-slate-100 hover:bg-slate-700"
                >
                    <span class="text-xs">＋</span>
                    <span>New</span>
                </button>
            </div>

            
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70">
                <?php if($depots->where('is_active', true)->isEmpty()): ?>
                    <div class="px-3 py-4 text-[11px] text-slate-400">
                        No active depots yet. Tap <span class="font-semibold">New</span> to add one.
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-slate-800/80 text-xs">
                        <?php $__currentLoopData = $depots->where('is_active', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $depot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li>
                                <a
                                    href="<?php echo e(route('settings.depots.index', ['depot' => $depot->id])); ?>"
                                    class="flex items-center justify-between px-3 py-2.5 transition
                                        <?php echo e($currentDepot && $currentDepot->id === $depot->id
                                            ? 'bg-slate-800 text-slate-50'
                                            : 'hover:bg-slate-900 text-slate-200'); ?>"
                                >
                                    <div>
                                        <div class="text-[13px] font-semibold">
                                            <?php echo e($depot->name); ?>

                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            <?php echo e($depot->city ?: 'No city set'); ?>

                                        </div>
                                    </div>

                                    <div class="flex flex-col items-end gap-1">
                                        <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[9px] font-semibold text-emerald-300">
                                            ACTIVE
                                        </span>
                                        <?php if($depot->storage_fee_per_1000_l): ?>
                                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-[9px] text-slate-300">
                                                <?php echo e(number_format($depot->storage_fee_per_1000_l, 2)); ?> / 1,000L
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>
            </div>

            
            <?php if($depots->where('is_active', false)->isNotEmpty()): ?>
                <div class="mt-4">
                    <div class="mb-1 text-[10px] uppercase tracking-wide text-slate-500">
                        Inactive depots
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70">
                        <ul class="divide-y divide-slate-800/80 text-xs">
                            <?php $__currentLoopData = $depots->where('is_active', false); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $depot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <a
                                        href="<?php echo e(route('settings.depots.index', ['depot' => $depot->id])); ?>"
                                        class="flex items-center justify-between px-3 py-2.5 transition hover:bg-slate-900/80 text-slate-400"
                                    >
                                        <div>
                                            <div class="text-[13px] font-semibold">
                                                <?php echo e($depot->name); ?>

                                            </div>
                                            <div class="text-[10px] text-slate-500">
                                                <?php echo e($depot->city ?: 'No city set'); ?>

                                            </div>
                                        </div>

                                        <span class="rounded-full bg-slate-800 px-2 py-0.5 text-[9px] font-semibold text-slate-400">
                                            INACTIVE
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        
        <section class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 md:p-5 space-y-4">
            <?php if(! $currentDepot): ?>
                <div class="py-10 text-center text-sm text-slate-400">
                    Select a depot on the left to see its details.
                </div>
            <?php else: ?>
                
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold">
                                <?php echo e($currentDepot->name); ?>

                            </h2>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold
                                <?php echo e($currentDepot->is_active
                                    ? 'bg-emerald-500/15 text-emerald-300'
                                    : 'bg-slate-800 text-slate-300'); ?>">
                                <?php echo e($currentDepot->is_active ? 'ACTIVE' : 'INACTIVE'); ?>

                            </span>
                        </div>
                        <div class="text-[11px] text-slate-400">
                            <?php echo e($currentDepot->city ?: 'City/location not set'); ?>

                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Default storage contract and shrinkage policy for this depot.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        
                        <button
                            type="button"
                            onclick="openDepotModal('edit')"
                            class="inline-flex items-center gap-1 rounded-xl border border-slate-700 bg-slate-900 px-3 py-1.5 text-[11px] font-semibold text-slate-100 hover:bg-slate-800"
                        >
                            ✏️ Edit details
                        </button>

                        
                        <form id="toggle-depot-<?php echo e($currentDepot->id); ?>"
                              method="POST"
                              action="<?php echo e(route('settings.depots.destroy', $currentDepot)); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button
                                type="button"
                                onclick="confirmToggleDepot(<?php echo e($currentDepot->id); ?>, <?php echo e($currentDepot->is_active ? 'true' : 'false'); ?>)"
                                class="inline-flex items-center gap-1 rounded-xl px-3 py-1.5 text-[11px] font-semibold
                                    <?php echo e($currentDepot->is_active
                                        ? 'bg-rose-600/80 hover:bg-rose-600 text-slate-50'
                                        : 'bg-emerald-600/80 hover:bg-emerald-500 text-slate-950'); ?>"
                            >
                                <?php echo e($currentDepot->is_active ? 'Deactivate depot' : 'Reactivate depot'); ?>

                            </button>
                        </form>
                    </div>
                </div>

                
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 space-y-1">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            Storage fee
                        </div>
                        <div class="text-sm text-slate-100">
                            <?php if($currentDepot->storage_fee_per_1000_l): ?>
                                <?php echo e(number_format($currentDepot->storage_fee_per_1000_l, 2)); ?> / 1,000L
                            <?php else: ?>
                                <span class="text-slate-500 text-[12px]">Not defined yet</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Used when calculating depot storage charge per 1,000 litres.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3 space-y-1">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            Shrinkage allowance
                        </div>
                        <div class="text-sm text-slate-100">
                            <?php if($currentDepot->default_shrinkage_pct_30d): ?>
                                <?php echo e(number_format($currentDepot->default_shrinkage_pct_30d, 3)); ?> % per 30 days
                            <?php else: ?>
                                <span class="text-slate-500 text-[12px]">Not defined yet</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-[11px] text-slate-500">
                            Used to calculate allowed stock loss over time for this depot.
                        </p>
                    </div>
                </div>

                
                <div class="rounded-xl border border-dashed border-slate-700 bg-slate-950/40 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 mb-1">
                        Operational snapshot
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Once the depot stock engine is wired, this panel will show live litres on hand,
                        last offload, last sale and quick links into the depot stock dashboard.
                    </p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    
    <div
        id="depotModal"
        class="fixed inset-0 z-40 hidden items-center justify-center bg-black/60 px-4"
    >
        <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-4 shadow-xl">
            <div class="mb-3 flex items-center justify-between">
                <h2 id="depotModalTitle" class="text-sm font-semibold text-slate-100">
                    New depot
                </h2>
                <button
                    type="button"
                    class="text-slate-400 hover:text-slate-200"
                    onclick="closeDepotModal()"
                >
                    ✖
                </button>
            </div>

            <form id="depotModalForm" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="_method" id="depotModalMethod" value="POST">

                <div class="space-y-3 text-sm">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-300">
                            Name
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="depotNameInput"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none"
                            required
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-300">
                            City / location
                        </label>
                        <input
                            type="text"
                            name="city"
                            id="depotCityInput"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none"
                        >
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold text-slate-300">
                                Storage fee per 1,000L
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                name="storage_fee_per_1000_l"
                                id="depotStorageFeeInput"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-semibold text-slate-300">
                                Shrinkage % per 30 days
                            </label>
                            <input
                                type="number"
                                step="0.001"
                                name="default_shrinkage_pct_30d"
                                id="depotShrinkageInput"
                                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-slate-100 focus:border-emerald-500 focus:outline-none"
                            >
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-200 hover:bg-slate-800"
                        onclick="closeDepotModal()"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-emerald-500 px-4 py-1.5 text-xs font-semibold text-slate-950 hover:bg-emerald-400"
                    >
                        Save depot
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const depotModal      = document.getElementById('depotModal');
        const depotModalTitle = document.getElementById('depotModalTitle');
        const depotModalForm  = document.getElementById('depotModalForm');
        const depotModalMethod = document.getElementById('depotModalMethod');

        const depotNameInput       = document.getElementById('depotNameInput');
        const depotCityInput       = document.getElementById('depotCityInput');
        const depotStorageFeeInput = document.getElementById('depotStorageFeeInput');
        const depotShrinkageInput  = document.getElementById('depotShrinkageInput');

        function openDepotModal(mode) {
            <?php if($currentDepot): ?>
                const currentDepot = <?php echo json_encode([
                    'id' => $currentDepot->id,
                    'name' => $currentDepot->name,
                    'city' => $currentDepot->city,
                    'storage_fee_per_1000_l' => $currentDepot->storage_fee_per_1000_l,
                    'default_shrinkage_pct_30d' => $currentDepot->default_shrinkage_pct_30d,
                ]); ?>;
            <?php else: ?>
                const currentDepot = null;
            <?php endif; ?>

            if (mode === 'edit' && currentDepot) {
                depotModalTitle.textContent = 'Edit depot';
                depotModalForm.action = "<?php echo e($currentDepot ? route('settings.depots.update', $currentDepot) : ''); ?>";
                depotModalMethod.value = 'PATCH';

                depotNameInput.value       = currentDepot.name || '';
                depotCityInput.value       = currentDepot.city || '';
                depotStorageFeeInput.value = currentDepot.storage_fee_per_1000_l || '';
                depotShrinkageInput.value  = currentDepot.default_shrinkage_pct_30d || '';
            } else {
                depotModalTitle.textContent = 'New depot';
                depotModalForm.action = "<?php echo e(route('settings.depots.store')); ?>";
                depotModalMethod.value = 'POST';

                depotNameInput.value       = '';
                depotCityInput.value       = '';
                depotStorageFeeInput.value = '';
                depotShrinkageInput.value  = '';
            }

            depotModal.classList.remove('hidden');
            depotModal.classList.add('flex');
        }

        function closeDepotModal() {
            depotModal.classList.add('hidden');
            depotModal.classList.remove('flex');
        }

        // Close modal when clicking outside the panel
        depotModal?.addEventListener('click', function (e) {
            if (e.target === depotModal) {
                closeDepotModal();
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDepotModal();
            }
        });

        // Deactivate / Reactivate confirmation
        function confirmToggleDepot(id, isActive) {
            const message = isActive
                ? 'Deactivate this depot? It will be removed from future offloads and sales, but history will remain.'
                : 'Reactivate this depot and allow it for new movements?';

            if (confirm(message)) {
                const form = document.getElementById('toggle-depot-' + id);
                if (form) form.submit();
            }
        }
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/depots/index.blade.php ENDPATH**/ ?>