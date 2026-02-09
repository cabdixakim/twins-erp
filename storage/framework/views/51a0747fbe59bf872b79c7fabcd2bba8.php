<?php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $products */
    $products = $products ?? collect();
    $total = method_exists($products, 'total') ? $products->total() : $products->count();

    // Theme tokens (same pattern as the other premium pages)
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    // Buttons (no dim pills; emerald buttons = text-white)
    $btnGhost   = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition font-semibold";
    $btnPrimary = "inline-flex items-center justify-center rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
    $btnDanger  = "inline-flex items-center justify-center rounded-xl border border-rose-500/50 bg-rose-600 text-white hover:bg-rose-500 transition font-semibold";

    $label = "block text-[11px] $muted mb-1";
    $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg placeholder:opacity-70 focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
?>



<?php $__env->startSection('title', 'Products'); ?>
<?php $__env->startSection('subtitle', 'Company-scoped products (AGO, PMS, etc)'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full">
    <div class="mx-auto max-w-[980px] px-1 sm:px-0">

        
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight <?php echo e($fg); ?>">
                    Products
                </h1>
                <p class="mt-1 text-[12px] <?php echo e($muted); ?>">
                    Manage products for the active company. Used by purchases, batches, and inventory.
                </p>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                <div class="hidden sm:flex items-center text-[11px] <?php echo e($muted); ?> rounded-xl px-2.5 py-1 border <?php echo e($border); ?> <?php echo e($surface); ?>">
                    <span class="<?php echo e($fg); ?> font-semibold"><?php echo e($total); ?></span>
                    <span class="ml-1">total</span>
                </div>

                <button type="button"
                        id="btnOpenCreateProduct"
                        class="<?php echo e($btnPrimary); ?> h-9 px-3 text-[12px] cursor-pointer">
                    New product
                </button>
            </div>
        </div>

        
        <?php if(session('status')): ?>
            <div class="mb-4 rounded-2xl border border-emerald-500/35 bg-emerald-600 text-white px-4 py-3 text-[12px] font-semibold">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="mb-4 rounded-2xl border border-rose-500/35 bg-rose-600 text-white px-4 py-3 text-[12px]">
                <div class="font-semibold">Fix the following:</div>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($e); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        
        <div class="mb-3">
            <div class="relative max-w-[520px]">
                <div class="absolute inset-y-0 left-3 grid place-items-center <?php echo e($muted); ?>">
                    <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                    </svg>
                </div>

                <input id="twProductSearch"
                       class="w-full h-10 pl-9 pr-3 rounded-xl border <?php echo e($border); ?> <?php echo e($bg); ?>

                              text-[13px] <?php echo e($fg); ?> placeholder:opacity-70
                              focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                       placeholder="Search products…"
                       autocomplete="off">
            </div>
        </div>

        
        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> overflow-hidden">
            <div class="px-4 py-3 border-b <?php echo e($border); ?> flex items-center justify-between">
                <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">List</div>
                <div class="text-[11px] <?php echo e($muted); ?>"><?php echo e($total); ?> total</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="<?php echo e($surface); ?>">
                        <tr class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?> border-b <?php echo e($border); ?>">
                            <th class="px-4 py-2.5 font-semibold">Name</th>
                            <th class="px-4 py-2.5 font-semibold">Code</th>
                            <th class="px-4 py-2.5 font-semibold">UOM</th>
                            <th class="px-4 py-2.5 font-semibold">Status</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="twProductList" class="divide-y divide-[color:var(--tw-border)]">
                        <?php $__empty_1 = true; $__currentLoopData = ($products?->items() ?? $products); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $isActive = (bool)($p->is_active ?? false);
                            ?>

                            
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="h-2 w-2 rounded-full <?php echo e($isActive ? 'bg-emerald-400' : 'bg-[color:var(--tw-border)]'); ?> shrink-0"></span>
                                        <div class="tw-product-name text-[13px] font-semibold <?php echo e($fg); ?> truncate">
                                            <?php echo e($p->name); ?>

                                        </div>
                                    </div>
                                    <div class="text-[11px] <?php echo e($muted); ?> mt-1">
                                        Company-scoped
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-[12px] <?php echo e($fg); ?>">
                                    <?php echo e($p->code ?: '—'); ?>

                                </td>

                                <td class="px-4 py-3 text-[12px] <?php echo e($fg); ?>">
                                    <?php echo e($p->base_uom ?: 'L'); ?>

                                </td>

                                <td class="px-4 py-3">
                                    <?php if($isActive): ?>
                                        <span class="inline-flex items-center text-[11px] font-semibold text-white bg-emerald-600 border border-emerald-500/50 px-2 py-0.5 rounded-lg">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center text-[11px] font-semibold <?php echo e($fg); ?> border <?php echo e($border); ?> <?php echo e($surface2); ?> px-2 py-0.5 rounded-lg">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                        <button type="button"
                                                class="btnEdit <?php echo e($btnGhost); ?> h-9 px-3 text-[12px]"
                                                data-edit="edit-<?php echo e($p->id); ?>">
                                            Edit
                                        </button>

                                        <form method="POST" action="<?php echo e(route('products.toggle-active', $p)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>

                                            <?php if($isActive): ?>
                                                <button type="submit" class="<?php echo e($btnDanger); ?> h-9 px-3 text-[12px]">
                                                    Disable
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="<?php echo e($btnPrimary); ?> h-9 px-3 text-[12px]">
                                                    Enable
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            
                            <tr id="edit-<?php echo e($p->id); ?>" class="hidden">
                                <td colspan="5" class="px-4 pb-4">
                             <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?>

                                        overflow-hidden
                                        p-3 sm:p-0
                                        sm:rounded-2xl">
                                        
                                        <div class="px-4 py-3 border-b <?php echo e($border); ?> flex items-center justify-between">
                                            <div class="min-w-0">
                                                <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Edit product</div>
                                                <div class="text-[13px] font-semibold <?php echo e($fg); ?> truncate"><?php echo e($p->name); ?></div>
                                            </div>

                                            <button type="button"
                                                    class="btnCancelEdit <?php echo e($btnGhost); ?> h-9 w-9 text-lg leading-none"
                                                    data-edit="edit-<?php echo e($p->id); ?>">
                                                ×
                                            </button>
                                        </div>

                                        <form method="POST" action="<?php echo e(route('products.update', $p)); ?>"
                                            class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>

                                            <div class="sm:col-span-1">
                                                <label class="<?php echo e($label); ?>">Name</label>
                                                <input name="name" required value="<?php echo e($p->name); ?>" class="h-9 <?php echo e($input); ?>">
                                            </div>

                                            <div class="sm:col-span-1">
                                                <label class="<?php echo e($label); ?>">Code (optional)</label>
                                                <input name="code" value="<?php echo e($p->code); ?>" class="h-9 <?php echo e($input); ?>" placeholder="AGO / PMS">
                                            </div>

                                            <div class="sm:col-span-1">
                                                <label class="<?php echo e($label); ?>">Base UOM</label>
                                                <input name="base_uom" value="<?php echo e($p->base_uom ?? 'L'); ?>" class="h-9 <?php echo e($input); ?>" placeholder="L">
                                            </div>

                                            <div class="sm:col-span-3 flex items-center justify-end gap-2 pt-1">
                                                <button type="button"
                                                        class="btnCancelEdit <?php echo e($btnGhost); ?> h-9 px-3 text-[12px]"
                                                        data-edit="edit-<?php echo e($p->id); ?>">
                                                    Cancel
                                                </button>

                                                <button type="submit"
                                                        class="<?php echo e($btnPrimary); ?> h-9 px-3 text-[12px]">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-center <?php echo e($muted); ?>">
                                    No products found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if(method_exists($products, 'links')): ?>
                <div class="px-4 py-3 border-t <?php echo e($border); ?>">
                    <?php echo e($products->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<div id="twCreateProductOverlay" class="hidden fixed inset-0 z-[80] bg-black/55"></div>


<div id="twCreateProductModal"
     class="hidden fixed inset-0 z-[90] p-4 sm:p-6
            flex items-end sm:items-center justify-center">
    <div class="max-w-[560px] w-full rounded-2xl overflow-hidden
                border <?php echo e($border); ?> <?php echo e($surface); ?>

                shadow-[0_30px_90px_rgba(0,0,0,.70)]">

        <div class="px-4 py-3 border-b <?php echo e($border); ?> flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold <?php echo e($fg); ?>">Create product</div>
                <div class="text-[11px] <?php echo e($muted); ?>">Company scoped</div>
            </div>

            <button type="button"
                    id="btnCloseCreateProduct"
                    class="<?php echo e($btnGhost); ?> h-9 w-9"
                    aria-label="Close">
                <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?php echo e(route('products.store')); ?>" class="p-4 space-y-3">
            <?php echo csrf_field(); ?>

            <div>
                <label class="<?php echo e($label); ?>">Name</label>
                <input name="name" required
                       class="h-10 <?php echo e($input); ?>"
                       placeholder="e.g. AGO">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Code (optional)</label>
                    <input name="code"
                           class="h-10 <?php echo e($input); ?>"
                           placeholder="AGO / PMS">
                </div>

                <div>
                    <label class="<?php echo e($label); ?>">Base UOM</label>
                    <input name="base_uom" value="L"
                           class="h-10 <?php echo e($input); ?>"
                           placeholder="L">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button"
                        id="btnCancelCreateProduct"
                        class="<?php echo e($btnGhost); ?> h-9 px-3 text-[12px]">
                    Cancel
                </button>

                <button type="submit"
                        class="<?php echo e($btnPrimary); ?> h-9 px-3 text-[12px] cursor-pointer">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    // Search filter
    const input = document.getElementById('twProductSearch');
    const list  = document.getElementById('twProductList');

    if (input && list) {
        input.addEventListener('input', () => {
            const q = (input.value || '').toLowerCase().trim();
            list.querySelectorAll('.tw-product-name').forEach(nameEl => {
                const row = nameEl.closest('tr');
                const txt = (nameEl.textContent || '').toLowerCase();
                row.style.display = (!q || txt.includes(q)) ? '' : 'none';
            });
        });
    }

    // Inline edit toggles
    function toggleEdit(id, open){
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('hidden', !open);
    }

    document.querySelectorAll('.btnEdit').forEach(btn => {
        btn.addEventListener('click', () => toggleEdit(btn.dataset.edit, true));
    });
    document.querySelectorAll('.btnCancelEdit').forEach(btn => {
        btn.addEventListener('click', () => toggleEdit(btn.dataset.edit, false));
    });

    // Create modal (KEEP SAME IDs)
    const openBtn = document.getElementById('btnOpenCreateProduct');
    const overlay = document.getElementById('twCreateProductOverlay');
    const modal   = document.getElementById('twCreateProductModal');
    const closeBtn = document.getElementById('btnCloseCreateProduct');
    const cancelBtn = document.getElementById('btnCancelCreateProduct');

 function open(){
    if (!overlay || !modal) return;
    overlay.classList.remove('hidden');
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
}

function close(){
    if (!overlay || !modal) return;
    overlay.classList.add('hidden');
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

    openBtn?.addEventListener('click', open);
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
})();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/products/index.blade.php ENDPATH**/ ?>