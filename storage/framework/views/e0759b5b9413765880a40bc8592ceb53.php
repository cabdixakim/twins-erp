

<?php
    /**
     * Theme-aware + premium (token-based)
     * Uses app.css tokens:
     *  --tw-fg, --tw-muted, --tw-surface, --tw-surface-2, --tw-border, --tw-accent, --tw-accent-soft
     */

    $sectionWrap = "pt-3 mt-2 border-t border-[color:var(--tw-border)]";

    $accBtnBase =
        "w-full px-3 py-2 rounded-2xl flex items-center justify-between transition
         border border-[color:var(--tw-border)]
         bg-[color:var(--tw-surface)] hover:bg-[color:var(--tw-surface-2)]
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

    $accBtnActive =
        "bg-[linear-gradient(90deg,var(--tw-accent-soft),transparent)]
         border-[color:rgba(34,197,94,.55)]
         shadow-[0_14px_40px_rgba(2,6,23,.10)]";

    $linkBase =
        "flex items-center gap-2 px-3 py-2 rounded-2xl text-[12px] transition
         border border-transparent
         hover:bg-[color:var(--tw-surface-2)]
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

    $linkActive =
        "bg-[color:var(--tw-surface-2)]
         border border-[color:rgba(34,197,94,.35)]
         shadow-[0_10px_25px_rgba(2,6,23,.08)]";

    $muted = "tw-muted";
?>

<div class="<?php echo e($sectionWrap); ?>">

    
    <button type="button"
            class="<?php echo e($accBtnBase); ?> <?php echo e($onSettingsRoute ? $accBtnActive : ''); ?>"
            data-acc-toggle="settings">
        <span class="flex items-center gap-2 min-w-0">
            <span class="h-7 w-7 rounded-2xl grid place-items-center
                        bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                <span class="text-[13px]" aria-hidden="true">⚙️</span>
            </span>

            <span class="tracking-wide uppercase text-[11px] sidebar-label truncate">
                Settings
            </span>
        </span>

        <span data-acc-caret class="text-[10px] <?php echo e($muted); ?>">
            <?php echo e($onSettingsRoute ? '▾' : '▸'); ?>

        </span>
    </button>

    
    <div class="mt-2 space-y-1 pl-3" data-acc-panel="settings">

        
        <?php if($user && $user->hasPermission('depots.view')): ?>
            <a href="<?php echo e(route('settings.depots.index')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('settings.depots.*') ? $linkActive : ''); ?>">
                <span class="h-7 w-7 rounded-2xl grid place-items-center
                             bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                    <span aria-hidden="true">🏭</span>
                </span>
                <span class="sidebar-label truncate">Depots</span>
                <span class="ml-auto text-[10px] <?php echo e($muted); ?> sidebar-label">
                    <?php echo e(request()->routeIs('settings.depots.*') ? 'Active' : ''); ?>

                </span>
            </a>
        <?php endif; ?>

        
        <?php if($user && $user->hasPermission('products.view')): ?>
            <a href="<?php echo e(route('products.index')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('products.*') ? $linkActive : ''); ?>">
                <span class="h-7 w-7 rounded-2xl grid place-items-center
                             bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                    <span aria-hidden="true">📦</span>
                </span>
                <span class="sidebar-label truncate">Products</span>
                <span class="ml-auto text-[10px] <?php echo e($muted); ?> sidebar-label">
                    <?php echo e(request()->routeIs('products.*') ? 'Active' : ''); ?>

                </span>
            </a>
        <?php endif; ?>

        
        <?php if($userRole === 'owner'): ?>
            <a href="<?php echo e(route('settings.company.edit')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('settings.company.*') ? $linkActive : ''); ?>">
                <span class="h-7 w-7 rounded-2xl grid place-items-center
                             bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                    <span aria-hidden="true">🧾</span>
                </span>
                <span class="sidebar-label truncate">Company profile</span>
                <span class="ml-auto text-[10px] <?php echo e($muted); ?> sidebar-label">
                    <?php echo e(request()->routeIs('settings.company.*') ? 'Active' : ''); ?>

                </span>
            </a>
        <?php endif; ?>

        
        <?php if(($user && $user->hasPermission('suppliers.view')) || $userRole === 'owner'): ?>
            <a href="<?php echo e(route('settings.suppliers.index')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('settings.suppliers.*') ? $linkActive : ''); ?>">
                <span class="h-7 w-7 rounded-2xl grid place-items-center
                             bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                    <span aria-hidden="true">⛽</span>
                </span>
                <span class="sidebar-label truncate">Suppliers</span>
                <span class="ml-auto text-[10px] <?php echo e($muted); ?> sidebar-label">
                    <?php echo e(request()->routeIs('settings.suppliers.*') ? 'Active' : ''); ?>

                </span>
            </a>
        <?php endif; ?>

        
        <?php if($user && ($user->hasPermission('transport.local') || $user->hasPermission('transport.intl') || $userRole === 'owner')): ?>
            <a href="<?php echo e(route('settings.transporters.index')); ?>"
               class="<?php echo e($linkBase); ?> <?php echo e(request()->routeIs('settings.transporters.*') ? $linkActive : ''); ?>">
                <span class="h-7 w-7 rounded-2xl grid place-items-center
                             bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                    <span aria-hidden="true">🚚</span>
                </span>
                <span class="sidebar-label truncate">Transporters</span>
                <span class="ml-auto text-[10px] <?php echo e($muted); ?> sidebar-label">
                    <?php echo e(request()->routeIs('settings.transporters.*') ? 'Active' : ''); ?>

                </span>
            </a>
        <?php endif; ?>

        
        <?php if($userRole === 'owner'): ?>
            <button type="button"
                    class="w-full mt-2 px-3 py-2 rounded-2xl flex items-center justify-between transition
                        border border-[color:var(--tw-border)]
                        bg-[color:var(--tw-surface)] hover:bg-[color:var(--tw-surface-2)]
                        focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                    data-acc-toggle="user-settings">
                <span class="flex items-center gap-2 min-w-0">
                    <span class="h-7 w-7 rounded-2xl grid place-items-center
                                bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                        <span aria-hidden="true">🛠️</span>
                    </span>
                    <span class="text-[11px] sidebar-label truncate">User settings</span>
                </span>

                <span data-acc-caret class="text-[10px] <?php echo e($muted); ?>">▸</span>
            </button>

            <div class="mt-2 space-y-1 pl-4 hidden" data-acc-panel="user-settings">
                <a href="<?php echo e(route('admin.users.index')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->is('admin/users*') ? $linkActive : ''); ?>">
                    <span class="h-7 w-7 rounded-2xl grid place-items-center
                                bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                        <span aria-hidden="true">👤</span>
                    </span>
                    <span class="sidebar-label truncate">Users</span>
                </a>

                <a href="<?php echo e(route('admin.roles.index')); ?>"
                class="<?php echo e($linkBase); ?> <?php echo e(request()->is('admin/roles*') ? $linkActive : ''); ?>">
                    <span class="h-7 w-7 rounded-2xl grid place-items-center
                                bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                        <span aria-hidden="true">🛡️</span>
                    </span>
                    <span class="sidebar-label truncate">Roles &amp; Permissions</span>
                </a>
            </div>
        <?php endif; ?>
         
    </div>
</div><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/nav-settings.blade.php ENDPATH**/ ?>