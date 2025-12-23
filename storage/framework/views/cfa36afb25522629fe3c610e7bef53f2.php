<div class="flex items-center gap-3 min-w-0">
    <?php if($company && $company->logo_path): ?>
        <img src="<?php echo e(asset('storage/'.$company->logo_path)); ?>"
             class="w-10 h-10 rounded-xl object-cover border border-slate-700 shadow">
    <?php else: ?>
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 animate-pulse"></div>
    <?php endif; ?>

    
    <div class="min-w-0 sidebar-label">
        <div class="font-semibold text-sm uppercase tracking-wide truncate">
            <?php echo e($company->name ?? 'Twins ERP'); ?>

        </div>
        <div class="text-[11px] text-slate-400">Fuel &amp; Transport ERP</div>
    </div>
</div><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/brand.blade.php ENDPATH**/ ?>