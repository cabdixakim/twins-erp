{{-- resources/views/layouts/partials/nav-primary.blade.php --}}
@php
    $itemBase =
        "group relative flex items-center gap-2.5 rounded-xl px-2.5 py-2 border transition
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]
         hover:-translate-y-[1px] active:translate-y-0";

    $itemIdle =
        "bg-[color:var(--tw-surface)] border-[color:var(--tw-border)]
         text-[color:var(--tw-fg)]
         hover:bg-[color:var(--tw-surface-2)]";

    $itemActive =
        "bg-[linear-gradient(90deg,var(--tw-accent-soft),transparent)]
         border-[color:rgba(34,197,94,.45)]
         text-[color:var(--tw-fg)]
         shadow-[0_8px_24px_rgba(2,6,23,.08)]";

    $iconWrapBase =
        "flex h-7 w-7 items-center justify-center rounded-lg border transition flex-shrink-0";

    $iconWrapIdle =
        "bg-[color:var(--tw-surface-2)] border-[color:var(--tw-border)]
         group-hover:bg-[color:var(--tw-btn-hover)]";

    $iconWrapActive =
        "bg-[color:var(--tw-accent-soft)] border-[color:rgba(34,197,94,.35)]";

    $title = "text-[12px] font-semibold truncate";
@endphp

<div class="space-y-1">

    {{-- SUMMARY --}}
    <a href="{{ route('dashboard') }}"
       class="{{ $itemBase }} {{ $onDashboard ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-full
                     {{ $onDashboard ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ $onDashboard ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M8 19V11M12 19V8M16 19V14M20 19V10"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Summary</div>
        </div>
    </a>

    {{-- DEPOT STOCK --}}
    <a href="{{ route('depot-stock.index') }}"
       class="{{ $itemBase }} {{ $onDepotStock ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-full
                     {{ $onDepotStock ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ $onDepotStock ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Depot Stock</div>
        </div>
    </a>

    {{-- PURCHASES --}}
    <a href="{{ route('purchases.index') }}"
       class="{{ $itemBase }} {{ $onPurchases ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-full
                     {{ $onPurchases ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ $onPurchases ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Purchases</div>
        </div>
    </a>

    {{-- SALES --}}
    <a href="{{ route('sales.index') }}"
       class="{{ $itemBase }} {{ $onSales ?? false ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-full
                     {{ ($onSales ?? false) ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ ($onSales ?? false) ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16l-3-2-3 2-3-2-3 2-4-2V4z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Sales</div>
        </div>
    </a>

    {{-- divider --}}
    <div class="border-t border-[color:var(--tw-border)] my-1"></div>

    {{-- TRANSPORTERS --}}
    <a href="{{ route('transporters.index') }}"
       class="{{ $itemBase }} {{ $onTransporters ?? false ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-full
                     {{ ($onTransporters ?? false) ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ ($onTransporters ?? false) ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0121 11.414V16a1 1 0 01-1 1h-1"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Transporters</div>
        </div>
    </a>

    {{-- SUPPLIERS --}}
    <a href="{{ route('suppliers.index') }}"
       class="{{ $itemBase }} {{ $onSuppliers ?? false ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-full
                     {{ ($onSuppliers ?? false) ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ ($onSuppliers ?? false) ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Suppliers</div>
        </div>
    </a>

    {{-- DEPOTS --}}
    <a href="{{ route('depots.index') }}"
       class="{{ $itemBase }} {{ $onDepotLedger ?? false ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-full
                     {{ ($onDepotLedger ?? false) ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ ($onDepotLedger ?? false) ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5v10a1 1 0 01-1 1H4a1 1 0 01-1-1V10z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Depots</div>
        </div>
    </a>

</div>
