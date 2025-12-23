<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Twins - @yield('title','Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .sidebar { transition: all 0.25s ease-in-out; }

        /*
         |==========================================================
         | ONE Tooltip System (PORTALED, no clipping, no title="")
         |==========================================================
         | Use: data-tip="Text" on any element
         | Class: tw-tip-r (right) or tw-tip-b (bottom)
         */
        #twinsTooltip {
            position: fixed;
            z-index: 999999;
            pointer-events: none;
            opacity: 0;
            transform: translateY(-6px);
            transition: opacity .12s ease, transform .12s ease;
        }
        #twinsTooltip.show {
            opacity: 1;
            transform: translateY(0);
        }
        #twinsTooltip .tip {
            background: rgba(15, 23, 42, 0.98);
            color: rgb(226 232 240);
            border: 1px solid rgba(51, 65, 85, 0.9);
            box-shadow: 0 16px 45px rgba(0,0,0,.45);
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 12px;
            line-height: 1;
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        /* Collapsed sidebar: tighter logout */
        #desktopSidebar.is-collapsed .logout-btn{
            padding: 8px;
            justify-content: center;
            border-radius: 14px;
        }
        #desktopSidebar.is-collapsed .logout-icon{
            width: 38px;
            height: 38px;
            border-radius: 12px;
        }
    </style>
</head>

<body class="bg-slate-950 text-slate-100 h-full flex overflow-hidden">

@php
    $user            = auth()->user();
    $userRole        = $user?->role?->slug;
    $company         = \App\Models\Company::first();

    $canManageUsers  = in_array($userRole, ['owner','manager'], true);

    $onDashboard     = request()->routeIs('dashboard');
    $onDepotStock    = request()->routeIs('depot-stock.*');
    $onSettingsRoute = request()->routeIs('settings.*') || request()->is('admin/*');
@endphp

@include('layouts.partials.sidebar-desktop', compact(
    'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onSettingsRoute'
))

@include('layouts.partials.sidebar-mobile', compact(
    'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onSettingsRoute'
))

<div class="flex-1 min-w-0 flex flex-col">
    @include('layouts.partials.topbar', compact(
        'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onSettingsRoute'
    ))

    <main class="flex-1 overflow-y-auto p-6 md:p-8">
        @yield('content')
    </main>
</div>

{{-- Tooltip portal (ALWAYS ABOVE EVERYTHING) --}}
<div id="twinsTooltip" class="">
    <div class="tip" id="twinsTooltipText"></div>
</div>

@include('layouts.partials.layout-scripts')

<script>
/*
 |==========================================================
 | Tooltip Engine (single source of truth)
 |==========================================================
 | - uses data-tip="" only
 | - removes title="" to avoid double tooltips
 | - positions right or bottom based on class:
 |   .tw-tip-r  (right of cursor)
 |   .tw-tip-b  (below cursor)
 */
(function initTwinsTooltips(){
    const tipBox  = document.getElementById('twinsTooltip');
    const tipText = document.getElementById('twinsTooltipText');
    if (!tipBox || !tipText) return;

    // Convert any native title tooltips into data-tip, then remove title
    document.querySelectorAll('[title]').forEach(el => {
        if (!el.getAttribute('data-tip')) {
            const t = el.getAttribute('title') || '';
            if (t.trim()) el.setAttribute('data-tip', t);
        }
        el.removeAttribute('title');
    });

    let activeEl = null;

    function show(el){
        const text = el.getAttribute('data-tip') || '';
        if (!text.trim()) return;
        activeEl = el;
        tipText.textContent = text;
        tipBox.classList.add('show');
    }

    function hide(){
        activeEl = null;
        tipBox.classList.remove('show');
    }

    function move(e){
        if (!activeEl) return;

        const pad = 14;
        const rect = tipBox.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        const isBottom = activeEl.classList.contains('tw-tip-b'); // else right

        let x, y;

        if (isBottom) {
            x = e.clientX - rect.width / 2;
            y = e.clientY + pad;
            // clamp
            if (x < 8) x = 8;
            if (x + rect.width > vw - 8) x = vw - rect.width - 8;
            if (y + rect.height > vh - 8) y = e.clientY - rect.height - pad;
        } else {
            x = e.clientX + pad;
            y = e.clientY - rect.height / 2;
            // flip left if needed
            if (x + rect.width > vw - 8) x = e.clientX - rect.width - pad;
            // clamp vertical
            if (y < 8) y = 8;
            if (y + rect.height > vh - 8) y = vh - rect.height - 8;
        }

        tipBox.style.left = `${x}px`;
        tipBox.style.top  = `${y}px`;
    }

    document.addEventListener('pointermove', move, { passive: true });

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
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hide(); });
})();
</script>

</body>
</html>