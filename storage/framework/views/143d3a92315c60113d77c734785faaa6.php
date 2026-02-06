

<aside id="desktopSidebar"
       class="sidebar w-64 bg-slate-900/95 border-r border-slate-800
              hidden md:flex flex-col backdrop-blur">

    
    <div class="px-4 py-4 flex items-center justify-between border-b border-slate-800/80">
        <?php echo $__env->make('layouts.partials.brand', compact('company'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <button type="button"
                id="toggleDesktopSidebar"
                class="h-8 w-8 grid place-items-center rounded-lg
                       bg-slate-800/40 hover:bg-slate-700/60
                       text-slate-300 transition"
                aria-label="Toggle sidebar">
            <svg id="sidebarToggleIcon"
                 class="w-4 h-4 transition-transform duration-200"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
    </div>

    
    <div class="flex-1 overflow-hidden">
        <div class="h-full overflow-y-auto px-3 py-4 space-y-4 text-sm">
            <?php echo $__env->make('layouts.partials.nav-primary', compact('onDashboard','onDepotStock', 'onPurchases'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('layouts.partials.nav-settings', compact('user','userRole','onSettingsRoute'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    
    <form method="post"
          action="<?php echo e(route('logout')); ?>"
          class="px-3 py-3 border-t border-slate-800/80">
        <?php echo csrf_field(); ?>

        <button type="submit"
                class="logout-btn w-full flex items-center gap-2
                       px-3 py-2 rounded-lg
                       text-slate-300 hover:text-white
                       hover:bg-rose-600/80
                       transition text-xs font-medium">

            <span class="logout-icon h-6 w-6 grid place-items-center rounded-md
                         bg-slate-800/50">
                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M10 7V5a2 2 0 012-2h7a2 2 0 012 2v14a2 2 0 01-2 2h-7a2 2 0 01-2-2v-2"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 12H3m0 0l3-3m-3 3l3 3"/>
                </svg>
            </span>

            <span class="sidebar-label">Logout</span>
        </button>
    </form>

</aside><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/sidebar-desktop.blade.php ENDPATH**/ ?>