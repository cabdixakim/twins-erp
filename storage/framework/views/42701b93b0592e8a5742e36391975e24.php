<?php
    /** @var \App\Models\Depot|null $depot */

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
?>

<?php if(!$depot): ?>
    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4 text-xs <?php echo e($muted); ?>">
        No depot selected yet.
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="space-y-4">

    
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide <?php echo e($muted); ?>">Selected depot</div>
            <div class="flex items-center gap-2 min-w-0">
                <h2 class="text-sm font-semibold truncate <?php echo e($fg); ?>"><?php echo e($depot->name); ?></h2>

                
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border
                    <?php echo e($depot->is_active
                        ? 'bg-emerald-600 text-white border-emerald-500/50'
                        : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]'); ?>">
                    <?php echo e($depot->is_active ? 'Active' : 'Inactive'); ?>

                </span>
            </div>

            <p class="text-[11px] <?php echo e($muted); ?>">
                <?php echo e($depot->city ?: 'City not set'); ?>

            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            
            <button type="button"
                    onclick="openDepotEditModal()"
                    class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]">
                Edit
            </button>

            
            <button type="button"
                    onclick="openDepotStatusModal()"
                    class="px-3 py-1.5 rounded-xl text-[11px] font-semibold transition border
                    <?php echo e($depot->is_active
                        ? 'bg-rose-600 hover:bg-rose-500 text-white border-rose-500/50'
                        : 'bg-emerald-600 hover:bg-emerald-500 text-white border-emerald-500/50'); ?>">
                <?php echo e($depot->is_active ? 'Deactivate' : 'Activate'); ?>

            </button>
        </div>
    </div>

    
    <div class="grid gap-3 sm:grid-cols-3">

        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide <?php echo e($muted); ?>">
                Storage fee
            </div>
            <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
                <?php echo e(number_format((float)$depot->storage_fee_per_1000_l, 2)); ?> $
            </div>
            <div class="text-[10px] <?php echo e($muted); ?>">
                per 1,000L / day
            </div>
        </div>

        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide <?php echo e($muted); ?>">
                Default shrinkage
            </div>
            <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
                <?php echo e(number_format((float)$depot->default_shrinkage_pct, 3)); ?> %
            </div>
            <div class="text-[10px] <?php echo e($muted); ?>">
                Used unless a custom allowance is set
            </div>
        </div>

        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide <?php echo e($muted); ?>">
                Notes
            </div>
            <div class="mt-1 text-[11px] <?php echo e($fg); ?> line-clamp-3">
                <?php echo e($depot->notes ?: 'No special instructions for this depot yet.'); ?>

            </div>
        </div>

    </div>
</div>


<div id="depotEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55 p-2">
    <div class="w-full max-w-md rounded-2xl <?php echo e($surface); ?> border <?php echo e($border); ?> p-4 m-3 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold <?php echo e($fg); ?>">Edit depot</h3>
            <button type="button"
                    class="<?php echo e($btnGhost); ?> h-9 w-9 text-lg leading-none"
                    onclick="closeDepotEditModal()">×</button>
        </div>

        <?php
            $label = "block text-[11px] $muted mb-1";
            $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
        ?>

        <form method="post" action="<?php echo e(route('settings.depots.update', $depot)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div>
                <label class="<?php echo e($label); ?>">Name</label>
                <input type="text" name="name"
                       value="<?php echo e(old('name', $depot->name)); ?>"
                       class="<?php echo e($input); ?>">
            </div>

            <div>
                <label class="<?php echo e($label); ?>">City</label>
                <input type="text" name="city"
                       value="<?php echo e(old('city', $depot->city)); ?>"
                       class="<?php echo e($input); ?>">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="<?php echo e($label); ?>">Storage fee (per 1,000L / day)</label>
                    <input type="number" step="0.01" min="0"
                           name="storage_fee_per_1000_l"
                           value="<?php echo e(old('storage_fee_per_1000_l', $depot->storage_fee_per_1000_l)); ?>"
                           class="<?php echo e($input); ?>">
                </div>

                <div>
                    <label class="<?php echo e($label); ?>">Default shrinkage %</label>
                    <input type="number" step="0.001" min="0"
                           name="default_shrinkage_pct"
                           value="<?php echo e(old('default_shrinkage_pct', $depot->default_shrinkage_pct)); ?>"
                           class="<?php echo e($input); ?>">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-600 focus:ring-emerald-500/40"
                       <?php if(old('is_active', $depot->is_active)): echo 'checked'; endif; ?>>
                <label for="edit_is_active" class="text-[11px] <?php echo e($fg); ?>">
                    Depot is active
                </label>
            </div>

            <div>
                <label class="<?php echo e($label); ?>">Notes</label>
                <textarea name="notes" rows="2" class="<?php echo e($input); ?>"><?php echo e(old('notes', $depot->notes)); ?></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]"
                        onclick="closeDepotEditModal()">
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


<div id="depotStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55"
     onclick="closeDepotStatusModal()">
    <div class="w-full max-w-sm rounded-2xl <?php echo e($surface); ?> border <?php echo e($border); ?> p-4 m-3 shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="mb-2">
            <h3 class="text-sm font-semibold <?php echo e($fg); ?> mb-1">
                <?php echo e($depot->is_active ? 'Deactivate depot?' : 'Activate depot?'); ?>

            </h3>
            <p class="text-[11px] <?php echo e($muted); ?>">
                <?php echo e($depot->is_active
                    ? 'Deactivated depots cannot be used for new loads or sales until re-activated.'
                    : 'Once activated, this depot can be used for stock movements and sales.'); ?>

            </p>
        </div>

        <form method="post" action="<?php echo e(route('settings.depots.toggle-active', $depot)); ?>"
              class="flex justify-end gap-2 pt-2">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <button type="button"
                    class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]"
                    onclick="closeDepotStatusModal()">
                Cancel
            </button>

            
            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold transition border
                        <?php echo e($depot->is_active
                            ? 'bg-rose-600 hover:bg-rose-500 text-white border-rose-500/50'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white border-emerald-500/50'); ?>">
                <?php echo e($depot->is_active ? 'Deactivate' : 'Activate'); ?>

            </button>
        </form>
    </div>
</div>

<script>
    function openDepotEditModal() {
        const m = document.getElementById('depotEditModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeDepotEditModal() {
        const m = document.getElementById('depotEditModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function openDepotStatusModal() {
        const m = document.getElementById('depotStatusModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeDepotStatusModal() {
        const m = document.getElementById('depotStatusModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
</script><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/depots/_details.blade.php ENDPATH**/ ?>