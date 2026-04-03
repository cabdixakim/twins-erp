{{-- resources/views/layouts/partials/nav-settings.blade.php --}}
@php
    $sectionWrap = "pt-3 mt-2 border-t border-[color:var(--tw-border)]";

    $accBtnBase =
        "w-full px-3 py-2 rounded-2xl flex items-center justify-between transition
         border border-[color:var(--tw-border)]
         bg-[color:var(--tw-surface)] hover:bg-[color:var(--tw-surface-2)]
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

    $accBtnActive =
        "bg-[linear-gradient(90deg,var(--tw-accent-soft),transparent)]
         border-[color:rgba(16,185,129,.45)]";

    $linkBase =
        "flex items-center gap-2.5 px-3 py-2 rounded-2xl text-[12px] transition
         border border-transparent hover:bg-[color:var(--tw-surface-2)]
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

    $linkActive =
        "bg-[color:var(--tw-surface-2)] border border-[color:rgba(16,185,129,.30)]";

    $iconWrap =
        "h-6 w-6 rounded-xl grid place-items-center flex-shrink-0
         bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]";

    $muted = "tw-muted";
@endphp

<div class="{{ $sectionWrap }}">

    {{-- SETTINGS accordion header --}}
    <button type="button"
            class="{{ $accBtnBase }} {{ $onSettingsRoute ? $accBtnActive : '' }}"
            data-acc-toggle="settings">
        <span class="flex items-center gap-2.5 min-w-0">
            <span class="{{ $iconWrap }}">
                <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <circle stroke-linecap="round" stroke-linejoin="round" cx="12" cy="12" r="3"/>
                </svg>
            </span>
            <span class="tracking-wide uppercase text-[11px] font-semibold sidebar-label truncate text-[color:var(--tw-muted)]">
                Settings
            </span>
        </span>
        <svg data-acc-caret class="w-3 h-3 {{ $muted }} transition-transform sidebar-label {{ $onSettingsRoute ? 'rotate-90' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
        </svg>
    </button>

    {{-- SETTINGS LINKS --}}
    <div class="mt-1.5 space-y-0.5 pl-2" data-acc-panel="settings">

        {{-- Depots --}}
        @if($user && $user->hasPermission('depots.view'))
            <a href="{{ route('settings.depots.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('settings.depots.*') ? $linkActive : '' }}">
                <span class="{{ $iconWrap }}">
                    <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5v10a1 1 0 01-1 1H4a1 1 0 01-1-1V10z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
                    </svg>
                </span>
                <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Depots</span>
            </a>
        @endif

        {{-- Products --}}
        @if($user && $user->hasPermission('products.view'))
            <a href="{{ route('products.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('products.*') ? $linkActive : '' }}">
                <span class="{{ $iconWrap }}">
                    <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0v10l-8 4m-8-4V7m16 10L12 21m0 0L4 17"/>
                    </svg>
                </span>
                <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Products</span>
            </a>
        @endif

        {{-- Company profile --}}
        @if($userRole === 'owner')
            <a href="{{ route('settings.company.edit') }}"
               class="{{ $linkBase }} {{ request()->routeIs('settings.company.*') ? $linkActive : '' }}">
                <span class="{{ $iconWrap }}">
                    <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3M9 7h1m-1 4h1m4-4h1m-1 4h1M9 15h6"/>
                    </svg>
                </span>
                <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Company</span>
            </a>
        @endif

        {{-- Suppliers --}}
        @if(($user && $user->hasPermission('suppliers.view')) || $userRole === 'owner')
            <a href="{{ route('settings.suppliers.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('settings.suppliers.*') ? $linkActive : '' }}">
                <span class="{{ $iconWrap }}">
                    <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                    </svg>
                </span>
                <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Suppliers</span>
            </a>
        @endif

        {{-- Transporters --}}
        @if($user && ($user->hasPermission('transport.local') || $user->hasPermission('transport.intl') || $userRole === 'owner'))
            <a href="{{ route('settings.transporters.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('settings.transporters.*') ? $linkActive : '' }}">
                <span class="{{ $iconWrap }}">
                    <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 .001M13 16H9m4 0h2m3-5h2l2 5-2 .001M13 6l3 5h5"/>
                    </svg>
                </span>
                <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Transporters</span>
            </a>
        @endif

        {{-- Clients --}}
            <a href="{{ route('settings.clients.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('settings.clients.*') ? $linkActive : '' }}">
                <span class="{{ $iconWrap }}">
                    <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
                <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Clients</span>
            </a>

        {{-- Inventory Settings --}}
        @if($userRole === 'owner')
            <a href="{{ route('settings.inventory.index') }}"
               class="{{ $linkBase }} {{ request()->routeIs('settings.inventory.*') ? $linkActive : '' }}">
                <span class="{{ $iconWrap }}">
                    <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </span>
                <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Inventory</span>
            </a>
        @endif

        {{-- User settings sub-accordion --}}
        @if($userRole === 'owner')
            <button type="button"
                    class="w-full mt-1 px-3 py-2 rounded-2xl flex items-center justify-between transition
                        border border-transparent hover:bg-[color:var(--tw-surface-2)]
                        focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                    data-acc-toggle="user-settings">
                <span class="flex items-center gap-2.5 min-w-0">
                    <span class="{{ $iconWrap }}">
                        <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </span>
                    <span class="text-[12px] sidebar-label truncate text-[color:var(--tw-fg)]">Users</span>
                </span>
                <svg data-acc-caret class="w-3 h-3 {{ $muted }} transition-transform sidebar-label" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
                </svg>
            </button>

            <div class="mt-1 space-y-0.5 pl-4 hidden" data-acc-panel="user-settings">
                <a href="{{ route('admin.users.index') }}"
                class="{{ $linkBase }} {{ request()->is('admin/users*') ? $linkActive : '' }}">
                    <span class="{{ $iconWrap }}">
                        <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Users</span>
                </a>

                <a href="{{ route('admin.roles.index') }}"
                class="{{ $linkBase }} {{ request()->is('admin/roles*') ? $linkActive : '' }}">
                    <span class="{{ $iconWrap }}">
                        <svg class="w-3.5 h-3.5 text-[color:var(--tw-muted)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                    <span class="sidebar-label truncate text-[color:var(--tw-fg)]">Roles &amp; Permissions</span>
                </a>
            </div>
        @endif

    </div>
</div>
