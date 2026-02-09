{{-- resources/views/layouts/partials/brand.blade.php --}}
@php
    $name = $company->name ?? 'Twins ERP';
    $initial = strtoupper(mb_substr(trim($name), 0, 1));

    // stable “icon tile” container (works for white/black/transparent logos)
    $tile = "h-10 w-10 rounded-xl grid place-items-center overflow-hidden
             bg-white border border-slate-200 shadow-sm
             dark:bg-slate-950 dark:border-slate-800";

    // fallback initial badge (premium)
    $fallback = "h-full w-full grid place-items-center font-semibold text-[14px] tracking-wide
                 text-white bg-gradient-to-br from-emerald-500 to-cyan-500";
@endphp

<div class="flex items-center gap-3 min-w-0">
    {{-- Logo tile --}}
    <div class="{{ $tile }}">
        @if($company && $company->logo_path)
            <img src="{{ asset('storage/'.$company->logo_path) }}"
                 alt="{{ $name }} logo"
                 class="h-full w-full object-contain p-1.5">
        @else
            <div class="{{ $fallback }}">
                {{ $initial }}
            </div>
        @endif
    </div>

    {{-- When sidebar collapses, we hide this block --}}
    <div class="min-w-0 sidebar-label">
        <div class="font-semibold text-sm uppercase tracking-wide truncate">
            {{ $name }}
        </div>
        <div class="text-[11px] tw-muted">Fuel &amp; Transport ERP</div>
    </div>
</div>