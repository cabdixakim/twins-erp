{{-- resources/views/layouts/partials/nav-primary.blade.php --}}
@php
    /**
     * Theme-aware + premium (token-based)
     * Flags expected:
     *  $onDashboard, $onDepotStock, $onPurchases, $onSales
     */

    $itemBase =
        "group relative flex items-center gap-3 rounded-2xl px-3 py-2.5 border transition
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]
         hover:-translate-y-[1px] active:translate-y-0";

    $itemIdle =
        "bg-[color:var(--tw-surface)] border-[color:var(--tw-border)]
         text-[color:var(--tw-fg)]
         hover:bg-[color:var(--tw-surface-2)]";

    $itemActive =
        "bg-[linear-gradient(90deg,var(--tw-accent-soft),transparent)]
         border-[color:rgba(34,197,94,.55)]
         text-[color:var(--tw-fg)]
         shadow-[0_14px_40px_rgba(2,6,23,.10)]";

    $iconWrapBase =
        "tw-tip-r flex h-9 w-9 items-center justify-center rounded-2xl border transition
         group-hover:shadow-[0_10px_25px_rgba(2,6,23,.10)]";

    $iconWrapIdle =
        "bg-[color:var(--tw-surface-2)] border-[color:var(--tw-border)]
         group-hover:bg-[color:var(--tw-btn-hover)]";

    $iconWrapActive =
        "bg-[color:var(--tw-accent-soft)] border-[color:rgba(34,197,94,.45)]";

    $kicker = "text-[11px] tw-muted truncate";
    $title  = "text-[13px] font-semibold truncate";
@endphp

<div class="space-y-1.5">

    {{-- SUMMARY --}}
    <a href="{{ route('dashboard') }}"
       class="{{ $itemBase }} {{ $onDashboard ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     {{ $onDashboard ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ $onDashboard ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 19V11"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V8"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 19V14"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 19V10"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Summary</div>
        </div>
    </a>

    {{-- DEPOT STOCK --}}
    <a href="{{ route('depot-stock.index') }}"
       class="{{ $itemBase }} {{ $onDepotStock ? $itemActive : $itemIdle }}">
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     {{ $onDepotStock ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ $onDepotStock ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     {{ $onPurchases ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ $onPurchases ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     {{ ($onSales ?? false) ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ ($onSales ?? false) ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16l-3-2-3 2-3-2-3 2-4-2V4z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h8M8 12h8M8 16h5"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="{{ $title }}">Sales</div>
        </div>
    </a>

</div>