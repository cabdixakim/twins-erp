{{-- resources/views/layouts/partials/nav-primary.blade.php --}}
{{-- Uses .tw-nav-item / .tw-nav-icon / .tw-nav-pip / .tw-nav-label CSS classes
     (defined in app.css) instead of broken Tailwind [color:var()] arbitrary classes --}}

<nav class="space-y-0.5">

    {{-- SUMMARY — always visible --}}
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
    @if($can['inventory.view'])
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
    @endif

    {{-- CLEARANCES --}}
    @if($can['reports.export'])
    <a href="{{ route('clearances.index') }}"
       class="tw-nav-item {{ ($onClearances ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Clearances</span>
    </a>
    @endif

    {{-- PURCHASES --}}
    @if($can['purchases.view'])
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
    @endif

    {{-- SALES --}}
    @if($can['sales.view'])
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
    @endif

    {{-- CLIENTS (AR) --}}
    @if($can['clients.view'])
    <a href="{{ route('clients.index') }}"
       class="tw-nav-item {{ ($onClients ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Clients</span>
    </a>
    @endif

    {{-- INVOICES --}}
    @if($can['sales.view'])
    <a href="{{ route('invoices.index') }}"
       class="tw-nav-item {{ ($onInvoices ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1" stroke-linejoin="round"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h4"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Invoices</span>
    </a>
    @endif

    @if($can['transporters.view'] || $can['suppliers.view'] || $can['depots.view'] || $can['reports.export'])
    <div class="tw-nav-divider"></div>
    @endif

    {{-- TRANSPORTERS --}}
    @if($can['transporters.view'])
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
    @endif

    {{-- SUPPLIERS --}}
    @if($can['suppliers.view'])
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
    @endif

    {{-- DEPOTS --}}
    @if($can['depots.view'])
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
    @endif

    {{-- DUTIES --}}
    @if($can['reports.export'])
    <a href="{{ route('duties.index') }}"
       class="tw-nav-item {{ ($onDuties ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Duties</span>
    </a>
    @endif

    @if($can['petty-cash.view'] || $can['reports.export'])
    <div class="tw-nav-divider"></div>
    @endif

    {{-- PETTY CASH --}}
    @if($can['petty-cash.view'])
    <a href="{{ route('petty-cash.index') }}"
       class="tw-nav-item {{ ($onPettyCash ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Petty Cash</span>
    </a>
    @endif

    {{-- BANKS --}}
    @if($can['petty-cash.view'])
    <a href="{{ route('banks.index') }}"
       class="tw-nav-item {{ ($onBanks ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5v2H3v-2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12v7M9 12v7M15 12v7M19 12v7"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 19h18"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Banks</span>
    </a>
    @endif

    {{-- REPORTS --}}
    @if($can['reports.export'])
    <a href="{{ route('reports.index') }}"
       class="tw-nav-item {{ ($onReports ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Reports</span>
    </a>
    @endif

    {{-- ACCOUNTING --}}
    @if($can['reports.export'])
    <a href="{{ route('accounting.index') }}"
       class="tw-nav-item {{ ($onAccounting ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Accounting</span>
    </a>
    @endif

    {{-- DOCUMENTS — always visible --}}
    <a href="{{ route('documents.index') }}"
       class="tw-nav-item {{ ($onDocuments ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Documents</span>
    </a>

    {{-- ALERTS — always visible --}}
    @php $__alertCount = auth()->check() ? \App\Services\AlertService::countForCompany(auth()->user()->active_company_id) : 0; @endphp
    <a href="{{ route('alerts.index') }}"
       class="tw-nav-item {{ ($onAlerts ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon relative">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.73 21a2 2 0 01-3.46 0"/>
            </svg>
            @if($__alertCount > 0)
            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full flex items-center justify-center text-white font-bold pointer-events-none" style="font-size:8px;background:#ef4444;line-height:1">{{ min($__alertCount,9) }}</span>
            @endif
        </span>
        <span class="tw-nav-label sidebar-label flex items-center gap-1.5">
            Alerts
            @if($__alertCount > 0)
            <span class="inline-flex items-center justify-center rounded-full font-bold text-white" style="min-width:16px;height:16px;font-size:9px;padding:0 3px;background:#ef4444;line-height:1">{{ min($__alertCount,9) }}</span>
            @endif
        </span>
    </a>

    {{-- WRITE OFFS — bottom of nav, operational not primary --}}
    @if($can['inventory.view'])
    <a href="{{ route('inventory-adjustments.index') }}"
       class="tw-nav-item {{ ($onAdjustments ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Write Offs</span>
    </a>
    @endif

</nav>
