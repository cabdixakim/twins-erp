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

    {{-- SIDEBAR (Desktop) --}}
    <aside class="sidebar w-60 bg-slate-900 border-r border-slate-800 hidden md:flex flex-col">
        
        {{-- Logo --}}
        <div class="px-4 py-4 flex items-center gap-2 border-b border-slate-800">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 animate-pulse"></div>
            <div>
                <div class="font-semibold text-sm uppercase tracking-wide">Twins</div>
                <div class="text-xs text-slate-400">Fuel & Transport ERP</div>
            </div>
        </div>

        {{-- NAV --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">

            <a href="{{ route('dashboard') }}"
               class="block px-3 py-2 rounded-lg hover:bg-slate-800 transition
               {{ request()->routeIs('dashboard') ? 'bg-slate-800' : '' }}">
               📊 Summary
            </a>

            {{-- future core modules here --}}

            <div class="pt-4 mt-4 border-t border-slate-800 text-[10px] uppercase tracking-wide text-slate-500">
                Settings
            </div>

            {{-- User settings dropdown --}}
            <button type="button"
                    class="w-full mt-1 px-3 py-2 rounded-lg flex items-center justify-between text-xs bg-slate-900 hover:bg-slate-800 transition"
                    onclick="toggleUserSettingsDesktop()">
                <span class="flex items-center gap-2 text-slate-200">
                    ⚙️ <span>User settings</span>
                </span>
                <span id="userSettingsCaretDesktop" class="text-[10px] text-slate-400">▾</span>
            </button>

            <div id="userSettingsLinksDesktop" class="mt-1 space-y-1 pl-5">
                <a href="{{ route('admin.users.index') }}"
                   class="block px-3 py-2 rounded-lg hover:bg-slate-800 transition
                   {{ request()->is('admin/users*') ? 'bg-slate-800' : '' }}">
                    👤 Users
                </a>

                <a href="{{ route('admin.roles.index') }}"
                   class="block px-3 py-2 rounded-lg hover:bg-slate-800 transition
                   {{ request()->is('admin/roles*') ? 'bg-slate-800' : '' }}">
                    🛡️ Roles & Permissions
                </a>
            </div>

        </nav>

        {{-- LOGOUT --}}
        <form method="post" action="{{ route('logout') }}" class="px-3 py-3 border-t border-slate-800">
            @csrf
            <button class="w-full px-3 py-2 rounded-lg bg-slate-800/60 hover:bg-rose-600 text-xs transition">
                Logout
            </button>
        </form>
    </aside>

    {{-- MOBILE SIDEBAR TOGGLE (moved to right) --}}
    <button id="openMenu"
            class="md:hidden fixed top-4 right-4 bg-slate-900 text-slate-200 px-3 py-2 rounded-lg border border-slate-700 z-50">
        ☰
    </button>

    {{-- MOBILE DRAWER --}}
    <aside id="mobileSidebar"
           class="sidebar fixed top-0 left-0 h-full w-60 bg-slate-900 border-r border-slate-800 z-50 transform -translate-x-full md:hidden flex flex-col">

        <div class="px-4 py-4 flex justify-between items-center border-b border-slate-800">
            <div>
                <div class="font-semibold text-sm uppercase tracking-wide">Twins</div>
                <div class="text-xs text-slate-400">Fuel & Transport ERP</div>
            </div>
            <button id="closeMenu" class="text-xl">✖</button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">

            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">
                📊 Summary
            </a>

            <div class="pt-4 mt-4 border-t border-slate-800 text-[10px] uppercase tracking-wide text-slate-500">
                Settings
            </div>

            <button type="button"
                    class="w-full mt-1 px-3 py-2 rounded-lg flex items-center justify-between text-xs bg-slate-900 hover:bg-slate-800 transition"
                    onclick="toggleUserSettingsMobile()">
                <span class="flex items-center gap-2 text-slate-200">
                    ⚙️ <span>User settings</span>
                </span>
                <span id="userSettingsCaretMobile" class="text-[10px] text-slate-400">▾</span>
            </button>

            <div id="userSettingsLinksMobile" class="mt-1 space-y-1 pl-5">
                <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">
                    👤 Users
                </a>
                <a href="{{ route('admin.roles.index') }}" class="block px-3 py-2 rounded-lg hover:bg-slate-800">
                    🛡️ Roles & Permissions
                </a>
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

        function toggleUserSettingsDesktop() {
            const box = document.getElementById('userSettingsLinksDesktop');
            const caret = document.getElementById('userSettingsCaretDesktop');
            box.classList.toggle('hidden');
            caret.textContent = box.classList.contains('hidden') ? '▸' : '▾';
        }

        function toggleUserSettingsMobile() {
            const box = document.getElementById('userSettingsLinksMobile');
            const caret = document.getElementById('userSettingsCaretMobile');
            box.classList.toggle('hidden');
            caret.textContent = box.classList.contains('hidden') ? '▸' : '▾';
        }
    </script>

</body>
</html>