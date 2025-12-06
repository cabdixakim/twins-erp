<?php
    $title    = 'Transporters';
    $subtitle = 'Register your local and international transport partners with default rates and contacts.';
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
            <h2 class="text-sm font-semibold">Transporters</h2>
            <button
                type="button"
                onclick="openTransporterCreateModal()"
                class="px-3 py-1.5 text-xs rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold">
                + New transporter
            </button>
        </div>

        <ul class="space-y-1 text-xs">
            <?php $__empty_1 = true; $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transporter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <li>
                    <a href="<?php echo e(route('settings.transporters.index', ['transporter' => $transporter->id])); ?>"
                       class="flex items-center justify-between px-3 py-2 rounded-xl
                              <?php echo e($currentTransporter && $currentTransporter->id === $transporter->id
                                    ? 'bg-slate-800 text-slate-50'
                                    : 'bg-slate-950/40 text-slate-300 hover:bg-slate-900'); ?>">
                        <div class="min-w-0">
                            <div class="font-semibold text-[13px] truncate">
                                <?php echo e($transporter->name); ?>

                            </div>
                            <div class="text-[10px] text-slate-500 truncate">
                                <?php echo e($transporter->type === 'intl' ? 'International' : ($transporter->type === 'local' ? 'Local' : 'Type not set')); ?>

                                <?php if($transporter->city || $transporter->country): ?>
                                    • <?php echo e($transporter->city); ?><?php echo e($transporter->city && $transporter->country ? ', ' : ''); ?><?php echo e($transporter->country); ?>

                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="text-[9px] px-2 py-0.5 rounded-full
                            <?php echo e($transporter->is_active
                                ? 'bg-emerald-900/50 text-emerald-200 border border-emerald-500/60'
                                : 'bg-slate-800 text-slate-300 border border-slate-500/50'); ?>">
                            <?php echo e($transporter->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="text-[11px] text-slate-500 px-1 py-2">
                    No transporters yet. Create at least one to attach loads and settlements.
                </li>
            <?php endif; ?>
        </ul>
    </div>

    
    <div class="md:col-span-2">
        <?php echo $__env->make('settings.transporters._details', ['transporter' => $currentTransporter], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>


<div id="transporterCreateModal"
     class="fixed inset-0 bg-black/50 hidden items-end sm:items-center justify-center p-4 z-50">
    <div class="w-full max-w-md rounded-2xl bg-slate-950 border border-slate-800 p-4 shadow-xl"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-semibold text-slate-100">New transporter</h2>
            <button type="button"
                    class="text-slate-400 text-lg leading-none"
                    onclick="closeTransporterCreateModal()">×</button>
        </div>

        <form method="post" action="<?php echo e(route('settings.transporters.store')); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Name</label>
                <input type="text" name="name"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40"
                       required>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Type</label>
                    <select name="type"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                        <option value="">Not set</option>
                        <option value="intl">International</option>
                        <option value="local">Local</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Default currency</label>
                    <input type="text" name="default_currency" value="USD"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Country</label>
                    <input type="text" name="country"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">City</label>
                    <input type="text" name="city"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Contact person</label>
                <input type="text" name="contact_person"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Phone</label>
                    <input type="text" name="phone"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Email</label>
                    <input type="email" name="email"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">
                        Default rate (per 1,000L)
                    </label>
                    <input type="number" name="default_rate_per_1000_l" step="0.0001" min="0"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Payment terms</label>
                    <input type="text" name="payment_terms"
                           placeholder="e.g. 30 days, monthly"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="create_transporter_is_active" name="is_active" value="1" checked
                       class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500/60">
                <label for="create_transporter_is_active" class="text-[11px] text-slate-300">
                    Transporter is active
                </label>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                        onclick="closeTransporterCreateModal()">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950">
                    Save transporter
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTransporterCreateModal() {
        const m = document.getElementById('transporterCreateModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeTransporterCreateModal() {
        const m = document.getElementById('transporterCreateModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    document.getElementById('transporterCreateModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeTransporterCreateModal();
        }
    });
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/transporters/index.blade.php ENDPATH**/ ?>