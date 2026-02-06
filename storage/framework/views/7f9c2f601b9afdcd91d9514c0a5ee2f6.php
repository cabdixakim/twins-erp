<?php
    $suppliers = $suppliers ?? collect();
    $products  = $products ?? collect();
?>



<?php $__env->startSection('title', 'New purchase'); ?>
<?php $__env->startSection('subtitle', 'Create a draft purchase'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
    
    <div class="mb-5">
        <h1 class="text-[18px] sm:text-[20px] font-semibold tracking-tight text-slate-100">
            New purchase
        </h1>
        <!-- <p class="mt-1 text-[12px] text-slate-400 max-w-[65ch]">
            Save as draft first. Confirm later to create a batch, then proceed into the next workflow.
        </p> -->
    </div>

    
    <?php if($errors->any()): ?>
        <div class="mb-4 rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-3 text-[12px] text-rose-200">
            <div class="font-semibold">Please fix the highlighted fields.</div>
            <ul class="mt-2 list-disc pl-5 space-y-1 text-rose-200/90">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($e); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-[320px,1fr] gap-4 lg:gap-6">
        
 

        
        <main class="rounded-2xl bg-slate-950 ring-1 ring-slate-800 overflow-hidden">
            <form method="POST" action="<?php echo e(route('purchases.store')); ?>">
                <?php echo csrf_field(); ?>

                
                <div class="px-4 sm:px-6 py-4 border-b border-slate-800 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[13px] font-semibold text-slate-100">Purchase details</div>
                        <div class="mt-0.5 text-[11px] text-slate-400 max-w-[70ch]">
                            Choose import vs local, then fill the draft fields. Confirm later to create the batch.
                        </div>
                    </div>

                    <a href="<?php echo e(route('purchases.index')); ?>"
                       class="h-9 px-3 inline-flex items-center rounded-xl text-[12px] font-semibold
                              bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                        Back
                    </a>
                </div>

                <div class="px-4 sm:px-6 py-5 space-y-5">

                    
                    <div>
                        <div class="text-[12px] text-slate-400 mb-2">Purchase type</div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input id="type_import" type="radio" name="type" value="import" class="sr-only"
                                       <?php echo e(old('type','import') === 'import' ? 'checked' : ''); ?>>
                                <div class="rounded-2xl px-3 py-2 ring-1 ring-slate-800 bg-slate-900/40
                                            hover:bg-slate-900/60 transition"
                                     data-type-card="import">
                                    <div class="flex items-center justify-between">
                                        <div class="text-[13px] font-semibold text-slate-100">Import</div>
                                        <span class="text-[11px] text-slate-500">transport</span>
                                    </div>
                                    <div class="mt-0.5 text-[11px] text-slate-400">
                                        Transport → TR8 → Offload
                                    </div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input id="type_local" type="radio" name="type" value="local_depot" class="sr-only"
                                       <?php echo e(old('type') === 'local_depot' ? 'checked' : ''); ?>>
                                <div class="rounded-2xl px-3 py-2 ring-1 ring-slate-800 bg-slate-900/40
                                            hover:bg-slate-900/60 transition"
                                     data-type-card="local_depot">
                                    <div class="flex items-center justify-between">
                                        <div class="text-[13px] font-semibold text-slate-100">Local depot</div>
                                        <span class="text-[11px] text-slate-500">in-depot</span>
                                    </div>
                                    <div class="mt-0.5 text-[11px] text-slate-400">
                                        Ownership change only
                                    </div>
                                </div>
                            </label>
                        </div>

                        
                        <div class="mt-3 rounded-xl bg-slate-900/40 ring-1 ring-slate-800 px-3 py-2 text-[12px] text-slate-300">
                            <div data-mode="import">
                                This purchase will enter nominations and transport workflow.
                                <span class="text-slate-500">You’ll assign trucks and transporters after confirming.</span>
                            </div>
                            <div class="hidden" data-mode="local_depot">
                                This purchase is a local depot deal (ownership change).
                                <span class="text-slate-500">You’ll proceed to depot stock without nominations.</span>
                            </div>
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Product</label>
                            <select name="product_id" required
                                    class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                           focus:outline-none focus:ring-2 focus:ring-slate-700">
                                <option value="" disabled <?php echo e(old('product_id') ? '' : 'selected'); ?>>Select…</option>
                                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>" <?php echo e((string)old('product_id') === (string)$p->id ? 'selected' : ''); ?>>
                                        <?php echo e($p->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Supplier (optional)</label>
                            <select name="supplier_id"
                                    class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                           focus:outline-none focus:ring-2 focus:ring-slate-700">
                                <option value="" selected>—</option>
                                <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($s->id); ?>" <?php echo e((string)old('supplier_id') === (string)$s->id ? 'selected' : ''); ?>>
                                        <?php echo e($s->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        
                        <div class="lg:col-span-1">
                            <label class="block text-[11px] text-slate-400 mb-1">Quantity</label>
                            <input name="qty" required inputmode="decimal"
                                   value="<?php echo e(old('qty')); ?>"
                                   class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                          placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                                   placeholder="e.g. 800000.000">
                            <div class="mt-1 text-[11px] text-slate-500">Base unit (litres for fuel).</div>
                        </div>

                        
                        <div class="lg:col-span-1">
                            <div class="grid grid-cols-1 sm:grid-cols-[1fr,140px] gap-3">
                                <div>
                                    <label class="block text-[11px] text-slate-400 mb-1">Unit price</label>
                                    <input name="unit_price" required inputmode="decimal"
                                           value="<?php echo e(old('unit_price')); ?>"
                                           class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                                  placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                                           placeholder="e.g. 1.125000">
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-400 mb-1">Currency</label>
                                    <input name="currency" required
                                           value="<?php echo e(old('currency','USD')); ?>"
                                           class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                                  placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                                           placeholder="USD">
                                </div>
                            </div>
                        </div>

                        
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Purchase date (optional)</label>
                            <input type="date" name="purchase_date"
                                   value="<?php echo e(old('purchase_date')); ?>"
                                   class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                          focus:outline-none focus:ring-2 focus:ring-slate-700">
                        </div>

                        
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">Notes (optional)</label>
                            <input name="notes"
                                   value="<?php echo e(old('notes')); ?>"
                                   class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                          placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                                   placeholder="Reference / comment…">
                        </div>
                    </div>
                </div>

                
                <div class="px-4 sm:px-6 py-4 border-t border-slate-800 flex items-center justify-end gap-2">
                    <a href="<?php echo e(route('purchases.index')); ?>"
                       class="h-9 px-3 inline-flex items-center rounded-xl text-[12px] font-semibold
                              bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                        Cancel
                    </a>

                    <button type="submit"
                            class="h-9 px-3 rounded-xl text-[12px] font-semibold
                                   bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20 transition">
                        Save draft
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>


<script>
(function () {
    const importRadio = document.getElementById('type_import');
    const localRadio  = document.getElementById('type_local');

    const modeBlocks  = document.querySelectorAll('[data-mode]');
    const typeCards   = document.querySelectorAll('[data-type-card]');

    function applyMode(mode) {
        // show/hide text blocks
        modeBlocks.forEach(el => {
            const m = el.getAttribute('data-mode');
            if (!m) return;
            el.classList.toggle('hidden', m !== mode);
        });

        // highlight selected card (premium but subtle)
        typeCards.forEach(card => {
            const m = card.getAttribute('data-type-card');
            const active = (m === mode);
            card.classList.toggle('ring-emerald-500/30', active);
            card.classList.toggle('bg-emerald-500/10', active);
            card.classList.toggle('bg-slate-900/40', !active);
        });
    }

    function currentMode() {
        return (localRadio && localRadio.checked) ? 'local_depot' : 'import';
    }

    // init
    applyMode(currentMode());

    // listeners
    importRadio?.addEventListener('change', () => applyMode(currentMode()));
    localRadio?.addEventListener('change', () => applyMode(currentMode()));
})();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/create.blade.php ENDPATH**/ ?>