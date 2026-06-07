{{-- resources/views/layouts/partials/nav-primary.blade.php --}}
{{-- Uses .tw-nav-item / .tw-nav-icon / .tw-nav-pip / .tw-nav-label CSS classes
     (defined in app.css) instead of broken Tailwind [color:var()] arbitrary classes --}}

<nav class="space-y-0.5">

    {{-- SUMMARY --}}
    <a href="{{ route('dashboard') }}"
       class="tw-nav-item {{ $onDashboard ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M8 19V11M12 19V8M16 19V14M20 19V10"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Summary</span>
    </a>

    {{-- DEPOT STOCK --}}
    <a href="{{ route('depot-stock.index') }}"
       class="tw-nav-item {{ $onDepotStock ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Depot Stock</span>
    </a>

    {{-- PURCHASES --}}
    <a href="{{ route('purchases.index') }}"
       class="tw-nav-item {{ $onPurchases ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Purchases</span>
    </a>

    {{-- SALES --}}
    <a href="{{ route('sales.index') }}"
       class="tw-nav-item {{ ($onSales ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16l-3-2-3 2-3-2-3 2-4-2V4z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Sales</span>
    </a>

    <div class="tw-nav-divider"></div>

    {{-- TRANSPORTERS --}}
    <a href="{{ route('transporters.index') }}"
       class="tw-nav-item {{ ($onTransporters ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0121 11.414V16a1 1 0 01-1 1h-1"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Transporters</span>
    </a>

    {{-- SUPPLIERS --}}
    <a href="{{ route('suppliers.index') }}"
       class="tw-nav-item {{ ($onSuppliers ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Suppliers</span>
    </a>

    {{-- DEPOTS --}}
    <a href="{{ route('depots.index') }}"
       class="tw-nav-item {{ ($onDepotLedger ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5v10a1 1 0 01-1 1H4a1 1 0 01-1-1V10z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Depots</span>
    </a>

</nav>
