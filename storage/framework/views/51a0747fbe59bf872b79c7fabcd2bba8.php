<?php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $products */
    $products = $products ?? collect();

    $total = method_exists($products, 'total') ? $products->total() : $products->count();
?>



<?php $__env->startSection('title', 'Products'); ?>
<?php $__env->startSection('subtitle', 'Company-scoped products (AGO, PMS, etc)'); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full">
    <div class="mx-auto max-w-[980px] px-1 sm:px-0">

        
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight text-slate-100">
                    Products
                </h1>
                <p class="mt-1 text-[12px] text-slate-400">
                    Manage products for the active company. Used by purchases, batches, and inventory.
                </p>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                <div class="hidden sm:flex items-center text-[11px] text-slate-400 rounded-xl px-2.5 py-1 ring-1 ring-slate-800 bg-slate-900">
                    <span class="text-slate-200 font-semibold"><?php echo e($total); ?></span>
                    <span class="ml-1">total</span>
                </div>

                <button type="button"
                        id="btnOpenCreateProduct"
                        class="h-9 px-3 rounded-xl text-[12px] font-semibold
                               bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                    New product
                </button>
            </div>
        </div>

        
        <?php if(session('status')): ?>
            <div class="mb-4 rounded-2xl bg-emerald-500/10 ring-1 ring-emerald-500/20 px-4 py-3 text-[12px] text-emerald-200">
                <?php echo e(session('status')); ?>

            </div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="mb-4 rounded-2xl bg-rose-500/10 ring-1 ring-rose-500/20 px-4 py-3 text-[12px] text-rose-200">
                <div class="font-semibold">Fix the following:</div>
                <ul class="mt-2 list-disc pl-5 space-y-1 text-rose-200/90">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($e); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        
        <div class="mb-3">
            <div class="relative max-w-[520px]">
                <div class="absolute inset-y-0 left-3 grid place-items-center text-slate-500">
                    <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                    </svg>
                </div>

                <input id="twProductSearch"
                       class="w-full h-10 pl-9 pr-3 rounded-xl bg-slate-900 ring-1 ring-slate-800
                              text-[13px] placeholder:text-slate-500
                              focus:outline-none focus:ring-2 focus:ring-slate-700"
                       placeholder="Search products…"
                       autocomplete="off">
            </div>
        </div>

        
        <div class="rounded-2xl bg-slate-950 ring-1 ring-slate-800 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                <div class="text-[11px] uppercase tracking-wide text-slate-500">List</div>
                <div class="text-[11px] text-slate-500"><?php echo e($total); ?> total</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="bg-slate-950">
                        <tr class="text-[11px] uppercase tracking-wide text-slate-500 border-b border-slate-800">
                            <th class="px-4 py-2.5 font-semibold">Name</th>
                            <th class="px-4 py-2.5 font-semibold">Code</th>
                            <th class="px-4 py-2.5 font-semibold">UOM</th>
                            <th class="px-4 py-2.5 font-semibold">Status</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="twProductList" class="divide-y divide-slate-800">
                        <?php $__empty_1 = true; $__currentLoopData = ($products?->items() ?? $products); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $isActive = (bool)($p->is_active ?? false);
                            ?>

                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="h-2 w-2 rounded-full <?php echo e($isActive ? 'bg-emerald-400' : 'bg-slate-700'); ?> shrink-0"></span>
                                        <div class="tw-product-name text-[13px] font-semibold text-slate-100 truncate">
                                            <?php echo e($p->name); ?>

                                        </div>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-1">
                                        Company-scoped
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-[12px] text-slate-200">
                                    <?php echo e($p->code ?: '—'); ?>

                                </td>

                                <td class="px-4 py-3 text-[12px] text-slate-200">
                                    <?php echo e($p->base_uom ?: 'L'); ?>

                                </td>

                                <td class="px-4 py-3">
                                    <?php if($isActive): ?>
                                        <span class="inline-flex items-center text-[11px] text-emerald-300 bg-emerald-500/10 ring-1 ring-emerald-500/20 px-2 py-0.5 rounded-lg">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center text-[11px] text-slate-300 bg-slate-800/60 ring-1 ring-slate-700/60 px-2 py-0.5 rounded-lg">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                class="btnEdit h-9 px-3 rounded-xl text-[12px] font-semibold
                                                       bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition"
                                                data-edit="edit-<?php echo e($p->id); ?>">
                                            Edit
                                        </button>

                                        <form method="POST" action="<?php echo e(route('products.toggle-active', $p)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <button type="submit"
                                                    class="h-9 px-3 rounded-xl text-[12px] font-semibold
                                                           <?php echo e($isActive
                                                                ? 'bg-rose-500/10 text-rose-200 ring-1 ring-rose-500/20 hover:bg-rose-500/15'
                                                                : 'bg-emerald-500/10 text-emerald-200 ring-1 ring-emerald-500/20 hover:bg-emerald-500/15'); ?> transition">
                                                <?php echo e($isActive ? 'Disable' : 'Enable'); ?>

                                            </button>
                                        </form>
                                    </div>

                                    
                                    <div id="edit-<?php echo e($p->id); ?>" class="hidden mt-3 rounded-2xl bg-slate-900/40 ring-1 ring-slate-800 p-3">
                                        <form method="POST" action="<?php echo e(route('products.update', $p)); ?>" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>

                                            <div class="sm:col-span-1">
                                                <label class="block text-[11px] text-slate-400 mb-1">Name</label>
                                                <input name="name" required value="<?php echo e($p->name); ?>"
                                                       class="w-full h-9 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                                              focus:outline-none focus:ring-2 focus:ring-slate-700">
                                            </div>

                                            <div class="sm:col-span-1">
                                                <label class="block text-[11px] text-slate-400 mb-1">Code (optional)</label>
                                                <input name="code" value="<?php echo e($p->code); ?>"
                                                       class="w-full h-9 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                                              placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                                                       placeholder="AGO / PMS">
                                            </div>

                                            <div class="sm:col-span-1">
                                                <label class="block text-[11px] text-slate-400 mb-1">Base UOM</label>
                                                <input name="base_uom" value="<?php echo e($p->base_uom ?? 'L'); ?>"
                                                       class="w-full h-9 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                                              placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                                                       placeholder="L">
                                            </div>

                                            <div class="sm:col-span-3 flex items-center justify-end gap-2 pt-1">
                                                <button type="button"
                                                        class="btnCancelEdit h-9 px-3 rounded-xl text-[12px] font-semibold
                                                               bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition"
                                                        data-edit="edit-<?php echo e($p->id); ?>">
                                                    Cancel
                                                </button>

                                                <button type="submit"
                                                        class="h-9 px-3 rounded-xl text-[12px] font-semibold
                                                               bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20 transition">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6">
                                    <div class="text-[13px] font-semibold text-slate-200">No products yet</div>
                                    <div class="text-[12px] text-slate-400 mt-1">
                                        Create AGO, PMS, Jet A-1, etc for this company.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if(method_exists($products, 'links')): ?>
                <div class="px-4 py-3 border-t border-slate-800">
                    <?php echo e($products->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<div id="twCreateProductOverlay" class="hidden fixed inset-0 z-[80] bg-black/55"></div>


<div id="twCreateProductModal"
     class="hidden fixed inset-0 z-[90] p-4 sm:p-6
            items-end sm:items-center justify-center">
    <div class="max-w-[560px] rounded-2xl overflow-hidden
                bg-slate-950 ring-1 ring-slate-800 shadow-[0_30px_90px_rgba(0,0,0,.70)]">

        <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold text-slate-100">Create product</div>
                <div class="text-[11px] text-slate-400">Company scoped</div>
            </div>

            <button type="button"
                    id="btnCloseCreateProduct"
                    class="h-9 w-9 grid place-items-center rounded-xl bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition"
                    aria-label="Close">
                <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="<?php echo e(route('products.store')); ?>" class="p-4 space-y-3">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Name</label>
                <input name="name" required
                       class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                              placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                       placeholder="e.g. AGO">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Code (optional)</label>
                    <input name="code"
                           class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                  placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                           placeholder="AGO / PMS">
                </div>

                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Base UOM</label>
                    <input name="base_uom" value="L"
                           class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                  placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                           placeholder="L">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button"
                        id="btnCancelCreateProduct"
                        class="h-9 px-3 rounded-xl text-[12px] font-semibold
                               bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                    Cancel
                </button>

                <button type="submit"
                        class="h-9 px-3 rounded-xl text-[12px] font-semibold
                               bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20 transition">
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
        modal.classList.add('flex'); // ✅ required because modal is a flex wrapper now
        document.body.classList.add('overflow-hidden');
        setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
    }

    function close(){
        if (!overlay || !modal) return;
        overlay.classList.add('hidden');
        modal.classList.add('hidden');
        modal.classList.remove('flex'); // ✅ cleanup
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