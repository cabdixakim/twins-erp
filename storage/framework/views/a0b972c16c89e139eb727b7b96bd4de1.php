
<?php
    /**
     * Theme-aware + premium (token-based)
     * Flags expected:
     *  $onDashboard, $onDepotStock, $onPurchases, $onSales
     */

    $itemBase =
        "group relative flex items-center gap-3 rounded-2xl px-3 py-2.5 border transition
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]
         hover:-translate-y-[1px] active:translate-y-0";

    $itemIdle =
        "bg-[color:var(--tw-surface)] border-[color:var(--tw-border)]
         text-[color:var(--tw-fg)]
         hover:bg-[color:var(--tw-surface-2)]";

    $itemActive =
        "bg-[linear-gradient(90deg,var(--tw-accent-soft),transparent)]
         border-[color:rgba(34,197,94,.55)]
         text-[color:var(--tw-fg)]
         shadow-[0_14px_40px_rgba(2,6,23,.10)]";

    $iconWrapBase =
        "tw-tip-r flex h-9 w-9 items-center justify-center rounded-2xl border transition
         group-hover:shadow-[0_10px_25px_rgba(2,6,23,.10)]";

    $iconWrapIdle =
        "bg-[color:var(--tw-surface-2)] border-[color:var(--tw-border)]
         group-hover:bg-[color:var(--tw-btn-hover)]";

    $iconWrapActive =
        "bg-[color:var(--tw-accent-soft)] border-[color:rgba(34,197,94,.45)]";

    $kicker = "text-[11px] tw-muted truncate";
    $title  = "text-[13px] font-semibold truncate";
?>

<div class="space-y-1.5">

    
    <a href="<?php echo e(route('dashboard')); ?>"
       class="<?php echo e($itemBase); ?> <?php echo e($onDashboard ? $itemActive : $itemIdle); ?>">
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     <?php echo e($onDashboard ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent'); ?>"></span>

        <span class="<?php echo e($iconWrapBase); ?> <?php echo e($onDashboard ? $iconWrapActive : $iconWrapIdle); ?>">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 19V11"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V8"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 19V14"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 19V10"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="<?php echo e($title); ?>">Summary</div>
            <div class="<?php echo e($kicker); ?>">High-level view of all activity</div>
        </div>
    </a>

    
    <a href="<?php echo e(route('depot-stock.index')); ?>"
       class="<?php echo e($itemBase); ?> <?php echo e($onDepotStock ? $itemActive : $itemIdle); ?>">
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     <?php echo e($onDepotStock ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent'); ?>"></span>

        <span class="<?php echo e($iconWrapBase); ?> <?php echo e($onDepotStock ? $iconWrapActive : $iconWrapIdle); ?>">
            <svg class="w-5 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="<?php echo e($title); ?>">Depot Stock</div>
            <div class="<?php echo e($kicker); ?>">Receive, sell, adjust by depot</div>
        </div>
    </a>

    
    <a href="<?php echo e(route('purchases.index')); ?>"
       class="<?php echo e($itemBase); ?> <?php echo e($onPurchases ? $itemActive : $itemIdle); ?>">
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     <?php echo e($onPurchases ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent'); ?>"></span>

        <span class="<?php echo e($iconWrapBase); ?> <?php echo e($onPurchases ? $iconWrapActive : $iconWrapIdle); ?>">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 17h18"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="<?php echo e($title); ?>">Purchases</div>
            <div class="<?php echo e($kicker); ?>">Draft → confirm → batch</div>
        </div>
    </a>

    
    <a href="<?php echo e(route('sales.index')); ?>"
       class="<?php echo e($itemBase); ?> <?php echo e($onSales ?? false ? $itemActive : $itemIdle); ?>">
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     <?php echo e(($onSales ?? false) ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent'); ?>"></span>

        <span class="<?php echo e($iconWrapBase); ?> <?php echo e(($onSales ?? false) ? $iconWrapActive : $iconWrapIdle); ?>">
            
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16l-3-2-3 2-3-2-3 2-4-2V4z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="<?php echo e($title); ?>">Sales</div>
            <div class="<?php echo e($kicker); ?>">Draft → post → FIFO issue</div>
        </div>

        <span class="ml-auto opacity-0 translate-x-[-2px] transition
                     group-hover:opacity-100 group-hover:translate-x-0 sidebar-label">
            <svg class="w-4 h-4 tw-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
            </svg>
        </span>
    </a>

</div><?php /**PATH /home/runner/workspace/resources/views/layouts/partials/nav-primary.blade.php ENDPATH**/ ?>