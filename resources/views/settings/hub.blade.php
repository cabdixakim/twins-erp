@extends('layouts.app')
@section('title', 'Settings')

@section('content')
<div class="max-w-5xl mx-auto space-y-10">

    {{-- Page header --}}
    <div class="flex items-center gap-4">
        <div class="h-12 w-12 rounded-2xl flex items-center justify-center
                    bg-[color:var(--tw-accent-soft)] border border-[color:rgba(16,185,129,.35)]">
            <svg class="w-6 h-6 text-[color:var(--tw-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>
        <div>
            <h1 class="text-2xl font-bold tracking-tight" style="color:var(--tw-fg)">Settings</h1>
            <p class="text-sm mt-0.5" style="color:var(--tw-muted)">Configure your workspace, master data and access control</p>
        </div>
    </div>

    {{-- ── Organisation ─────────────────────────────────────────── --}}
    <section>
        <h2 class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color:var(--tw-muted)">Organisation</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            @if(auth()->user()?->role?->slug === 'owner')
            <a href="{{ route('settings.company.edit') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-[color:var(--tw-accent-soft)] border border-[color:rgba(16,185,129,.3)]">
                    <svg class="w-5 h-5 text-[color:var(--tw-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3M9 7h1m-1 4h1m4-4h1m-1 4h1M9 15h6"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Company Profile</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Name, logo, currency and basic info</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>
            @endif

            @if(auth()->user()?->role?->slug === 'owner')
            <a href="{{ route('settings.inventory.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-sky-500/10 border border-sky-500/20">
                    <svg class="w-5 h-5 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Inventory</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Costing method and period management</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>
            @endif

        </div>
    </section>

    {{-- ── Master Data ──────────────────────────────────────────── --}}
    <section>
        <h2 class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color:var(--tw-muted)">Master Data</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            @if(auth()->user()?->hasPermission('products.view'))
            <a href="{{ route('products.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-violet-500/10 border border-violet-500/20">
                    <svg class="w-5 h-5 text-violet-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0v10l-8 4m-8-4V7m16 10L12 21m0 0L4 17"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Products</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Fuel grades and product codes</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>
            @endif

            @if(auth()->user()?->hasPermission('depots.view'))
            <a href="{{ route('settings.depots.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-amber-500/10 border border-amber-500/20">
                    <svg class="w-5 h-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5v10a1 1 0 01-1 1H4a1 1 0 01-1-1V10z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 21V12h6v9"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Depots</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Storage locations and contact details</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>
            @endif

            @if(auth()->user()?->hasPermission('suppliers.view') || auth()->user()?->role?->slug === 'owner')
            <a href="{{ route('settings.suppliers.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-rose-500/10 border border-rose-500/20">
                    <svg class="w-5 h-5 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Suppliers</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Supplier records and contact info</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>
            @endif

            @if(auth()->user()?->hasPermission('transport.local') || auth()->user()?->hasPermission('transport.intl') || auth()->user()?->role?->slug === 'owner')
            <a href="{{ route('settings.transporters.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-orange-500/10 border border-orange-500/20">
                    <svg class="w-5 h-5 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 .001M13 16H9m4 0h2m3-5h2l2 5-2 .001M13 6l3 5h5"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Transporters</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Haulage companies and fleet partners</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>
            @endif

            <a href="{{ route('settings.clients.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-teal-500/10 border border-teal-500/20">
                    <svg class="w-5 h-5 text-teal-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Clients</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Buyer records, credit limits and types</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>

        </div>
    </section>

    {{-- ── Access Control ───────────────────────────────────────── --}}
    @if(auth()->user()?->role?->slug === 'owner')
    <section>
        <h2 class="text-[11px] font-semibold uppercase tracking-widest mb-3" style="color:var(--tw-muted)">Access Control</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

            <a href="{{ route('admin.users.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-indigo-500/10 border border-indigo-500/20">
                    <svg class="w-5 h-5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Users</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Invite team members and manage access</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>

            <a href="{{ route('admin.roles.index') }}"
               class="group tw-card flex items-start gap-4 p-5 hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
                <span class="h-10 w-10 rounded-xl flex items-center justify-center flex-shrink-0
                             bg-pink-500/10 border border-pink-500/20">
                    <svg class="w-5 h-5 text-pink-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="font-semibold text-sm" style="color:var(--tw-fg)">Roles &amp; Permissions</div>
                    <div class="text-xs mt-0.5 leading-relaxed" style="color:var(--tw-muted)">Define roles and what each one can do</div>
                </div>
                <svg class="w-4 h-4 ml-auto mt-0.5 flex-shrink-0 opacity-0 group-hover:opacity-60 transition" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
            </a>

        </div>
    </section>
    @endif

</div>
@endsection
