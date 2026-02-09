@php
    $u = $user ?? auth()->user();

    $activeCompanyName = null;
    if ($u && method_exists($u, 'companies')) {
        $activeCompanyName = $u->companies()
            ->whereKey($u->active_company_id)
            ->value('name');
    }
    $activeCompanyName = $activeCompanyName ?: ($company->name ?? 'Company');

    $title = trim(view()->yieldContent('title', 'Dashboard'));

    /**
     * CLEAN THEME-AWARE BUTTONS
     * Uses your CSS tokens/classes from app.css:
     *  - tw-surface, tw-surface-2
     *  - tw-icon-btn, tw-pill, tw-muted, tw-accent-ring
     */
    $iconBtn = "tw-icon-btn cursor-pointer select-none h-8 w-8 md:h-9 md:w-9 grid place-items-center rounded-2xl";
    $pillBtn = "tw-pill cursor-pointer select-none h-8 md:h-9 flex items-center gap-2 pl-2 pr-2.5 rounded-2xl";

    // Popover container uses token surfaces (no blur)
    $popover = "hidden fixed z-[70] rounded-2xl overflow-hidden tw-surface w-[22rem] max-w-[calc(100vw-24px)]";

    // Popover item hover is done via inline token-friendly Tailwind (no dark: needed)
    $popItem = "cursor-pointer select-none px-3 py-2 rounded-xl transition hover:bg-[color:var(--tw-surface-2)]";
@endphp

<header class="sticky top-0 z-50">
    {{-- CLEAN TOPBAR (no blur, no opacity) --}}
    <div class="tw-surface border-b border-[color:var(--tw-border)] px-4 md:px-6 py-2.5 md:py-3 flex items-center justify-between gap-3 md:gap-4">

        {{-- LEFT --}}
        <div class="min-w-0 flex items-center gap-3">

            {{-- Mobile hamburger (drawer open) --}}
            <button id="openMenu"
                    type="button"
                    class="tw-icon-btn cursor-pointer select-none md:hidden h-9 w-9 grid place-items-center rounded-xl"
                    aria-label="Open menu"
                    data-mobile-menu-open>
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Title --}}
            <div class="min-w-0 hidden md:block">
                <div class="text-[15px] font-semibold tracking-tight truncate">
                    {{ $title }}
                </div>
                <div class="text-[11px] tw-muted truncate">
                    @yield('subtitle')
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <div class="flex items-center gap-1.5 md:gap-2">

            {{-- Company pill --}}
            <button type="button"
                    data-popover-btn="company"
                    class="{{ $pillBtn }}"
                    aria-label="Company menu">
                <span class="h-6 w-6 rounded-xl grid place-items-center bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                    <span class="h-2 w-2 rounded-full bg-[color:var(--tw-accent)]"></span>
                </span>

                <span class="text-[12px] font-semibold truncate max-w-[120px] md:max-w-[240px]">
                    {{ $activeCompanyName }}
                </span>

                <svg class="w-4 h-4 tw-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
            </button>

            {{-- Theme toggle --}}
            <button type="button"
                    data-theme-toggle
                    class="{{ $iconBtn }}"
                    aria-label="Toggle theme">

                {{-- Moon (show in LIGHT) --}}
                <svg data-icon="moon" class="w-4 h-4 md:w-[17px] md:h-[17px]"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
                </svg>

                {{-- Sun (show in DARK) --}}
                <svg data-icon="sun" class="w-4 h-4 md:w-[17px] md:h-[17px] hidden"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
                </svg>
            </button>

            <button type="button" data-popover-btn="quick" class="{{ $iconBtn }}" aria-label="Quick create">
                <svg class="w-4 h-4 md:w-[17px] md:h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                </svg>
            </button>

            <button type="button" data-popover-btn="search" class="{{ $iconBtn }}" aria-label="Search">
                <svg class="w-4 h-4 md:w-[17px] md:h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                </svg>
            </button>

            <button type="button" data-popover-btn="notif" class="{{ $iconBtn }}" aria-label="Notifications">
                <svg class="w-4 h-4 md:w-[17px] md:h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
            </button>

            <button type="button" data-popover-btn="profile" class="{{ $iconBtn }}" aria-label="Profile">
                <svg class="w-4 h-4 md:w-[17px] md:h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 10-16 0"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 100-8 4 4 0 000 8z"/>
                </svg>
            </button>
        </div>
    </div>
</header>

{{-- overlay for closing popovers --}}
<div id="twTopbarOverlay" class="hidden fixed inset-0 z-[60] bg-black/20"></div>

{{-- DEBUG (remove later): shows actual html.dark state --}}

{{-- POP: Company --}}
<div id="pop-company" class="{{ $popover }}">
    <div class="px-4 py-3 border-b border-[color:var(--tw-border)]">
        <div class="text-[11px] tw-muted">Workspace</div>
        <div class="text-[13px] font-semibold truncate">{{ $activeCompanyName }}</div>
    </div>
    <div class="p-2">
        <a href="{{ route('companies.switcher') }}" class="flex items-center gap-3 {{ $popItem }}">
            <span class="h-9 w-9 rounded-xl grid place-items-center bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h16"/>
                </svg>
            </span>
            <div>
                <div class="text-[13px] font-semibold">Switch company</div>
                <div class="text-[11px] tw-muted">Change workspace</div>
            </div>
        </a>
    </div>
</div>

{{-- POP: Quick --}}
<div id="pop-quick" class="{{ str_replace('w-[22rem]', 'w-[18rem]', $popover) }}">
    <div class="px-4 py-3 border-b border-[color:var(--tw-border)]">
        <div class="text-[13px] font-semibold">Quick create</div>
        <div class="text-[11px] tw-muted">Common setup</div>
    </div>
    <div class="p-2 grid gap-1">
        <a href="{{ route('settings.depots.index') }}" class="{{ $popItem }} text-[13px]">Depot</a>
        <a href="{{ route('settings.suppliers.index') }}" class="{{ $popItem }} text-[13px]">Supplier</a>
        <a href="{{ route('settings.transporters.index') }}" class="{{ $popItem }} text-[13px]">Transporter</a>
    </div>
</div>

{{-- POP: Search --}}
<div id="pop-search" class="{{ str_replace('w-[22rem]', 'w-[24rem]', $popover) }}">
    <div class="p-3 border-b border-[color:var(--tw-border)]">
        <input id="twTopbarSearch"
               class="w-full h-10 px-3 rounded-2xl bg-[color:var(--tw-bg)]
                      border border-[color:var(--tw-border)]
                      text-[13px] text-[color:var(--tw-fg)]
                      placeholder:text-[color:var(--tw-muted)]
                      focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
               placeholder="Search…"
               autocomplete="off">
    </div>
    <div id="twTopbarSearchList" class="p-2 grid gap-1">
        <a data-key="dashboard summary" href="{{ route('dashboard') }}" class="tw-s-item {{ $popItem }}">
            <div class="text-[13px] font-semibold">Dashboard</div>
            <div class="text-[11px] tw-muted">Summary</div>
        </a>
        <a data-key="depot stock ago" href="{{ route('depot-stock.index') }}" class="tw-s-item {{ $popItem }}">
            <div class="text-[13px] font-semibold">Depot Stock</div>
            <div class="text-[11px] tw-muted">Live AGO</div>
        </a>
        <a data-key="depots settings" href="{{ route('settings.depots.index') }}" class="tw-s-item {{ $popItem }}">
            <div class="text-[13px] font-semibold">Depots</div>
            <div class="text-[11px] tw-muted">Settings</div>
        </a>
    </div>
</div>

{{-- POP: Notifications --}}
<div id="pop-notif" class="{{ $popover }}">
    <div class="px-4 py-3 border-b border-[color:var(--tw-border)] flex items-center justify-between">
        <div>
            <div class="text-[13px] font-semibold">Notifications</div>
            <div class="text-[11px] tw-muted">Later: approvals, credit, stock alerts</div>
        </div>
        <button type="button"
                class="tw-pill cursor-pointer select-none text-[11px] px-2 py-1 rounded-xl">
            Mark all
        </button>
    </div>
    <div class="p-3">
        <div class="rounded-2xl p-3 bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
            <div class="text-[13px] font-semibold">No notifications</div>
            <div class="text-[11px] tw-muted mt-1 leading-relaxed">
                This panel will feel like a real app once we wire events.
            </div>
        </div>
    </div>
</div>

{{-- POP: Profile --}}
<div id="pop-profile" class="{{ $popover }}">
    <div class="px-4 py-3 border-b border-[color:var(--tw-border)]">
        <div class="text-[11px] tw-muted">Signed in</div>
        <div class="text-[13px] font-semibold truncate">{{ $u?->name ?? 'User' }}</div>
        <div class="text-[11px] tw-muted truncate">{{ $u?->email ?? '' }}</div>
    </div>
    <div class="p-2">
        <a href="{{ route('settings.company.edit') }}" class="flex items-center gap-3 {{ $popItem }}">
            <span class="h-9 w-9 rounded-xl grid place-items-center bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                <svg class="w-[17px] h-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 20h9"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.5a2.1 2 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                </svg>
            </span>
            <div>
                <div class="text-[13px] font-semibold">Company profile</div>
                <div class="text-[11px] tw-muted">Edit details</div>
            </div>
        </a>

        <div class="h-px bg-[color:var(--tw-border)] my-2"></div>

        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="cursor-pointer select-none w-full flex items-center gap-3 {{ $popItem }} text-left">
                <span class="h-9 w-9 rounded-xl grid place-items-center bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)] text-rose-500">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 20H6a2 2 0 01-2-2V6a2 2 0 012-2h7"/>
                    </svg>
                </span>
                <div>
                    <div class="text-[13px] font-semibold">Logout</div>
                    <div class="text-[11px] tw-muted">End session</div>
                </div>
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('twTopbarOverlay');
    const keys = ['company', 'quick', 'search', 'notif', 'profile'];

    function panel(key) { return document.getElementById('pop-' + key); }
    function hideAll() {
        keys.forEach(k => panel(k)?.classList.add('hidden'));
        overlay?.classList.add('hidden');
    }

    function place(panelEl, btnEl) {
        const gap = 10;
        const br = btnEl.getBoundingClientRect();
        panelEl.style.top = (br.bottom + gap) + 'px';

        const vw = window.innerWidth;
        const pw = panelEl.offsetWidth || 320;

        let left = br.right - pw;
        if (left < 12) left = 12;
        if (left + pw > vw - 12) left = vw - pw - 12;

        panelEl.style.left = left + 'px';
    }

    function show(key, btn) {
        hideAll();
        const p = panel(key);
        if (!p) return;

        p.classList.remove('hidden');
        overlay?.classList.remove('hidden');

        requestAnimationFrame(() => {
            place(p, btn);
            if (key === 'search') document.getElementById('twTopbarSearch')?.focus();
        });
    }

    /* -------------------------------
       THEME (respects system by default)
       - If localStorage has 'tw-theme' => force that
       - Else => follow system preference + live-update on system change
    --------------------------------*/
    const THEME_KEY = 'tw-theme';
    const root = document.documentElement;
    const mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function getStoredTheme() {
        try { return localStorage.getItem(THEME_KEY); } catch (e) { return null; }
    }

    function setStoredTheme(val) {
        try { localStorage.setItem(THEME_KEY, val); } catch (e) {}
    }

    function clearStoredTheme() {
        try { localStorage.removeItem(THEME_KEY); } catch (e) {}
    }

    function applyDark(isDark, animate = false) {
        root.classList.toggle('dark', !!isDark);

        if (animate) {
            root.classList.add('theme-anim');
            setTimeout(() => root.classList.remove('theme-anim'), 1200);
        }

        syncThemeUI();
    }

    function applyInitialTheme() {
        const saved = getStoredTheme(); // 'dark' | 'light' | null
        if (saved === 'dark') return applyDark(true, false);
        if (saved === 'light') return applyDark(false, false);

        // No saved preference => follow system
        const sysDark = !!(mql && mql.matches);
        applyDark(sysDark, false);
    }

    function syncThemeUI() {
        const isDark = root.classList.contains('dark');
        const btn = document.querySelector('[data-theme-toggle]');
        if (btn) {
            btn.querySelector('[data-icon="moon"]')?.classList.toggle('hidden', isDark);
            btn.querySelector('[data-icon="sun"]')?.classList.toggle('hidden', !isDark);
            btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        }

      
    }

    // Initial paint theme (best effort here; ideal is in <head> too)
    applyInitialTheme();

    // If user has NOT chosen a theme, react to system changes
    function onSystemThemeChange() {
        const saved = getStoredTheme();
        if (saved === 'dark' || saved === 'light') return; // user override stays
        applyDark(!!(mql && mql.matches), false);
    }

    if (mql) {
        // Safari < 14 uses addListener
        if (typeof mql.addEventListener === 'function') mql.addEventListener('change', onSystemThemeChange);
        else if (typeof mql.addListener === 'function') mql.addListener(onSystemThemeChange);
    }

    // Toggle button: explicitly sets a user preference (overrides system)
    document.addEventListener('click', (e) => {
        const hit = e.target.closest('[data-theme-toggle]');
        if (!hit) return;

        e.preventDefault();
        e.stopPropagation();

        const isDarkNow = root.classList.contains('dark');
        const nextDark = !isDarkNow;

        setStoredTheme(nextDark ? 'dark' : 'light');
        applyDark(nextDark, true);
        hideAll();
    }, true);

    /* -------------------------------
       MOBILE SIDEBAR — SINGLE SOURCE OF TRUTH
    --------------------------------*/
    const mobileSidebar = document.getElementById('mobileSidebar');
    const mobileOverlay = document.getElementById('mobileSidebarOverlay');

    function mobileIsOpen() {
        return mobileSidebar && !mobileSidebar.classList.contains('-translate-x-full');
    }

    function mobileOpen() {
        if (!mobileSidebar || !mobileOverlay) return;
        mobileSidebar.classList.remove('-translate-x-full');
        mobileOverlay.classList.remove('hidden');
        document.getElementById('openMenu')?.setAttribute('aria-expanded', 'true');
    }

    function mobileClose() {
        if (!mobileSidebar || !mobileOverlay) return;
        mobileSidebar.classList.add('-translate-x-full');
        mobileOverlay.classList.add('hidden');
        document.getElementById('openMenu')?.setAttribute('aria-expanded', 'false');
    }

    function mobileToggle() {
        mobileIsOpen() ? mobileClose() : mobileOpen();
    }

    document.addEventListener('click', (e) => {
        // Toggle with the SAME burger
        if (e.target.closest('[data-mobile-menu-open]')) {
            e.preventDefault();
            e.stopPropagation();
            hideAll();
            mobileToggle();
            return;
        }

        // Close button inside sidebar
        if (e.target.closest('[data-mobile-menu-close]')) {
            e.preventDefault();
            e.stopPropagation();
            mobileClose();
            return;
        }

        // Overlay click closes
        if (mobileOverlay && (e.target === mobileOverlay)) {
            e.preventDefault();
            mobileClose();
            return;
        }
    }, true);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideAll();
            mobileClose();
        }
    });

    /* -------------------------------
       POPOVERS
    --------------------------------*/
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-popover-btn]');
        const inside = e.target.closest('[id^="pop-"]');
        const isTheme = !!e.target.closest('[data-theme-toggle]');
        const isMobileMenuBtn = !!e.target.closest('[data-mobile-menu-open]');

        // ignore the burger here; handled above
        if (isMobileMenuBtn) return;

        if (!btn && !inside && !isTheme) { hideAll(); return; }

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
    window.addEventListener('resize', hideAll);
    window.addEventListener('scroll', hideAll, true);

    /* -------------------------------
       SEARCH FILTER
    --------------------------------*/
    const input = document.getElementById('twTopbarSearch');
    const list = document.getElementById('twTopbarSearchList');
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
</script>