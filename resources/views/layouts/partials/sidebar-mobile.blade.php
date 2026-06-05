{{-- Overlay — shown/hidden via JS (topbar script) --}}
<div id="mobileSidebarOverlay"
     class="hidden fixed inset-0 z-40 bg-black/50 md:hidden">
</div>

<aside id="mobileSidebar"
       class="fixed top-0 left-0 z-50 h-full w-72 md:hidden flex flex-col
              -translate-x-full transition-transform duration-200 ease-out
              tw-surface">

    {{-- Header --}}
    <div class="px-4 py-4 flex items-center justify-between border-b"
         style="border-color: var(--tw-border);">

        @include('layouts.partials.brand', compact('company'))

        <button
            type="button"
            data-mobile-menu-close
            class="tw-icon-btn h-9 w-9 grid place-items-center rounded-xl"
            aria-label="Close menu">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 text-sm">
        @include('layouts.partials.nav-primary', compact('onDashboard','onDepotStock','onPurchases','onSales','onClients'))
        @include('layouts.partials.nav-settings', compact('user','userRole','onSettingsRoute'))
    </nav>

    {{-- Footer --}}
    <div class="px-3 pb-2 pt-3 border-t space-y-2" style="border-color: var(--tw-border);">

        {{-- Company switcher row --}}
        <a href="{{ route('companies.switcher') }}"
           class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-[12px] font-medium transition"
           style="background: var(--tw-surface-2);">
            <span class="h-6 w-6 rounded-lg grid place-items-center" style="background: var(--tw-btn); border: 1px solid var(--tw-border);">
                <span class="h-2 w-2 rounded-full" style="background: var(--tw-accent);"></span>
            </span>
            <span class="flex-1 truncate">{{ $activeCompanyName ?? ($company->name ?? 'Company') }}</span>
            <svg class="w-4 h-4 tw-muted flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        {{-- Theme toggle row --}}
        <button type="button"
                data-theme-toggle
                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-[12px] font-medium transition"
                style="background: var(--tw-surface-2);">
            <span class="h-6 w-6 rounded-lg grid place-items-center" style="background: var(--tw-btn); border: 1px solid var(--tw-border);">
                <svg data-icon="moon" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
                </svg>
                <svg data-icon="sun" class="w-3.5 h-3.5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
                </svg>
            </span>
            <span class="flex-1 text-left">Toggle theme</span>
        </button>

        {{-- Logout --}}
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button
                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl border text-[12px] font-medium transition
                       hover:bg-rose-600 hover:text-white"
                style="background: var(--tw-btn); border-color: var(--tw-border);">
                <span class="h-6 w-6 grid place-items-center rounded-lg" style="background: var(--tw-surface-2);">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                        <path d="M3 21V3a2 2 0 012-2h6"/>
                    </svg>
                </span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>