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

<?php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
?>

<?php if(!$supplier): ?>
    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4 text-xs <?php echo e($muted); ?>">
        No supplier selected yet.
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="space-y-4">

    
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide <?php echo e($muted); ?>">Selected supplier</div>

            <div class="flex items-center gap-2 min-w-0">
                <h2 class="text-sm font-semibold truncate <?php echo e($fg); ?>"><?php echo e($supplier->name); ?></h2>

                
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border
                    <?php echo e($supplier->is_active
                        ? 'bg-emerald-600 text-white border-emerald-500/50'
                        : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]'); ?>">
                    <?php echo e($supplier->is_active ? 'Active' : 'Inactive'); ?>

                </span>
            </div>

            <p class="text-[11px] <?php echo e($muted); ?> truncate">
                <?php echo e($supplier->type ?: 'Type not set'); ?>

                <?php if($supplier->city || $supplier->country): ?>
                    • <?php echo e($supplier->city); ?><?php echo e($supplier->city && $supplier->country ? ', ' : ''); ?><?php echo e($supplier->country); ?>

                <?php endif; ?>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button type="button"
                    onclick="openSupplierEditModal()"
                    class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]">
                Edit
            </button>

            
            <button type="button"
                    onclick="openSupplierStatusModal()"
                    class="px-3 py-1.5 rounded-xl text-[11px] font-semibold transition
                        <?php echo e($supplier->is_active
                            ? 'bg-rose-600 hover:bg-rose-500 text-white'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white'); ?>">
                <?php echo e($supplier->is_active ? 'Deactivate' : 'Activate'); ?>

            </button>
        </div>
    </div>

    
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide <?php echo e($muted); ?>">
                Default currency
            </div>
            <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
                <?php echo e($supplier->default_currency ?: '—'); ?>

            </div>
            <div class="text-[10px] <?php echo e($muted); ?>">
                Used as default on purchases
            </div>
        </div>

        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide <?php echo e($muted); ?>">
                Contact
            </div>
            <div class="mt-1 text-[11px] <?php echo e($fg); ?>">
                <?php echo e($supplier->contact_person ?: 'Not set'); ?>

            </div>
            <div class="text-[10px] <?php echo e($muted); ?>">
                <?php echo e($supplier->phone ?: 'No phone'); ?>

            </div>
        </div>

        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide <?php echo e($muted); ?>">
                Email
            </div>
            <div class="mt-1 text-[11px] <?php echo e($fg); ?> truncate">
                <?php echo e($supplier->email ?: 'No email'); ?>

            </div>
            <div class="text-[10px] <?php echo e($muted); ?>">
                For POs & documents
            </div>
        </div>
    </div>

    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> px-3 py-3">
        <div class="text-[10px] uppercase tracking-wide <?php echo e($muted); ?> mb-1">
            Notes
        </div>
        <p class="text-[11px] <?php echo e($fg); ?> whitespace-pre-line">
            <?php echo e($supplier->notes ?: 'No special notes for this supplier yet.'); ?>

        </p>
    </div>
</div>


<div id="supplierEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-md rounded-2xl <?php echo e($surface); ?> border <?php echo e($border); ?> p-4 m-3 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold <?php echo e($fg); ?>">Edit supplier</h3>
            <button type="button"
                    class="<?php echo e($btnGhost); ?> h-9 w-9 text-lg leading-none"
                    onclick="closeSupplierEditModal()">×</button>
        </div>

        <?php
            $label = "block text-[11px] $muted mb-1";
            $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
            $select = $input;
            $textarea = $input;
        ?>

        <form method="post" action="<?php echo e(route('settings.suppliers.update', $supplier)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div>
                <label class="<?php echo e($label); ?>">Name</label>
                <input type="text" name="name"
                       value="<?php echo e(old('name', $supplier->name)); ?>"
                       class="<?php echo e($input); ?>">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Type</label>
                    <select name="type" class="<?php echo e($select); ?>">
                        <option value="" <?php if(!$supplier->type): echo 'selected'; endif; ?>>Not set</option>
                        <option value="port" <?php if($supplier->type === 'port'): echo 'selected'; endif; ?>>Port / terminal</option>
                        <option value="local_depot" <?php if($supplier->type === 'local_depot'): echo 'selected'; endif; ?>>Local depot</option>
                        <option value="trader" <?php if($supplier->type === 'trader'): echo 'selected'; endif; ?>>Trader</option>
                    </select>
                </div>
                <div>
                    <label class="<?php echo e($label); ?>">Default currency</label>
                    <input type="text" name="default_currency"
                           value="<?php echo e(old('default_currency', $supplier->default_currency)); ?>"
                           class="<?php echo e($input); ?>">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Country</label>
                    <input type="text" name="country"
                           value="<?php echo e(old('country', $supplier->country)); ?>"
                           class="<?php echo e($input); ?>">
                </div>
                <div>
                    <label class="<?php echo e($label); ?>">City</label>
                    <input type="text" name="city"
                           value="<?php echo e(old('city', $supplier->city)); ?>"
                           class="<?php echo e($input); ?>">
                </div>
            </div>

            <div>
                <label class="<?php echo e($label); ?>">Contact person</label>
                <input type="text" name="contact_person"
                       value="<?php echo e(old('contact_person', $supplier->contact_person)); ?>"
                       class="<?php echo e($input); ?>">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Phone</label>
                    <input type="text" name="phone"
                           value="<?php echo e(old('phone', $supplier->phone)); ?>"
                           class="<?php echo e($input); ?>">
                </div>
                <div>
                    <label class="<?php echo e($label); ?>">Email</label>
                    <input type="email" name="email"
                           value="<?php echo e(old('email', $supplier->email)); ?>"
                           class="<?php echo e($input); ?>">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-600 focus:ring-emerald-500/40"
                       <?php if(old('is_active', $supplier->is_active)): echo 'checked'; endif; ?>>
                <label for="edit_is_active" class="text-[11px] <?php echo e($fg); ?>">
                    Supplier is active
                </label>
            </div>

            <div>
                <label class="<?php echo e($label); ?>">Notes</label>
                <textarea name="notes" rows="2" class="<?php echo e($textarea); ?>"><?php echo e(old('notes', $supplier->notes)); ?></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]"
                        onclick="closeSupplierEditModal()">
                    Cancel
                </button>

                
                <button type="submit"
                        class="px-4 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition border border-emerald-500/50">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>


<div id="supplierStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-sm rounded-2xl <?php echo e($surface); ?> border <?php echo e($border); ?> p-4 m-3 shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="mb-2">
            <h3 class="text-sm font-semibold <?php echo e($fg); ?> mb-1">
                <?php echo e($supplier->is_active ? 'Deactivate supplier?' : 'Activate supplier?'); ?>

            </h3>
            <p class="text-[11px] <?php echo e($muted); ?>">
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
                    class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]"
                    onclick="closeSupplierStatusModal()">
                Cancel
            </button>

            
            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold transition border
                        <?php echo e($supplier->is_active
                            ? 'bg-rose-600 hover:bg-rose-500 text-white border-rose-500/50'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white border-emerald-500/50'); ?>">
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