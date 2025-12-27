<script>
    // Mobile sidebar
    const openMenu = document.getElementById('openMenu');
    const closeMenu = document.getElementById('closeMenu');
    const mobileSidebar = document.getElementById('mobileSidebar');

    openMenu?.addEventListener('click', () => mobileSidebar.classList.remove('-translate-x-full'));
    closeMenu?.addEventListener('click', () => mobileSidebar.classList.add('-translate-x-full'));

    // Desktop accordions
    function toggleSettingsDesktop() {
        const box = document.getElementById('settingsLinksDesktop');
        const caret = document.getElementById('settingsCaretDesktop');
        if (!box || !caret) return;
        box.classList.toggle('hidden');
        caret.textContent = box.classList.contains('hidden') ? '▸' : '▾';
    }
    function toggleUserSettingsDesktop() {
        const box = document.getElementById('userSettingsLinksDesktop');
        const caret = document.getElementById('userSettingsCaretDesktop');
        if (!box || !caret) return;
        box.classList.toggle('hidden');
        caret.textContent = box.classList.contains('hidden') ? '▸' : '▾';
    }

    // Mobile accordions
    function toggleSettingsMobile() {
        const box = document.getElementById('settingsLinksMobile');
        const caret = document.getElementById('settingsCaretMobile');
        if (!box || !caret) return;
        box.classList.toggle('hidden');
        caret.textContent = box.classList.contains('hidden') ? '▸' : '▾';
    }
    function toggleUserSettingsMobile() {
        const box = document.getElementById('userSettingsLinksMobile');
        const caret = document.getElementById('userSettingsCaretMobile');
        if (!box || !caret) return;
        box.classList.toggle('hidden');
        caret.textContent = box.classList.contains('hidden') ? '▸' : '▾';
    }

    // Desktop sidebar collapse (persist)
    const desktopSidebar = document.getElementById('desktopSidebar');
    const toggleDesktopSidebar = document.getElementById('toggleDesktopSidebar');

    function setSidebarCollapsed(collapsed) {
        if (!desktopSidebar) return;

        desktopSidebar.classList.toggle('w-64', !collapsed);
        desktopSidebar.classList.toggle('w-20', collapsed);
        desktopSidebar.classList.toggle('is-collapsed', collapsed);

        desktopSidebar.querySelectorAll('.sidebar-label').forEach(el => {
            el.classList.toggle('hidden', collapsed);
        });

        if (toggleDesktopSidebar) {
            toggleDesktopSidebar.setAttribute('data-tip', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggleDesktopSidebar.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }

        localStorage.setItem('twins_sidebar_collapsed', collapsed ? '1' : '0');
    }

    const collapsed = localStorage.getItem('twins_sidebar_collapsed') === '1';
    setSidebarCollapsed(collapsed);

    toggleDesktopSidebar?.addEventListener('click', () => {
        const nowCollapsed = !(localStorage.getItem('twins_sidebar_collapsed') === '1');
        setSidebarCollapsed(nowCollapsed);
    });

    /* ============================================================
       Twins Tooltip System (quiet, tiny, NOT cursor-follow)
       - Uses data-tip only (no title migration)
       - Positions relative to element box (tw-tip-b / tw-tip-r)
    ============================================================ */
    (function initTwinsTooltips() {
        const tipBox  = document.getElementById('twinsTooltip');
        const tipText = document.getElementById('twinsTooltipText');
        if (!tipBox || !tipText) return;

        let activeEl = null;

        function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

        function position(el) {
            const rect = el.getBoundingClientRect();
            const vw = window.innerWidth;
            const vh = window.innerHeight;

            const pad = 8;

            // ensure tip measured
            const tipRect = tipBox.getBoundingClientRect();
            const isBottom = el.classList.contains('tw-tip-b');

            let x, y;

            if (isBottom) {
                x = rect.left + (rect.width / 2) - (tipRect.width / 2);
                y = rect.bottom + pad;
                x = clamp(x, 8, vw - tipRect.width - 8);

                // flip upward if bottom overflows
                if (y + tipRect.height > vh - 8) {
                    y = rect.top - tipRect.height - pad;
                }
            } else {
                // default: right
                x = rect.right + pad;
                y = rect.top + (rect.height / 2) - (tipRect.height / 2);

                // flip left if overflow
                if (x + tipRect.width > vw - 8) {
                    x = rect.left - tipRect.width - pad;
                }

                y = clamp(y, 8, vh - tipRect.height - 8);
            }

            tipBox.style.left = `${x}px`;
            tipBox.style.top  = `${y}px`;
        }

        function show(el) {
            const text = (el.getAttribute('data-tip') || '').trim();
            if (!text) return;

            activeEl = el;
            tipText.textContent = text;
            tipBox.classList.add('show');

            // wait a tick so size is correct
            requestAnimationFrame(() => {
                if (!activeEl) return;
                position(activeEl);
            });
        }

        function hide() {
            activeEl = null;
            tipBox.classList.remove('show');
        }

        document.addEventListener('pointerover', (e) => {
            const el = e.target.closest('[data-tip]');
            if (!el) return;
            show(el);
        });

        document.addEventListener('pointerout', (e) => {
            const leaving = e.target.closest('[data-tip]');
            if (!leaving) return;

            const to = e.relatedTarget && e.relatedTarget.closest ? e.relatedTarget.closest('[data-tip]') : null;
            if (to && to === leaving) return;

            hide();
        });

        window.addEventListener('scroll', hide, true);
        window.addEventListener('resize', () => { if (activeEl) position(activeEl); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hide(); });
    })();
</script>