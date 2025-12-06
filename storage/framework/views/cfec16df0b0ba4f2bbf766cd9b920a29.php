<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['transporter']));

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

foreach (array_filter((['transporter']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php if(!$transporter): ?>
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-xs text-slate-400">
        No transporter selected yet.
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="space-y-4">

    
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide text-slate-500">Selected transporter</div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold truncate"><?php echo e($transporter->name); ?></h2>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                    <?php echo e($transporter->is_active ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/50'
                                               : 'bg-slate-800 text-slate-300 border border-slate-700'); ?>">
                    <?php echo e($transporter->is_active ? 'Active' : 'Inactive'); ?>

                </span>
            </div>
            <p class="text-[11px] text-slate-400">
                <?php echo e($transporter->type === 'intl' ? 'International transporter'
                    : ($transporter->type === 'local' ? 'Local transporter' : 'Type not set')); ?>

                <?php if($transporter->city || $transporter->country): ?>
                    • <?php echo e($transporter->city); ?><?php echo e($transporter->city && $transporter->country ? ', ' : ''); ?><?php echo e($transporter->country); ?>

                <?php endif; ?>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button
                type="button"
                onclick="openTransporterEditModal()"
                class="px-3 py-1.5 rounded-xl border border-slate-700 bg-slate-900/70 text-[11px] hover:bg-slate-800">
                Edit
            </button>

            <button
                type="button"
                onclick="openTransporterStatusModal()"
                class="px-3 py-1.5 rounded-xl text-[11px]
                    <?php echo e($transporter->is_active
                        ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                        : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950'); ?>">
                <?php echo e($transporter->is_active ? 'Deactivate' : 'Activate'); ?>

            </button>
        </div>
    </div>

    
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Default rate / 1,000L
            </div>
            <div class="mt-1 text-sm font-semibold">
                <?php if($transporter->default_rate_per_1000_l !== null): ?>
                    <?php echo e(number_format($transporter->default_rate_per_1000_l, 4)); ?>

                    <?php echo e($transporter->default_currency ?? 'USD'); ?>

                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
            <div class="text-[10px] text-slate-500">
                Baseline for freight calculations
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Payment terms
            </div>
            <div class="mt-1 text-sm font-semibold">
                <?php echo e($transporter->payment_terms ?: 'Not set'); ?>

            </div>
            <div class="text-[10px] text-slate-500">
                Used on statements & settlements
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Default currency
            </div>
            <div class="mt-1 text-sm font-semibold">
                <?php echo e($transporter->default_currency ?: 'USD'); ?>

            </div>
            <div class="text-[10px] text-slate-500">
                Used for freight & short charges
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Contact
            </div>
            <div class="mt-1 text-[11px] text-slate-200">
                <?php echo e($transporter->contact_person ?: 'Not set'); ?>

            </div>
            <div class="text-[10px] text-slate-500">
                <?php echo e($transporter->phone ?: 'No phone'); ?>

            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Email
            </div>
            <div class="mt-1 text-[11px] text-slate-200 truncate">
                <?php echo e($transporter->email ?: 'No email'); ?>

            </div>
            <div class="text-[10px] text-slate-500">
                For POs, invoices & docs
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-3">
        <div class="text-[10px] uppercase tracking-wide text-slate-500 mb-1">
            Notes
        </div>
        <p class="text-[11px] text-slate-300 whitespace-pre-line">
            <?php echo e($transporter->notes ?: 'No special notes for this transporter yet.'); ?>

        </p>
    </div>
</div>


<div id="transporterEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40">
    <div class="w-full max-w-md rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold">Edit transporter</h3>
            <button type="button" class="text-slate-500 text-lg leading-none"
                    onclick="closeTransporterEditModal()">×</button>
        </div>

        <form method="post" action="<?php echo e(route('settings.transporters.update', $transporter)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Name</label>
                <input type="text" name="name"
                       value="<?php echo e(old('name', $transporter->name)); ?>"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Type</label>
                    <select name="type"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                        <option value="" <?php if(!$transporter->type): echo 'selected'; endif; ?>>Not set</option>
                        <option value="intl" <?php if($transporter->type === 'intl'): echo 'selected'; endif; ?>>International</option>
                        <option value="local" <?php if($transporter->type === 'local'): echo 'selected'; endif; ?>>Local</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Default currency</label>
                    <input type="text" name="default_currency"
                           value="<?php echo e(old('default_currency', $transporter->default_currency)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Country</label>
                    <input type="text" name="country"
                           value="<?php echo e(old('country', $transporter->country)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">City</label>
                    <input type="text" name="city"
                           value="<?php echo e(old('city', $transporter->city)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Contact person</label>
                <input type="text" name="contact_person"
                       value="<?php echo e(old('contact_person', $transporter->contact_person)); ?>"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Phone</label>
                    <input type="text" name="phone"
                           value="<?php echo e(old('phone', $transporter->phone)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Email</label>
                    <input type="email" name="email"
                           value="<?php echo e(old('email', $transporter->email)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">
                        Default rate (per 1,000L)
                    </label>
                    <input type="number" name="default_rate_per_1000_l" step="0.0001" min="0"
                           value="<?php echo e(old('default_rate_per_1000_l', $transporter->default_rate_per_1000_l)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Payment terms</label>
                    <input type="text" name="payment_terms"
                           value="<?php echo e(old('payment_terms', $transporter->payment_terms)); ?>"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_transporter_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500/60"
                       <?php if(old('is_active', $transporter->is_active)): echo 'checked'; endif; ?>>
                <label for="edit_transporter_is_active" class="text-[11px] text-slate-300">
                    Transporter is active
                </label>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40"><?php echo e(old('notes', $transporter->notes)); ?></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                        onclick="closeTransporterEditModal()">
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


<div id="transporterStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40">
    <div class="w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">

        <div class="mb-2">
            <h3 class="text-sm font-semibold mb-1">
                <?php echo e($transporter->is_active ? 'Deactivate transporter?' : 'Activate transporter?'); ?>

            </h3>
            <p class="text-[11px] text-slate-400">
                <?php echo e($transporter->is_active
                    ? 'Deactivated transporters cannot receive new loads until re-activated.'
                    : 'Once activated, this transporter can be used for new intl/local trips.'); ?>

            </p>
        </div>

        <form method="post" action="<?php echo e(route('settings.transporters.toggle-active', $transporter)); ?>"
              class="flex justify-end gap-2 pt-2">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <button type="button"
                    class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                    onclick="closeTransporterStatusModal()">
                Cancel
            </button>

            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold
                        <?php echo e($transporter->is_active
                            ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                            : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950'); ?>">
                <?php echo e($transporter->is_active ? 'Deactivate' : 'Activate'); ?>

            </button>
        </form>
    </div>
</div>

<script>
    function openTransporterEditModal() {
        const m = document.getElementById('transporterEditModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeTransporterEditModal() {
        const m = document.getElementById('transporterEditModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function openTransporterStatusModal() {
        const m = document.getElementById('transporterStatusModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeTransporterStatusModal() {
        const m = document.getElementById('transporterStatusModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    document.getElementById('transporterEditModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeTransporterEditModal();
        }
    });

    document.getElementById('transporterStatusModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeTransporterStatusModal();
        }
    });
</script><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/transporters/_details.blade.php ENDPATH**/ ?>