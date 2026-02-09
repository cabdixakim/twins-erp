
<div id="mobileSidebarOverlay"
     class="fixed inset-0 z-40 bg-black/40 opacity-0 pointer-events-none transition
            md:hidden
            [html.tw-mobile-open_&]:opacity-100
            [html.tw-mobile-open_&]:pointer-events-auto">
</div>

<aside id="mobileSidebar"
       class="fixed top-0 left-0 z-50 h-full w-64 md:hidden flex flex-col
              -translate-x-full transition-transform duration-300 ease-out
              tw-surface
              [html.tw-mobile-open_&]:translate-x-0">

    
    <div class="px-4 py-4 flex items-center justify-between border-b"
         style="border-color: var(--tw-border);">

        <?php echo $__env->make('layouts.partials.brand', compact('company'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <button
            type="button"
            data-mobile-close
            class="tw-icon-btn h-9 w-9 grid place-items-center rounded-xl"
            aria-label="Close menu">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>

    
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 text-sm">
        <?php echo $__env->make('layouts.partials.nav-primary', compact('onDashboard','onDepotStock','onPurchases'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('layouts.partials.nav-settings', compact('user','userRole','onSettingsRoute'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </nav>

    
    <form method="post"
          action="<?php echo e(route('logout')); ?>"
          class="px-3 py-3 border-t"
          style="border-color: var(--tw-border);">
        <?php echo csrf_field(); ?>

        <button
            class="w-full flex items-center gap-3 px-3 py-2 rounded-xl border text-[12px] font-medium transition
                   hover:bg-rose-600 hover:text-white"
            style="background: var(--tw-btn); border-color: var(--tw-border);">

            <span class="h-9 w-9 grid place-items-center rounded-lg"
                  style="background: var(--tw-surface-2);">
                <svg class="w-5 h-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                    <path d="M3 21V3a2 2 0 012-2h6"/>
                </svg>
            </span>

            <span>Logout</span>
        </button>
    </form>
</aside><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/sidebar-mobile.blade.php ENDPATH**/ ?>