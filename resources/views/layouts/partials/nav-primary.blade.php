<div class="space-y-1">

    {{-- SUMMARY --}}
    <a href="{{ route('dashboard') }}"
       class="group flex items-center gap-3 rounded-xl px-3 py-2.5 border
              {{ $onDashboard
                    ? 'border-emerald-500/70 bg-gradient-to-r from-emerald-500/15 via-emerald-500/10 to-cyan-500/10 text-emerald-100 shadow-md'
                    : 'border-slate-800 bg-slate-950/40 text-slate-200 hover:border-emerald-500/40 hover:bg-slate-900/80' }}">

        <span class="tw-tip-r flex h-9 w-9 items-center justify-center rounded-lg
                     {{ $onDashboard ? 'bg-emerald-500/15' : 'bg-slate-900/80 group-hover:bg-emerald-500/10' }}"
              data-tip="Summary" aria-label="Summary">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 19V11"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V8"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 19V14"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 19V10"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="text-[13px] font-semibold truncate">Summary</div>
            <div class="text-[11px] text-slate-400 truncate">High-level view of all activity</div>
        </div>
    </a>

    {{-- DEPOT STOCK --}}
    <a href="{{ route('depot-stock.index') }}"
       class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 border
              {{ $onDepotStock
                    ? 'border-emerald-500/70 bg-gradient-to-r from-emerald-500/15 via-emerald-500/10 to-cyan-500/10 text-emerald-100 shadow-md'
                    : 'border-slate-800 bg-slate-950/60 text-slate-200 hover:border-emerald-500/40 hover:bg-slate-900/90' }}">

        <span class="tw-tip-r flex h-9 w-9 items-center justify-center rounded-lg
                     {{ $onDepotStock ? 'bg-emerald-500/15' : 'bg-slate-900/80 group-hover:bg-emerald-500/10' }}"
              data-tip="Depot stock" aria-label="Depot stock">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="flex items-center gap-2">
                <div class="text-[13px] font-semibold truncate">Depot stock</div>
                <span class="text-[9px] uppercase tracking-wide rounded-full px-2 py-0.5
                            {{ $onDepotStock ? 'bg-emerald-500/20 text-emerald-200' : 'bg-slate-800 text-slate-400' }}">
                    Live AGO
                </span>
            </div>
            <div class="text-[11px] text-slate-400 truncate">Receive, sell, adjust by depot (soon)</div>
        </div>
    </a>

</div>