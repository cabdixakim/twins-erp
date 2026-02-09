
<?php
    $name = $company->name ?? 'Twins ERP';
    $initial = strtoupper(mb_substr(trim($name), 0, 1));

    // stable “icon tile” container (works for white/black/transparent logos)
    $tile = "h-10 w-10 rounded-xl grid place-items-center overflow-hidden
             bg-white border border-slate-200 shadow-sm
             dark:bg-slate-950 dark:border-slate-800";

    // fallback initial badge (premium)
    $fallback = "h-full w-full grid place-items-center font-semibold text-[14px] tracking-wide
                 text-white bg-gradient-to-br from-emerald-500 to-cyan-500";
?>

<div class="flex items-center gap-3 min-w-0">
    
    <div class="<?php echo e($tile); ?>">
        <?php if($company && $company->logo_path): ?>
            <img src="<?php echo e(asset('storage/'.$company->logo_path)); ?>"
                 alt="<?php echo e($name); ?> logo"
                 class="h-full w-full object-contain p-1.5">
        <?php else: ?>
            <div class="<?php echo e($fallback); ?>">
                <?php echo e($initial); ?>

            </div>
        <?php endif; ?>
    </div>

    
    <div class="min-w-0 sidebar-label">
        <div class="font-semibold text-sm uppercase tracking-wide truncate">
            <?php echo e($name); ?>

        </div>
        <div class="text-[11px] tw-muted">Fuel &amp; Transport ERP</div>
    </div>
</div><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/brand.blade.php ENDPATH**/ ?>