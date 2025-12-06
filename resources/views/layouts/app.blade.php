<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Twins - @yield('title','Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .sidebar { transition: all 0.25s ease-in-out; }
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

{{-- SIDEBAR (Desktop) --}}
<aside class="sidebar w-64 bg-slate-900/95 border-r border-slate-800 hidden md:flex flex-col backdrop-blur">

    {{-- Logo / Brand --}}
    <div class="px-4 py-4 flex items-center gap-3 border-b border-slate-800/80">
        @if($company && $company->logo_path)
            <img src="{{ asset('storage/'.$company->logo_path) }}"
                 class="w-10 h-10 rounded-xl object-cover border border-slate-700 shadow">
        @else
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 animate-pulse"></div>
        @endif

        <div class="min-w-0">
            <div class="font-semibold text-sm uppercase tracking-wide truncate">
                {{ $company->name ?? 'Twins ERP' }}
            </div>
            <div class="text-[11px] text-slate-400">Fuel &amp; Transport ERP</div>
        </div>
    </div>

    {{-- NAV --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 text-sm">

        {{-- PRIMARY AREA: Summary + Depot stock --}}
        <div class="space-y-1">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5
                      {{ $onDashboard
                            ? 'bg-slate-800 text-slate-50 shadow-inner'
                            : 'bg-slate-950/40 text-slate-200 hover:bg-slate-900/80' }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg
                             {{ $onDashboard ? 'bg-slate-900' : 'bg-slate-900/70' }}">
                    📊
                </span>
                <div class="min-w-0">
                    <div class="text-[13px] font-semibold truncate">Summary</div>
                    <div class="text-[11px] text-slate-400 truncate">
                        High-level view of all activity
                    </div>
                </div>
            </a>

            {{-- Depot Stock – HERO LINK --}}
            <a href="{{ route('depot-stock.index') }}"
               class="relative flex items-center gap-3 rounded-xl px-3 py-2.5 border
                      {{ $onDepotStock
                            ? 'border-emerald-500/70 bg-gradient-to-r from-emerald-500/15 via-emerald-500/10 to-cyan-500/10 text-emerald-100 shadow-md'
                            : 'border-slate-800 bg-slate-950/60 text-slate-200 hover:border-emerald-500/40 hover:bg-slate-900/90' }}">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg
                             {{ $onDepotStock ? 'bg-emerald-500/15' : 'bg-slate-900/80' }}">
                    📦
                </span>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <div class="text-[13px] font-semibold truncate">Depot stock</div>
                        <span class="text-[9px] uppercase tracking-wide rounded-full px-2 py-0.5
                                    {{ $onDepotStock ? 'bg-emerald-500/20 text-emerald-200' : 'bg-slate-800 text-slate-400' }}">
                            Live AGO
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400 truncate">
                        Receive, sell, adjust by depot (soon)
                    </div>
                </div>
            </a>

        </div>

        {{-- SETTINGS ACCORDION --}}
        <div class="pt-2 border-t border-slate-800/70">
            <button type="button"
                    onclick="toggleSettingsDesktop()"
                    class="w-full mt-3 px-3 py-2 rounded-lg flex items-center justify-between text-xs
                           {{ $onSettingsRoute ? 'bg-slate-800 text-slate-100' : 'bg-slate-900 text-slate-200 hover:bg-slate-800' }}">
                <span class="flex items-center gap-2">
                    ⚙️ <span class="tracking-wide uppercase text-[11px]">Settings</span>
                </span>
                <span id="settingsCaretDesktop"
                      class="text-[10px] text-slate-400">
                    {{ $onSettingsRoute ? '▾' : '▸' }}
                </span>
            </button>

            {{-- OPEN by default, but collapse if user clicks --}}
            <div id="settingsLinksDesktop"
                 class="mt-2 space-y-1 pl-3 {{ $onSettingsRoute ? '' : '' }}">
                {{-- Depots --}}
                @if($user && $user->hasPermission('depots.view'))
                    <a href="{{ route('settings.depots.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                              {{ request()->routeIs('settings.depots.*')
                                    ? 'bg-slate-800 text-slate-50'
                                    : 'hover:bg-slate-800/80 text-slate-200' }}">
                        🏭 <span>Depots</span>
                    </a>
                @endif

                {{-- Company profile --}}
                @if($userRole === 'owner')
                    <a href="{{ route('settings.company.edit') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                              {{ request()->routeIs('settings.company.*')
                                    ? 'bg-slate-800 text-slate-50'
                                    : 'hover:bg-slate-800/80 text-slate-200' }}">
                        🧾 <span>Company profile</span>
                    </a>
                @endif

                {{-- Suppliers --}}
                @if(($user && $user->hasPermission('suppliers.view')) || $userRole === 'owner')
                    <a href="{{ route('settings.suppliers.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                              {{ request()->routeIs('settings.suppliers.*')
                                    ? 'bg-slate-800 text-slate-50'
                                    : 'hover:bg-slate-800/80 text-slate-200' }}">
                        ⛽ <span>Suppliers</span>
                    </a>
                @endif

                {{-- Transporters --}}
                @if($user && ($user->hasPermission('transport.local') || $user->hasPermission('transport.intl') || $userRole === 'owner'))
                    <a href="{{ route('settings.transporters.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] transition
                              {{ request()->routeIs('settings.transporters.*')
                                    ? 'bg-slate-800 text-slate-50'
                                    : 'hover:bg-slate-800/80 text-slate-200' }}">
                        🚚 <span>Transporters</span>
                    </a>
                @endif

                {{-- User settings sub-accordion --}}
                @if($userRole === 'owner')
                    <button type="button"
                            onclick="toggleUserSettingsDesktop()"
                            class="w-full mt-1 px-3 py-2 rounded-lg flex items-center justify-between text-[11px] bg-slate-900 hover:bg-slate-800 transition">
                        <span class="flex items-center gap-2 text-slate-200">
                            🛠️ <span>User settings</span>
                        </span>
                        <span id="userSettingsCaretDesktop" class="text-[10px] text-slate-400">▸</span>
                    </button>

                    <div id="userSettingsLinksDesktop" class="mt-1 space-y-1 pl-4 hidden">
                        <a href="{{ route('admin.users.index') }}"
                           class="block px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800
                                  {{ request()->is('admin/users*') ? 'bg-slate-800 text-slate-50' : 'text-slate-200' }}">
                            👤 Users
                        </a>

                        <a href="{{ route('admin.roles.index') }}"
                           class="block px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800
                                  {{ request()->is('admin/roles*') ? 'bg-slate-800 text-slate-50' : 'text-slate-200' }}">
                            🛡️ Roles &amp; Permissions
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </nav>

    {{-- LOGOUT --}}
    <form method="post" action="{{ route('logout') }}" class="px-3 py-3 border-t border-slate-800/80">
        @csrf
        <button class="w-full px-3 py-2 rounded-lg bg-slate-800/70 hover:bg-rose-600 text-[11px] font-medium transition">
            Logout
        </button>
    </form>

</aside>

{{-- MOBILE SIDEBAR BUTTON --}}
<button id="openMenu"
        class="md:hidden fixed top-4 right-4 bg-slate-900/90 text-slate-200 px-3 py-2 rounded-lg border border-slate-700 z-50 shadow">
    ☰
</button>

{{-- MOBILE SIDEBAR --}}
<aside id="mobileSidebar"
       class="sidebar fixed top-0 left-0 h-full w-64 bg-slate-900/95 border-r border-slate-800 z-50 transform -translate-x-full md:hidden flex flex-col">

    <div class="px-4 py-4 flex justify-between items-center border-b border-slate-800/80">
        <div class="flex items-center gap-2">
            @if($company && $company->logo_path)
                <img src="{{ asset('storage/'.$company->logo_path) }}" class="w-8 h-8 rounded-lg object-cover">
            @else
                <div class="w-8 h-8 rounded-lg bg-emerald-500 animate-pulse"></div>
            @endif

            <div>
                <div class="font-semibold text-sm uppercase tracking-wide">{{ $company->name ?? 'Twins ERP' }}</div>
                <div class="text-xs text-slate-400">ERP System</div>
            </div>
        </div>
        <button id="closeMenu" class="text-xl">✖</button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4 text-sm">

        {{-- Primary links --}}
        <div class="space-y-1">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5
                      {{ $onDashboard
                            ? 'bg-slate-800 text-slate-50'
                            : 'bg-slate-950/40 text-slate-200 hover:bg-slate-900/80' }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg
                             {{ $onDashboard ? 'bg-slate-900' : 'bg-slate-900/70' }}">
                    📊
                </span>
                <span class="text-[13px] font-semibold">Summary</span>
            </a>

            <a href="{{ route('depot-stock.index') }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 border
                      {{ $onDepotStock
                            ? 'border-emerald-500/70 bg-gradient-to-r from-emerald-500/15 via-emerald-500/10 to-cyan-500/10 text-emerald-100'
                            : 'border-slate-800 bg-slate-950/60 text-slate-200 hover:border-emerald-500/40 hover:bg-slate-900/90' }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg
                             {{ $onDepotStock ? 'bg-emerald-500/15' : 'bg-slate-900/80' }}">
                    📦
                </span>
                <span class="text-[13px] font-semibold">Depot stock</span>
            </a>
        </div>

        {{-- SETTINGS MOBILE --}}
        <div class="pt-2 border-t border-slate-800/70">
            <button type="button"
                    onclick="toggleSettingsMobile()"
                    class="w-full mt-3 px-3 py-2 rounded-lg flex items-center justify-between text-xs bg-slate-900 hover:bg-slate-800 transition">
                <span class="flex items-center gap-2 text-slate-200">
                    ⚙️ <span class="tracking-wide uppercase text-[11px]">Settings</span>
                </span>
                <span id="settingsCaretMobile" class="text-[10px] text-slate-400">▾</span>
            </button>

            <div id="settingsLinksMobile" class="mt-2 space-y-1 pl-3">
                <a href="{{ route('settings.depots.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800">
                    🏭 <span>Depots</span>
                </a>

                @if($userRole === 'owner')
                    <a href="{{ route('settings.company.edit') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800">
                        🧾 <span>Company profile</span>
                    </a>
                @endif

                @if(($user && $user->hasPermission('suppliers.view')) || $userRole === 'owner')
                    <a href="{{ route('settings.suppliers.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800">
                        ⛽ <span>Suppliers</span>
                    </a>
                @endif

                @if($user && ($user->hasPermission('transport.local') || $user->hasPermission('transport.intl') || $userRole === 'owner'))
                    <a href="{{ route('settings.transporters.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800">
                        🚚 <span>Transporters</span>
                    </a>
                @endif

                @if($userRole === 'owner')
                    <button type="button"
                            onclick="toggleUserSettingsMobile()"
                            class="w-full mt-1 px-3 py-2 rounded-lg flex items-center justify-between text-[11px] bg-slate-900 hover:bg-slate-800 transition">
                        <span class="flex items-center gap-2 text-slate-200">
                            🛠️ <span>User settings</span>
                        </span>
                        <span id="userSettingsCaretMobile" class="text-[10px] text-slate-400">▸</span>
                    </button>

                    <div id="userSettingsLinksMobile" class="mt-1 space-y-1 pl-4 hidden">
                        <a href="{{ route('admin.users.index') }}"
                           class="block px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800">
                            👤 Users
                        </a>
                        <a href="{{ route('admin.roles.index') }}"
                           class="block px-3 py-2 rounded-lg text-[12px] hover:bg-slate-800">
                            🛡️ Roles &amp; Permissions
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </nav>

</aside>

{{-- MAIN BODY --}}
<main class="flex-1 overflow-y-auto p-6 md:p-8">
    <header class="md:hidden mb-4">
        <h1 class="text-xl font-semibold">@yield('title','Dashboard')</h1>
        <p class="text-sm text-slate-400">@yield('subtitle')</p>
    </header>

    @yield('content')
</main>

<script>
    const openMenu = document.getElementById('openMenu');
    const closeMenu = document.getElementById('closeMenu');
    const mobileSidebar = document.getElementById('mobileSidebar');

    openMenu?.addEventListener('click', () =>
        mobileSidebar.classList.remove('-translate-x-full')
    );
    closeMenu?.addEventListener('click', () =>
        mobileSidebar.classList.add('-translate-x-full')
    );

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
</script>

</body>
</html>