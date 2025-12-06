<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['supplier']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['supplier']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php if(!$supplier): ?>
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-xs text-slate-400">
        No supplier selected yet.
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="space-y-4">

    
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide text-slate-500">Selected supplier</div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold truncate"><?php echo e($supplier->name); ?></h2>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                    <?php echo e($supplier->is_active ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/50'
                                             : 'bg-slate-800 text-slate-300 border border-slate-700'); ?>">
                    <?php echo e($supplier->is_active ? 'Active' : 'Inactive'); ?>

                </span>
            </div>
            <p class="text-[11px] text-slate-400">
                <?php echo e($supplier->type ?: 'Type not set'); ?>

                <?php if($supplier->city || $supplier->country): ?>
                    • <?php echo e($supplier->city); ?><?php echo e($supplier->city && $supplier->country ? ', ' : ''); ?><?php echo e($supplier->country); ?>

                <?php endif; ?>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button type="button"
                    onclick="openSupplierEditModal()"
                    class="px-3 py-1.5 rounded-xl border border-slate-700 bg-slate-900/70 text-[11px] hover:bg-slate-800">
                Edit
            </button>

            <button type="button"
                    onclick="openSupplierStatusModal()"
                    class="px-3 py-1.5 rounded-xl text-[11px]
                        <?php echo e($supplier->is_active
                            ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                            : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950'); ?>">
                <?php echo e($supplier->is_active ? 'Deactivate' : 'Activate'); ?>

            </button>
        </div>
    </div>

    
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Default currency
            </div>
            <div class="mt-1 text-sm font-semibold">
                <?php echo e($supplier->default_currency ?: '—'); ?>

            </div>
            <div class="text-[10px] text-slate-500">
                Used as default on purchases
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Contact
            </div>
            <div class="mt-1 text-[11px] text-slate-200">
                <?php echo e($supplier->contact_person ?: 'Not set'); ?>

            </div>
            <div class="text-[10px] text-slate-500">
                <?php echo e($supplier->phone ?: 'No phone'); ?>

            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Email
            </div>
            <div class="mt-1 text-[11px] text-slate-200 truncate">
                <?php echo e($supplier->email ?: 'No email'); ?>

            </div>
            <div class="text-[10px] text-slate-500">
                For POs & documents
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-3">
        <div class="text-[10px] uppercase tracking-wide text-slate-500 mb-1">
            Notes
        </div>
        <p class="text-[11px] text-slate-300 whitespace-pre-line">
            <?php echo e($supplier->notes ?: 'No special notes for this supplier yet.'); ?>

        </p>
    </div>
</div>


<div id="supplierEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40">
    <div class="w-full max-w-md rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold">Edit supplier</h3>
            <button type="button" class="text-slate-500 text-lg leading-none"
                    onclick="closeSupplierEditModal()">×</button>
        </div>

        <form method="post" action="<?php echo e(route('settings.suppliers.update', $supplier)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Name</label>
                <input type="text" name="name"
                       value="<?php echo e(old('name', $supplier->name)); ?>"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Type</label>
                    <select name="type"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                        <option value="" <?php if(!$supplier->type): echo 'selected'; endif; ?>>Not set</option>
                        <option value="port" <?php if($supplier->type === 'port'): echo 'selected'; endif; ?>>Port / terminal</option>
                        <option value="local_depot" <?php if($supplier->type === 'local_depot'): echo 'selected'; endif; ?>>Local depot</option>
                        <option value="trader" <?php if($supplier->type === 'trader'): echo 'selected'; endif; ?>>Trader</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Default currency</label>
                    <input type="text" name="default_currency"
                           value="<?php echo e(old('default_currency', $supplier->default_currency)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Country</label>
                    <input type="text" name="country"
                           value="<?php echo e(old('country', $supplier->country)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">City</label>
                    <input type="text" name="city"
                           value="<?php echo e(old('city', $supplier->city)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Contact person</label>
                <input type="text" name="contact_person"
                       value="<?php echo e(old('contact_person', $supplier->contact_person)); ?>"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Phone</label>
                    <input type="text" name="phone"
                           value="<?php echo e(old('phone', $supplier->phone)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Email</label>
                    <input type="email" name="email"
                           value="<?php echo e(old('email', $supplier->email)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500/60"
                       <?php if(old('is_active', $supplier->is_active)): echo 'checked'; endif; ?>>
                <label for="edit_is_active" class="text-[11px] text-slate-300">
                    Supplier is active
                </label>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40"><?php echo e(old('notes', $supplier->notes)); ?></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                        onclick="closeSupplierEditModal()">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>


<div id="supplierStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40">
    <div class="w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">
        <div class="mb-2">
            <h3 class="text-sm font-semibold mb-1">
                <?php echo e($supplier->is_active ? 'Deactivate supplier?' : 'Activate supplier?'); ?>

            </h3>
            <p class="text-[11px] text-slate-400">
                <?php echo e($supplier->is_active
                    ? 'Deactivated suppliers cannot be used for new purchases until re-activated.'
                    : 'Once activated, this supplier will be available when creating new purchases.'); ?>

            </p>
        </div>

        <form method="post" action="<?php echo e(route('settings.suppliers.toggle-active', $supplier)); ?>"
              class="flex justify-end gap-2 pt-2">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <button type="button"
                    class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                    onclick="closeSupplierStatusModal()">
                Cancel
            </button>

            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold
                        <?php echo e($supplier->is_active
                            ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                            : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950'); ?>">
                <?php echo e($supplier->is_active ? 'Deactivate' : 'Activate'); ?>

            </button>
        </form>
    </div>
</div>

<script>
    function openSupplierEditModal() {
        const m = document.getElementById('supplierEditModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeSupplierEditModal() {
        const m = document.getElementById('supplierEditModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function openSupplierStatusModal() {
        const m = document.getElementById('supplierStatusModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeSupplierStatusModal() {
        const m = document.getElementById('supplierStatusModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // click-outside close
    document.getElementById('supplierEditModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeSupplierEditModal();
        }
    });

    document.getElementById('supplierStatusModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeSupplierStatusModal();
        }
    });
</script><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/suppliers/_details.blade.php ENDPATH**/ ?>