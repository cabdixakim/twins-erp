<header class="sticky top-0 z-40 flex items-center justify-between px-4 md:px-6 py-3
               border-b border-slate-800/70 bg-slate-950/60 backdrop-blur">

    <div class="min-w-0 flex items-center gap-3">
        {{-- Mobile menu button (ONLY on mobile) --}}
        <button id="openMenu"
                class="md:hidden h-9 w-9 grid place-items-center rounded-lg border border-slate-800
                       bg-slate-900/50 hover:bg-slate-800 transition"
                aria-label="Open menu">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="min-w-0">
            <div class="text-sm md:text-base font-semibold truncate">@yield('title','Dashboard')</div>
            <div class="hidden md:block text-xs text-slate-400 truncate">@yield('subtitle')</div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button class="tw-tip-b h-9 w-9 grid place-items-center rounded-lg border border-slate-800
                       bg-slate-900/50 hover:bg-slate-800 transition"
                data-tip="Quick create" aria-label="Quick create">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
        </button>

        <button class="tw-tip-b h-9 w-9 grid place-items-center rounded-lg border border-slate-800
                       bg-slate-900/50 hover:bg-slate-800 transition"
                data-tip="Search" aria-label="Search">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
            </svg>
        </button>

        <button class="tw-tip-b h-9 w-9 grid place-items-center rounded-lg border border-slate-800
                       bg-slate-900/50 hover:bg-slate-800 transition"
                data-tip="Notifications" aria-label="Notifications">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
        </button>

        <button class="tw-tip-b h-9 w-9 grid place-items-center rounded-lg border border-slate-800
                       bg-slate-900/50 hover:bg-slate-800 transition"
                data-tip="Profile" aria-label="Profile">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 10-16 0"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 100-8 4 4 0 000 8z"/>
            </svg>
        </button>
    </div>
</header>