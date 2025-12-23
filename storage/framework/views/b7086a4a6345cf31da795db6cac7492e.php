<aside id="mobileSidebar"
       class="sidebar fixed top-0 left-0 h-full w-64 bg-slate-900/95 border-r border-slate-800 z-50
              transform -translate-x-full md:hidden flex flex-col">

    <div class="px-4 py-4 flex justify-between items-center border-b border-slate-800/80">
        <?php echo $__env->make('layouts.partials.brand', compact('company'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <button id="closeMenu" class="h-9 w-9 grid place-items-center rounded-lg border border-slate-700 bg-slate-950/40 hover:bg-slate-800/80"
                aria-label="Close menu">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 text-sm">
        <?php echo $__env->make('layouts.partials.nav-primary', compact('onDashboard','onDepotStock'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('layouts.partials.nav-settings', compact('user','userRole','onSettingsRoute'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </nav>

    <form method="post" action="<?php echo e(route('logout')); ?>" class="px-3 py-3 border-t border-slate-800/80">
        <?php echo csrf_field(); ?>
        <button class="w-full flex items-center gap-3 px-3 py-2 rounded-xl
                       bg-slate-800/60 hover:bg-rose-600/90 border border-slate-700/50
                       text-[12px] font-medium transition"
                aria-label="Logout">
            <span class="h-9 w-9 grid place-items-center rounded-lg bg-slate-950/30">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 7V5a2 2 0 012-2h7a2 2 0 012 2v14a2 2 0 01-2 2h-7a2 2 0 01-2-2v-2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0l3-3m-3 3l3 3"/>
                </svg>
            </span>
            <span>Logout</span>
        </button>
    </form>

</aside><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/sidebar-mobile.blade.php ENDPATH**/ ?>