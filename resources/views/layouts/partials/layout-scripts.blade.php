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

        // Hide labels when collapsed
        desktopSidebar.querySelectorAll('.sidebar-label').forEach(el => {
            el.classList.toggle('hidden', collapsed);
        });

        // Change toggle tooltip text (data-tip)
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
       Twins Tooltip System (REAL tooltip, portaled to <body>)
       Works for BOTH:
       - data-tip="Text"
       - title="Text"   (auto-migrated into data-tip)
    ============================================================ */
    (function initTwinsTooltips() {
        const tipBox = document.getElementById('twinsTooltip');
        const tipText = document.getElementById('twinsTooltipText');
        if (!tipBox || !tipText) return;

        // Convert any native title tooltips into data-tip (prevents double tooltips)
        document.querySelectorAll('[title]').forEach(el => {
            if (!el.getAttribute('data-tip')) {
                el.setAttribute('data-tip', el.getAttribute('title') || '');
            }
            el.removeAttribute('title');
        });

        let activeEl = null;

        function showTip(el) {
            const text = el.getAttribute('data-tip') || '';
            if (!text.trim()) return;
            activeEl = el;
            tipText.textContent = text;
            tipBox.classList.add('show');
        }

        function hideTip() {
            activeEl = null;
            tipBox.classList.remove('show');
        }

        function moveTip(e) {
            if (!activeEl) return;

            const padding = 14;
            const tipRect = tipBox.getBoundingClientRect();
            const vw = window.innerWidth;
            const vh = window.innerHeight;

            // default: to the right of cursor
            let x = e.clientX + padding;
            let y = e.clientY;

            // if overflow right, flip to left
            if (x + tipRect.width + 8 > vw) {
                x = e.clientX - tipRect.width - padding;
            }

            // clamp vertical
            if (y - tipRect.height / 2 < 8) y = 8 + tipRect.height / 2;
            if (y + tipRect.height / 2 > vh - 8) y = (vh - 8) - tipRect.height / 2;

            tipBox.style.left = `${x}px`;
            tipBox.style.top  = `${y}px`;
        }

        // Use pointer events so it works for mouse + trackpad
        document.addEventListener('pointermove', moveTip, { passive: true });

        // Delegate hover
        document.addEventListener('pointerover', (e) => {
            const el = e.target.closest('[data-tip]');
            if (!el) return;
            showTip(el);
        });

        document.addEventListener('pointerout', (e) => {
            const leaving = e.target.closest('[data-tip]');
            if (!leaving) return;

            // if moving within same element, ignore
            const to = e.relatedTarget && e.relatedTarget.closest ? e.relatedTarget.closest('[data-tip]') : null;
            if (to && to === leaving) return;

            hideTip();
        });

        // Hide on scroll/escape
        window.addEventListener('scroll', hideTip, true);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideTip(); });
    })();
</script>