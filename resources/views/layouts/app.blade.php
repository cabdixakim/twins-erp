<!doctype html>
<html lang="en" class="h-full">
<head>

<script>
  (function () {
    const root = document.documentElement;
    const stored = localStorage.getItem('tw-theme'); // 'dark' | 'light' | null
    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches;
    const theme = stored || (prefersDark ? 'dark' : 'light');
    const isDark = root.classList.contains('dark');

    // Only animate if theme is actually changing
    if ((theme === 'dark' && !isDark) || (theme === 'light' && isDark)) {
      root.classList.add('theme-anim');
      setTimeout(() => root.classList.remove('theme-anim'), 1200);
    }

    if (theme === 'dark') root.classList.add('dark');
    else root.classList.remove('dark');
  })();
</script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    @php
        $appName = config('app.name', 'Twins');
    @endphp

    <title>{{ config('app.name', 'Twins') }} - @yield('title','Dashboard')</title>

    <!-- <script src="https://cdn.tailwindcss.com"></script> -->

    <style>
        .sidebar { transition: width 0.22s ease-in-out, transform 0.2s ease-out; }
    </style>

    {{-- App bundles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Page-level extra CSS (e.g. Cropper CSS link, small per-page tweaks) --}}
    @stack('styles')
</head>

{{-- Theme-aware base surface driven by CSS tokens --}}
<body class="h-full flex overflow-hidden" style="background:var(--tw-bg);color:var(--tw-fg);">
        <!-- <div aria-hidden="true"
        class="fixed inset-0 z-0 pointer-events-none tw-ambient">
    </div> -->

@php
    $user            = auth()->user();
    $userRole        = $user?->role?->slug;

    // Prefer active company if set, fallback to first company
    $company = null;
    if ($user?->active_company_id) {
        $company = \App\Models\Company::where('id', $user->active_company_id)->first();
    }
    $company = $company ?: \App\Models\Company::query()->orderBy('id')->first();
    $companyCode = $company?->code;

    $canManageUsers  = in_array($userRole, ['owner','admin','manager'], true);
    $isOwnerOrAdmin  = in_array($userRole, ['owner','admin'], true);
    $isFinanceRole   = in_array($userRole, ['owner','admin','manager','accountant'], true);
    $isTransport     = in_array($userRole, ['owner','admin','manager','transport-controller'], true);

    $onDashboard     = request()->routeIs('dashboard');
    $onDepotStock    = request()->routeIs('depot-stock.*');
    $onPurchases     = request()->routeIs('purchases.*');
    $onSettingsRoute = request()->routeIs('settings.*') || request()->is('admin/*');
    $onSales         = request()->routeIs('sales.*');
    $onClients       = request()->routeIs('clients.*') || request()->routeIs('settings.clients.*');
    $onInvoices      = request()->routeIs('invoices.*');
    $onTransporters  = request()->routeIs('transporters.*');
    $onSuppliers     = request()->routeIs('suppliers.*');
    $onDepotLedger   = request()->routeIs('depots.*');
@endphp

@include('layouts.partials.sidebar-desktop', compact(
    'user','userRole','company','canManageUsers','isOwnerOrAdmin','isFinanceRole','isTransport',
    'onDashboard','onDepotStock','onPurchases','onSettingsRoute','onSales','onClients','onInvoices','onTransporters','onSuppliers','onDepotLedger'
))

@include('layouts.partials.sidebar-mobile', compact(
    'user','userRole','company','canManageUsers','isOwnerOrAdmin','isFinanceRole','isTransport',
    'onDashboard','onDepotStock','onPurchases','onSettingsRoute','onSales','onClients','onInvoices','onTransporters','onSuppliers','onDepotLedger'
))

<div class="flex-1 min-w-0 flex flex-col">
    @include('layouts.partials.topbar', compact(
        'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onPurchases','onSettingsRoute'
    ))

    {{-- Optional: make main surface theme-aware without forcing dark --}}
    <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-transparent">
        @yield('content')
    </main>
</div>

{{-- Tooltip portal (always above everything) --}}
<div id="twinsTooltip">
    <div class="tip" id="twinsTooltipText"></div>
</div>

@include('layouts.partials.layout-scripts')

{{-- Page-level extra JS (e.g. Cropper modal script) --}}
@stack('scripts')
</body>
</html>