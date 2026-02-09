<script>

(() => {
  /* -------------------------------
     MOBILE SIDEBAR (open/close)
  --------------------------------*/
  const openMenu = document.getElementById('openMenu');
  const mobileSidebar = document.getElementById('mobileSidebar');
  const mobileOverlay = document.getElementById('mobileSidebarOverlay');

  const closeMobile = () => {
    mobileSidebar?.classList.add('-translate-x-full');
    mobileOverlay?.classList.add('hidden'); // safe if you use it
  };

  const openMobile = () => {
    mobileSidebar?.classList.remove('-translate-x-full');
    mobileOverlay?.classList.remove('hidden'); // safe if you use it
  };

  openMenu?.addEventListener('click', (e) => {
    e.preventDefault();
    openMobile();
  });

  // Your close button is: <button data-mobile-close ...>
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-mobile-close]')) {
      e.preventDefault();
      closeMobile();
    }

    // Optional: click overlay to close (only if overlay exists)
    if (mobileOverlay && e.target === mobileOverlay) {
      closeMobile();
    }
  });

  /* -------------------------------
     DESKTOP SIDEBAR COLLAPSE (persist)
  --------------------------------*/
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

  /* -------------------------------
     ACCORDIONS (desktop + mobile)
     data-acc-toggle="settings"
     data-acc-panel="settings"
     data-acc-caret
  --------------------------------*/
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-acc-toggle]');
    if (!btn) return;

    // Only toggle within the same sidebar root
    const sidebarRoot = btn.closest('#desktopSidebar, #mobileSidebar');
    if (!sidebarRoot) return;

    const key = btn.getAttribute('data-acc-toggle');
    const panel = sidebarRoot.querySelector(`[data-acc-panel="${key}"]`);
    if (!panel) return;

    panel.classList.toggle('hidden');

    const caret = btn.querySelector('[data-acc-caret]');
    if (caret) caret.textContent = panel.classList.contains('hidden') ? '▸' : '▾';
  });

  /* -------------------------------
     TOOLTIP SYSTEM (unchanged)
  --------------------------------*/
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
      const tipRect = tipBox.getBoundingClientRect();
      const isBottom = el.classList.contains('tw-tip-b');

      let x, y;

      if (isBottom) {
        x = rect.left + (rect.width / 2) - (tipRect.width / 2);
        y = rect.bottom + pad;
        x = clamp(x, 8, vw - tipRect.width - 8);

        if (y + tipRect.height > vh - 8) {
          y = rect.top - tipRect.height - pad;
        }
      } else {
        x = rect.right + pad;
        y = rect.top + (rect.height / 2) - (tipRect.height / 2);

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
})();
</script>