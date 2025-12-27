<?php
    $u = $user ?? auth()->user();

    $activeCompanyName = null;
    if ($u && method_exists($u, 'companies')) {
        $activeCompanyName = $u->companies()
            ->whereKey($u->active_company_id)
            ->value('name');
    }
    $activeCompanyName = $activeCompanyName ?: ($company->name ?? 'Company');

    $title = trim(view()->yieldContent('title', 'Dashboard'));
?>

<header class="sticky top-0 z-50 border-b border-slate-800/70 bg-slate-950">
    <div class="px-4 md:px-6 py-3 flex items-center justify-between gap-4">

        
        <div class="min-w-0 flex items-center gap-3">
            <button id="openMenu"
                    class="md:hidden h-9 w-9 grid place-items-center rounded-xl
                           bg-slate-900 hover:bg-slate-800 ring-1 ring-slate-800 transition"
                    aria-label="Open menu">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="min-w-0">
                <div class="text-[13px] md:text-[15px] font-semibold tracking-tight truncate">
                    <?php echo e($title); ?>

                </div>
                <div class="hidden md:block text-[11px] text-slate-400 truncate">
                    <?php echo $__env->yieldContent('subtitle'); ?>
                </div>
            </div>
        </div>

        
        <div class="flex items-center gap-2">

            
            <button type="button"
                    data-popover-btn="company"
                    class="h-9 flex items-center gap-2 pl-2 pr-2.5 rounded-2xl
                           bg-slate-900 hover:bg-slate-800 ring-1 ring-slate-800 transition"
                    aria-label="Company menu">
                <span class="h-6 w-6 rounded-xl grid place-items-center bg-slate-800 ring-1 ring-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                </span>

                <span class="text-[12px] font-semibold truncate max-w-[160px] md:max-w-[240px]">
                    <?php echo e($activeCompanyName); ?>

                </span>

                <svg class="w-4 h-4 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
            </button>

            
            <button type="button" data-popover-btn="quick"
                    class="h-9 w-9 grid place-items-center rounded-2xl
                           bg-slate-900 hover:bg-slate-800 ring-1 ring-slate-800 transition"
                    aria-label="Quick create">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
            </button>

            <button type="button" data-popover-btn="search"
                    class="h-9 w-9 grid place-items-center rounded-2xl
                           bg-slate-900 hover:bg-slate-800 ring-1 ring-slate-800 transition"
                    aria-label="Search">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                </svg>
            </button>

            <button type="button" data-popover-btn="notif"
                    class="h-9 w-9 grid place-items-center rounded-2xl
                           bg-slate-900 hover:bg-slate-800 ring-1 ring-slate-800 transition"
                    aria-label="Notifications">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
            </button>

            <button type="button" data-popover-btn="profile"
                    class="h-9 w-9 grid place-items-center rounded-2xl
                           bg-slate-900 hover:bg-slate-800 ring-1 ring-slate-800 transition"
                    aria-label="Profile">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 10-16 0"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 100-8 4 4 0 000 8z"/>
                </svg>
            </button>
        </div>
    </div>
</header>


<div id="twTopbarOverlay" class="hidden fixed inset-0 z-[60]"></div>


<div id="pop-company"
     class="hidden fixed z-[70] w-[22rem] rounded-2xl overflow-hidden
            bg-slate-950 ring-1 ring-slate-800 shadow-[0_30px_90px_rgba(0,0,0,.65)]">
    <div class="px-4 py-3 border-b border-slate-800">
        <div class="text-[11px] text-slate-400">Workspace</div>
        <div class="text-[13px] font-semibold truncate"><?php echo e($activeCompanyName); ?></div>
    </div>
    <div class="p-2">
        <a href="<?php echo e(route('companies.switcher')); ?>"
           class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-900 transition">
            <span class="h-9 w-9 rounded-xl grid place-items-center bg-slate-900 ring-1 ring-slate-800">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h16"/>
                </svg>
            </span>
            <div>
                <div class="text-[13px] font-semibold">Switch company</div>
                <div class="text-[11px] text-slate-400">Change workspace</div>
            </div>
        </a>
    </div>
</div>

<div id="pop-quick"
     class="hidden fixed z-[70] w-[18rem] rounded-2xl overflow-hidden
            bg-slate-950 ring-1 ring-slate-800 shadow-[0_30px_90px_rgba(0,0,0,.65)]">
    <div class="px-4 py-3 border-b border-slate-800">
        <div class="text-[13px] font-semibold">Quick create</div>
        <div class="text-[11px] text-slate-400">Common setup</div>
    </div>
    <div class="p-2 grid gap-1">
        <a href="<?php echo e(route('settings.depots.index')); ?>" class="px-3 py-2 rounded-xl hover:bg-slate-900 transition text-[13px]">Depot</a>
        <a href="<?php echo e(route('settings.suppliers.index')); ?>" class="px-3 py-2 rounded-xl hover:bg-slate-900 transition text-[13px]">Supplier</a>
        <a href="<?php echo e(route('settings.transporters.index')); ?>" class="px-3 py-2 rounded-xl hover:bg-slate-900 transition text-[13px]">Transporter</a>
    </div>
</div>

<div id="pop-search"
     class="hidden fixed z-[70] w-[24rem] rounded-2xl overflow-hidden
            bg-slate-950 ring-1 ring-slate-800 shadow-[0_30px_90px_rgba(0,0,0,.65)]">
    <div class="p-3 border-b border-slate-800">
        <input id="twTopbarSearch"
               class="w-full h-10 px-3 rounded-2xl bg-slate-900 ring-1 ring-slate-800
                      text-[13px] placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
               placeholder="Search…"
               autocomplete="off">
    </div>
    <div id="twTopbarSearchList" class="p-2 grid gap-1">
        <a data-key="dashboard summary" href="<?php echo e(route('dashboard')); ?>" class="tw-s-item px-3 py-2 rounded-xl hover:bg-slate-900 transition">
            <div class="text-[13px] font-semibold">Dashboard</div>
            <div class="text-[11px] text-slate-400">Summary</div>
        </a>
        <a data-key="depot stock ago" href="<?php echo e(route('depot-stock.index')); ?>" class="tw-s-item px-3 py-2 rounded-xl hover:bg-slate-900 transition">
            <div class="text-[13px] font-semibold">Depot Stock</div>
            <div class="text-[11px] text-slate-400">Live AGO</div>
        </a>
        <a data-key="depots settings" href="<?php echo e(route('settings.depots.index')); ?>" class="tw-s-item px-3 py-2 rounded-xl hover:bg-slate-900 transition">
            <div class="text-[13px] font-semibold">Depots</div>
            <div class="text-[11px] text-slate-400">Settings</div>
        </a>
    </div>
</div>

<div id="pop-notif"
     class="hidden fixed z-[70] w-[22rem] rounded-2xl overflow-hidden
            bg-slate-950 ring-1 ring-slate-800 shadow-[0_30px_90px_rgba(0,0,0,.65)]">
    <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
        <div>
            <div class="text-[13px] font-semibold">Notifications</div>
            <div class="text-[11px] text-slate-400">Later: approvals, credit, stock alerts</div>
        </div>
        <button class="text-[11px] px-2 py-1 rounded-xl bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
            Mark all
        </button>
    </div>
    <div class="p-3">
        <div class="rounded-2xl bg-slate-900 ring-1 ring-slate-800 p-3">
            <div class="text-[13px] font-semibold">No notifications</div>
            <div class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                This panel will feel like a real app once we wire events.
            </div>
        </div>
    </div>
</div>

<div id="pop-profile"
     class="hidden fixed z-[70] w-[22rem] rounded-2xl overflow-hidden
            bg-slate-950 ring-1 ring-slate-800 shadow-[0_30px_90px_rgba(0,0,0,.65)]">
    <div class="px-4 py-3 border-b border-slate-800">
        <div class="text-[11px] text-slate-400">Signed in</div>
        <div class="text-[13px] font-semibold truncate"><?php echo e($u?->name ?? 'User'); ?></div>
        <div class="text-[11px] text-slate-400 truncate"><?php echo e($u?->email ?? ''); ?></div>
    </div>
    <div class="p-2">
        <a href="<?php echo e(route('settings.company.edit')); ?>"
           class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-900 transition">
            <span class="h-9 w-9 rounded-xl grid place-items-center bg-slate-900 ring-1 ring-slate-800">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                </svg>
            </span>
            <div>
                <div class="text-[13px] font-semibold">Company profile</div>
                <div class="text-[11px] text-slate-400">Edit details</div>
            </div>
        </a>

        <div class="h-px bg-slate-800 my-2"></div>

        <form method="post" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-rose-500/10 transition text-left">
                <span class="h-9 w-9 rounded-xl grid place-items-center bg-slate-900 ring-1 ring-slate-800 text-rose-200">
                    <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 7V5a2 2 0 012-2h7a2 2 0 012 2v14a2 2 0 01-2 2h-7a2 2 0 01-2-2v-2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0l3-3m-3 3l3 3"/>
                    </svg>
                </span>
                <div>
                    <div class="text-[13px] font-semibold">Logout</div>
                    <div class="text-[11px] text-slate-400">End session</div>
                </div>
            </button>
        </form>
    </div>
</div>

<script>
(function(){
    const overlay = document.getElementById('twTopbarOverlay');
    const keys = ['company','quick','search','notif','profile'];

    function panel(key){ return document.getElementById('pop-'+key); }

    function hideAll(){
        keys.forEach(k => panel(k)?.classList.add('hidden'));
        overlay?.classList.add('hidden');
    }

    function place(panelEl, btnEl){
        // FIXED anchoring: always under the button, never “floating in page”
        const gap = 10;
        const br = btnEl.getBoundingClientRect();

        panelEl.style.top = (br.bottom + gap) + 'px';

        // right-align to button, clamp to viewport
        const vw = window.innerWidth;
        const pw = panelEl.offsetWidth || 320;

        let left = br.right - pw;
        if (left < 10) left = 10;
        if (left + pw > vw - 10) left = vw - pw - 10;

        panelEl.style.left = left + 'px';
    }

    function show(key, btn){
        hideAll();
        const p = panel(key);
        if (!p) return;

        p.classList.remove('hidden');
        overlay?.classList.remove('hidden');

        // Must place AFTER it’s visible (so offsetWidth is correct)
        requestAnimationFrame(() => {
            place(p, btn);
            if (key === 'search') document.getElementById('twTopbarSearch')?.focus();
        });
    }

    // Click handlers
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-popover-btn]');
        const inside = e.target.closest('[id^="pop-"]');

        if (!btn && !inside) { hideAll(); return; }

        if (btn) {
            const key = btn.getAttribute('data-popover-btn');
            const p = panel(key);
            if (!p) return;

            const open = !p.classList.contains('hidden');
            if (open) hideAll();
            else show(key, btn);
        }
    });

    overlay?.addEventListener('click', hideAll);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hideAll();
    });

    // Reposition on resize/scroll so it never drifts
    window.addEventListener('resize', hideAll);
    window.addEventListener('scroll', hideAll, true);

    // Search filter
    const input = document.getElementById('twTopbarSearch');
    const list  = document.getElementById('twTopbarSearchList');
    if (input && list) {
        input.addEventListener('input', () => {
            const q = (input.value || '').toLowerCase().trim();
            list.querySelectorAll('.tw-s-item').forEach(a => {
                const key = (a.getAttribute('data-key') || '').toLowerCase();
                a.style.display = (!q || key.includes(q)) ? '' : 'none';
            });
        });
    }
})();
</script><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/topbar.blade.php ENDPATH**/ ?>