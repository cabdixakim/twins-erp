<?php
    $title    = 'Suppliers';
    $subtitle = 'Configure who you buy AGO from – ports, local depots and other sources.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
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
            <h2 class="text-sm font-semibold <?php echo e($fg); ?>">Suppliers</h2>

            <button type="button"
                    onclick="openSupplierCreateModal()"
                    class="<?php echo e($btnPrimary); ?> px-3 py-1.5 text-xs">
                + New supplier
            </button>
        </div>

        <ul class="space-y-1 text-xs">
            <?php $__empty_1 = true; $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <li>
                    <a href="<?php echo e(route('settings.suppliers.index', ['supplier' => $supplier->id])); ?>"
                       class="flex items-center justify-between px-3 py-2 rounded-xl border transition
                              <?php echo e($currentSupplier && $currentSupplier->id === $supplier->id
                                    ? 'border-emerald-500/45 bg-[color:var(--tw-surface-2)] shadow-sm'
                                    : 'border-[color:var(--tw-border)] hover:bg-[color:var(--tw-surface-2)]'); ?>">
                        <div class="min-w-0">
                            <div class="font-semibold text-[13px] truncate <?php echo e($fg); ?>">
                                <?php echo e($supplier->name); ?>

                            </div>
                            <div class="text-[10px] <?php echo e($muted); ?> truncate">
                                <?php echo e($supplier->type ?: 'Unclassified'); ?>

                                <?php if($supplier->city || $supplier->country): ?>
                                    • <?php echo e($supplier->city); ?><?php echo e($supplier->city && $supplier->country ? ', ' : ''); ?><?php echo e($supplier->country); ?>

                                <?php endif; ?>
                            </div>
                        </div>

                        
                        <span class="text-[9px] px-2 py-0.5 rounded-full border font-semibold
                            <?php echo e($supplier->is_active
                                ? 'bg-emerald-600 text-white border-emerald-500/50'
                                : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]'); ?>">
                            <?php echo e($supplier->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="text-[11px] <?php echo e($muted); ?> px-1 py-2">
                    No suppliers yet. Create your first one to start tracking purchases.
                </li>
            <?php endif; ?>
        </ul>
    </div>

    
    <div class="md:col-span-2">
        <?php echo $__env->make('settings.suppliers._details', ['supplier' => $currentSupplier], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>


<div id="supplierCreateModal"
     class="fixed inset-0 bg-black/55 hidden items-end sm:items-center justify-center p-4 z-50">
    <div class="w-full max-w-md rounded-2xl <?php echo e($surface); ?> border <?php echo e($border); ?> p-4  max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-semibold <?php echo e($fg); ?>">New supplier</h2>
            <button type="button"
                    class="<?php echo e($btnGhost); ?> h-9 w-9 text-lg leading-none"
                    onclick="closeSupplierCreateModal()">×</button>
        </div>

        <?php
            $label = "block text-[11px] $muted mb-1";
            $input = "w-full rounded-xl border $border bg-[color:var(--tw-bg)] px-3 py-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
        ?>

        <form method="post" action="<?php echo e(route('settings.suppliers.store')); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>

            <div>
                <label class="<?php echo e($label); ?>">Name</label>
                <input type="text" name="name" class="<?php echo e($input); ?>" required>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Type</label>
                    <select name="type" class="<?php echo e($input); ?>">
                        <option value="">Not set</option>
                        <option value="port">Port / terminal</option>
                        <option value="local_depot">Local depot</option>
                        <option value="trader">Trader</option>
                    </select>
                </div>
                <div>
                    <label class="<?php echo e($label); ?>">Default currency</label>
                    <input type="text" name="default_currency" value="USD" class="<?php echo e($input); ?>">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Country</label>
                    <input type="text" name="country" class="<?php echo e($input); ?>">
                </div>
                <div>
                    <label class="<?php echo e($label); ?>">City</label>
                    <input type="text" name="city" class="<?php echo e($input); ?>">
                </div>
            </div>

            <div>
                <label class="<?php echo e($label); ?>">Contact person</label>
                <input type="text" name="contact_person" class="<?php echo e($input); ?>">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Phone</label>
                    <input type="text" name="phone" class="<?php echo e($input); ?>">
                </div>
                <div>
                    <label class="<?php echo e($label); ?>">Email</label>
                    <input type="email" name="email" class="<?php echo e($input); ?>">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="create_is_active" name="is_active" value="1" checked
                       class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-600 focus:ring-emerald-500/40">
                <label for="create_is_active" class="text-[11px] <?php echo e($fg); ?>">
                    Supplier is active
                </label>
            </div>

            <div>
                <label class="<?php echo e($label); ?>">Notes</label>
                <textarea name="notes" rows="2" class="<?php echo e($input); ?>"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]"
                        onclick="closeSupplierCreateModal()">
                    Cancel
                </button>

                
                <button type="submit"
                        class="<?php echo e($btnPrimary); ?> px-4 py-1.5 text-[11px] border border-emerald-500/50">
                    Save supplier
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSupplierCreateModal() {
        const m = document.getElementById('supplierCreateModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeSupplierCreateModal() {
        const m = document.getElementById('supplierCreateModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // close on background click
    document.getElementById('supplierCreateModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeSupplierCreateModal();
        }
    });
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/suppliers/index.blade.php ENDPATH**/ ?>