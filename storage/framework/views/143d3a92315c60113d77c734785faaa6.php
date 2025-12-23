<aside id="desktopSidebar"
       class="sidebar w-64 bg-slate-900/95 border-r border-slate-800 hidden md:flex flex-col backdrop-blur">

    <div class="px-4 py-4 flex items-center justify-between gap-3 border-b border-slate-800/80">
        <?php echo $__env->make('layouts.partials.brand', compact('company'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <button type="button"
                id="toggleDesktopSidebar"
                class="tw-tip-r h-9 w-9 grid place-items-center rounded-xl border border-slate-700/80
                       bg-slate-950/40 hover:bg-slate-800/80 text-slate-200 transition"
                data-tip="Collapse sidebar"
                aria-label="Collapse sidebar">
            <svg id="sidebarToggleIcon"
                 class="w-5 h-5 transition-transform duration-200"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
    </div>

    
    <div class="flex-1 overflow-visible">
        
        <div class="h-full overflow-y-auto overflow-x-visible px-3 py-4 space-y-4 text-sm">
            <?php echo $__env->make('layouts.partials.nav-primary', compact('onDashboard','onDepotStock'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('layouts.partials.nav-settings', compact('user','userRole','onSettingsRoute'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <form method="post" action="<?php echo e(route('logout')); ?>" class="px-3 py-3 border-t border-slate-800/80">
        <?php echo csrf_field(); ?>
        <button class="logout-btn tw-tip-r w-full flex items-center justify-center md:justify-start gap-3 px-3 py-2 rounded-xl
                       bg-slate-800/60 hover:bg-rose-600/90 border border-slate-700/50
                       text-[12px] font-medium transition"
                data-tip="Logout"
                aria-label="Logout">

            <span class="logout-icon h-9 w-9 grid place-items-center rounded-lg bg-slate-950/30">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 7V5a2 2 0 012-2h7a2 2 0 012 2v14a2 2 0 01-2 2h-7a2 2 0 01-2-2v-2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0l3-3m-3 3l3 3"/>
                </svg>
            </span>

            <span class="sidebar-label">Logout</span>
        </button>
    </form>

</aside><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/sidebar-desktop.blade.php ENDPATH**/ ?>