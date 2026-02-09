{{-- resources/views/layouts/partials/sidebar-desktop.blade.php --}}

<aside id="desktopSidebar"
       class="sidebar w-64 hidden md:flex flex-col tw-surface">

    {{-- Header --}}
    <div class="px-4 py-4 flex items-center justify-between border-b"
         style="border-color: var(--tw-border);">

        {{-- Sexy company/brand block (theme-aware) --}}
        <div class="flex items-center gap-3 min-w-0">
            @if($company && $company->logo_path)
                <img src="{{ asset('storage/'.$company->logo_path) }}"
                     class="w-10 h-10 rounded-xl object-cover border shadow"
                     style="border-color: var(--tw-border); box-shadow: var(--tw-shadow);">
            @else
                <div class="w-10 h-10 rounded-xl"
                     style="background: linear-gradient(135deg, var(--tw-accent), #06b6d4); box-shadow: var(--tw-shadow);">
                </div>
            @endif

            {{-- When sidebar collapses, we hide this block --}}
            <div class="min-w-0 sidebar-label">
                <div class="font-semibold text-[12px] uppercase tracking-[0.12em] truncate"
                     style="color: var(--tw-fg);">
                    {{ $company->name ?? 'Twins ERP' }}
                </div>
                <div class="text-[11px] truncate"
                     style="color: var(--tw-muted);">
                    Fuel &amp; Transport ERP
                </div>
            </div>
        </div>

        {{-- Collapse button (NO tooltip) --}}
        <button type="button"
                id="toggleDesktopSidebar"
                class="tw-icon-btn h-8 w-8 grid place-items-center rounded-xl"
                aria-label="Toggle sidebar">
            <svg id="sidebarToggleIcon"
                 class="w-4 h-4 transition-transform duration-200"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <div class="flex-1 overflow-hidden">
        <div class="h-full overflow-y-auto px-3 py-4 space-y-4 text-sm">
            @include('layouts.partials.nav-primary', compact('onDashboard','onDepotStock', 'onPurchases'))
            @include('layouts.partials.nav-settings', compact('user','userRole','onSettingsRoute'))
        </div>
    </div>

    {{-- Logout --}}
    <form method="post"
          action="{{ route('logout') }}"
          class="px-3 py-3 border-t"
          style="border-color: var(--tw-border);">
        @csrf
        <button type="submit"
                class="group w-full flex items-center gap-3 px-3 py-2 rounded-2xl
                    text-xs font-medium transition
                    border border-[color:var(--tw-border)]
                    bg-[color:var(--tw-btn)]
                    text-[color:var(--tw-fg)]
                    hover:bg-rose-600/90 hover:border-rose-500/60 hover:text-white">

            {{-- Icon --}}
            <span class="h-9 w-9 grid place-items-center rounded-xl
                        border border-[color:var(--tw-border)]
                        bg-[color:var(--tw-surface-2)]
                        transition
                        group-hover:bg-white/10 group-hover:border-white/20">

                <svg class="w-5 h-5 transition"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M17 16l4-4m0 0l-4-4m4 4H9"/>
                    <path d="M13 20H6a2 2 0 01-2-2V6a2 2 0 012-2h7"/>
                </svg>
            </span>

            {{-- Label --}}
            <span class="sidebar-label tracking-wide">
                Logout
            </span>
        </button>
    </form>

</aside>