<div class="flex items-center gap-3 min-w-0">
    @if($company && $company->logo_path)
        <img src="{{ asset('storage/'.$company->logo_path) }}"
             class="w-10 h-10 rounded-xl object-cover border border-slate-700 shadow">
    @else
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-cyan-500 animate-pulse"></div>
    @endif

    {{-- When sidebar collapses, we hide this block --}}
    <div class="min-w-0 sidebar-label">
        <div class="font-semibold text-sm uppercase tracking-wide truncate">
            {{ $company->name ?? 'Twins ERP' }}
        </div>
        <div class="text-[11px] text-slate-400">Fuel &amp; Transport ERP</div>
    </div>
</div>