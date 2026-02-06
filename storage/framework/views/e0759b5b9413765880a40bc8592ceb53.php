<div class="pt-2 border-t border-slate-800/70">
    <button type="button"
            onclick="toggleSettingsDesktop()"
            class="w-full mt-3 px-3 py-2 rounded-lg flex items-center justify-between text-xs
                   <?php echo e($onSettingsRoute ? 'bg-slate-800 text-slate-100' : 'bg-slate-900 text-slate-200 hover:bg-slate-800'); ?>">
        <span class="flex items-center gap-2">
            <span title="Settings">⚙️</span>
            <span class="tracking-wide uppercase text-[11px] sidebar-label">Settings</span>
        </span>
        <span id="settingsCaretDesktop" class="text-[10px] text-slate-400">
            <?php echo e($onSettingsRoute ? '▾' : '▸'); ?>

        </span>
    </button>

    <div id="settingsLinksDesktop" class="mt-2 space-y-1 pl-3">
        
        <?php if($user && $user->hasPermission('depots.view')): ?>
            <a href="<?php echo e(route('settings.depots.index')); ?>"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                      <?php echo e(request()->routeIs('settings.depots.*')
                            ? 'bg-slate-800 text-slate-50'
                            : 'hover:bg-slate-800/80 text-slate-200'); ?>">
                <span title="Depots">🏭</span>
                <span class="sidebar-label">Depots</span>
            </a>
        <?php endif; ?>
        <!-- products -->
        <?php if($user && $user->hasPermission('products.view')): ?>
            <a href="<?php echo e(route('products.index')); ?>"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                      <?php echo e(request()->routeIs('settings.products.*')
                            ? 'bg-slate-800 text-slate-50'
                            : 'hover:bg-slate-800/80 text-slate-200'); ?>">
                <span title="Products">📦</span>
                <span class="sidebar-label">Products</span>
            </a>
        <?php endif; ?>
        
        <?php if($userRole === 'owner'): ?>
            <a href="<?php echo e(route('settings.company.edit')); ?>"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                      <?php echo e(request()->routeIs('settings.company.*')
                            ? 'bg-slate-800 text-slate-50'
                            : 'hover:bg-slate-800/80 text-slate-200'); ?>">
                <span title="Company profile">🧾</span>
                <span class="sidebar-label">Company profile</span>
            </a>
        <?php endif; ?>

        
        <?php if(($user && $user->hasPermission('suppliers.view')) || $userRole === 'owner'): ?>
            <a href="<?php echo e(route('settings.suppliers.index')); ?>"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                      <?php echo e(request()->routeIs('settings.suppliers.*')
                            ? 'bg-slate-800 text-slate-50'
                            : 'hover:bg-slate-800/80 text-slate-200'); ?>">
                <span title="Suppliers">⛽</span>
                <span class="sidebar-label">Suppliers</span>
            </a>
        <?php endif; ?>

        
        <?php if($user && ($user->hasPermission('transport.local') || $user->hasPermission('transport.intl') || $userRole === 'owner')): ?>
            <a href="<?php echo e(route('settings.transporters.index')); ?>"
               class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                      <?php echo e(request()->routeIs('settings.transporters.*')
                            ? 'bg-slate-800 text-slate-50'
                            : 'hover:bg-slate-800/80 text-slate-200'); ?>">
                <span title="Transporters">🚚</span>
                <span class="sidebar-label">Transporters</span>
            </a>
        <?php endif; ?>

        
        <?php if($userRole === 'owner'): ?>
            <button type="button"
                    onclick="toggleUserSettingsDesktop()"
                    class="w-full mt-1 px-3 py-2 rounded-lg flex items-center justify-between text-[11px] bg-slate-900 hover:bg-slate-800 transition">
                <span class="flex items-center gap-2 text-slate-200">
                    <span title="User settings">🛠️</span>
                    <span class="sidebar-label">User settings</span>
                </span>
                <span id="userSettingsCaretDesktop" class="text-[10px] text-slate-400">▸</span>
            </button>

            <div id="userSettingsLinksDesktop" class="mt-1 space-y-1 pl-4 hidden">
                <a href="<?php echo e(route('admin.users.index')); ?>"
                   class="block px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800
                          <?php echo e(request()->is('admin/users*') ? 'bg-slate-800 text-slate-50' : 'text-slate-200'); ?>">
                    <span class="sidebar-label">👤 Users</span>
                    <span class="md:hidden">👤</span>
                </a>

                <a href="<?php echo e(route('admin.roles.index')); ?>"
                   class="block px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800
                          <?php echo e(request()->is('admin/roles*') ? 'bg-slate-800 text-slate-50' : 'text-slate-200'); ?>">
                    <span class="sidebar-label">🛡️ Roles &amp; Permissions</span>
                    <span class="md:hidden">🛡️</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/nav-settings.blade.php ENDPATH**/ ?>