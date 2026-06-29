<!doctype html>
<html lang="en" class="h-full">
<head>

<script>
  (function () {
    const root = document.documentElement;
    const stored = localStorage.getItem('tw-theme'); // 'dark' | 'light' | null
    const prefersLight = window.matchMedia?.('(prefers-color-scheme: light)')?.matches;
    // Default to dark; only switch to light if system explicitly says light
    const theme = stored || (prefersLight ? 'light' : 'dark');
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
        $appName = auth()->user()?->activeCompany?->name ?? config('app.name');
    @endphp

    <title>{{ $appName }} — @yield('title','Dashboard')</title>

    <!-- <script src="https://cdn.tailwindcss.com"></script> -->

    <style>
        .sidebar { transition: width 0.22s ease-in-out, transform 0.2s ease-out; }

        /* ── Print styles ─────────────────────────────────────────────── */
        @media print {
            /* Hide all chrome */
            #desktopSidebar,
            #mobileSidebarOverlay,
            #mobileSidebar,
            #appTopbar,
            #twinsTooltip,
            .no-print { display: none !important; }

            /* Reset layout — full page width */
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: #fff !important;
                color: #111 !important;
            }
            body { display: block !important; }
            .flex-1.min-w-0.flex.flex-col { display: block !important; width: 100% !important; }
            main {
                overflow: visible !important;
                padding: 0 !important;
                background: #fff !important;
            }

            /* Show print-only header */
            .print-header { display: block !important; }

            /* Clean up cards and surfaces */
            [class*="rounded"] { border-radius: 4px !important; }
            [class*="shadow"] { box-shadow: none !important; }
            * { background: transparent !important; color: #111 !important; }

            /* Keep emerald/rose colours on profit/loss numbers */
            .text-emerald-400, .text-emerald-500, .text-emerald-600 { color: #059669 !important; }
            .text-rose-400, .text-rose-500 { color: #dc2626 !important; }

            /* Borders in print */
            [class*="border"] { border-color: #d1d5db !important; }

            /* Tables */
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { padding: 6px 10px !important; font-size: 11px !important; }
            thead { background: #f3f4f6 !important; }
            tr { page-break-inside: avoid; }

            /* Page breaks */
            .page-break-before { page-break-before: always; }
            .page-break-after  { page-break-after: always; }

            /* Typography */
            body { font-size: 12px; font-family: -apple-system, Arial, sans-serif; }
            h1, h2, h3 { color: #111 !important; }

            /* Grid: make single column for narrow items, keep two-column for P&L */
            .print-two-col { display: grid !important; grid-template-columns: 2fr 1fr !important; gap: 1.5rem !important; }
        }
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

    // Eager-load permissions once so hasPermission() calls below don't N+1
    $user?->loadMissing('role.permissions');

    // Precomputed permission flags used in nav partials
    $can = [
        'inventory.view'    => (bool) $user?->hasPermission('inventory.view'),
        'purchases.view'    => (bool) $user?->hasPermission('purchases.view'),
        'sales.view'        => (bool) $user?->hasPermission('sales.view'),
        'clients.view'      => (bool) $user?->hasPermission('clients.view'),
        'transporters.view' => (bool) $user?->hasPermission('transporters.view'),
        'suppliers.view'    => (bool) $user?->hasPermission('suppliers.view'),
        'depots.view'       => (bool) $user?->hasPermission('depots.view'),
        'petty-cash.view'   => (bool) $user?->hasPermission('petty-cash.view'),
        'reports.export'    => (bool) $user?->hasPermission('reports.export'),
        'settings.company'  => (bool) $user?->hasPermission('settings.company'),
        'admin.users'       => (bool) $user?->hasPermission('admin.users'),
    ];

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
    $onReports       = request()->routeIs('reports.*');
    $onPettyCash     = request()->routeIs('petty-cash.*');
    $onBanks         = request()->routeIs('banks.*');
    $onAccounting    = request()->routeIs('accounting.*');
    $onDuties        = request()->routeIs('duties.*') || request()->routeIs('duty-vendors.*');
    $onClearances    = request()->routeIs('clearances.*');
    $onDocuments     = request()->routeIs('documents.*');
    $onAlerts        = request()->routeIs('alerts.*');
    $onAdjustments   = request()->routeIs('inventory-adjustments.*');
@endphp

@include('layouts.partials.sidebar-desktop', compact(
    'user','userRole','company','canManageUsers','isOwnerOrAdmin','isFinanceRole','isTransport','can',
    'onDashboard','onDepotStock','onPurchases','onSettingsRoute','onSales','onClients','onInvoices','onTransporters','onSuppliers','onDepotLedger','onReports','onPettyCash','onBanks','onAccounting','onDuties','onClearances','onDocuments','onAlerts','onAdjustments'
))

@include('layouts.partials.sidebar-mobile', compact(
    'user','userRole','company','canManageUsers','isOwnerOrAdmin','isFinanceRole','isTransport','can',
    'onDashboard','onDepotStock','onPurchases','onSettingsRoute','onSales','onClients','onInvoices','onTransporters','onSuppliers','onDepotLedger','onReports','onPettyCash','onBanks','onAccounting','onDuties','onClearances','onDocuments','onAlerts','onAdjustments'
))

<div class="flex-1 min-w-0 flex flex-col">
    <div id="appTopbar">
        @include('layouts.partials.topbar', compact(
            'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onPurchases','onSettingsRoute'
        ))
    </div>

    {{-- Optional: make main surface theme-aware without forcing dark --}}
    <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-transparent">
        {{-- Print-only header: hidden on screen, shown at top of every printed page --}}
        <div class="print-header" style="display:none; margin-bottom:1.5rem; padding-bottom:0.75rem; border-bottom:2px solid #111;">
            <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                <div>
                    <div style="font-size:18px; font-weight:700; color:#111;">{{ auth()->user()?->activeCompany?->name ?? config('app.name') }}</div>
                    <div style="font-size:13px; font-weight:600; color:#333; margin-top:2px;">@yield('title')</div>
                    @hasSection('subtitle')
                    <div style="font-size:11px; color:#666; margin-top:2px;">@yield('subtitle')</div>
                    @endif
                </div>
                <div style="text-align:right; font-size:10px; color:#666;">
                    <div>Printed {{ now()->format('d M Y, H:i') }}</div>
                </div>
            </div>
        </div>
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