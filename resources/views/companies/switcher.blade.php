@php
    /** @var \App\Models\User|null $u */
    $u = auth()->user();

    $companies = $companies ?? collect();
    $activeId  = (int) ($activeId ?? ($u?->active_company_id ?? 0));

    $isOwner  = (bool) ($isOwner ?? false);

    $companyCount = (int) ($companyCount ?? $companies->count());
    $appCount     = (int) ($appCount ?? 0);

    $maxPerUser = (int) ($maxPerUser ?? 1); // 0 = unlimited
    $maxInApp   = (int) ($maxInApp ?? 0);   // 0 = unlimited

    $underUserCap = (bool) ($underUserCap ?? true);
    $underAppCap  = (bool) ($underAppCap ?? true);

    $canCreateCompany = (bool) ($canCreateCompany ?? false);

    $atUserCap = ($maxPerUser !== 0) ? ($companyCount >= $maxPerUser) : false;
    $atAppCap  = ($maxInApp !== 0)   ? ($appCount >= $maxInApp)       : false;

    $title = 'Switch company';
@endphp

@extends('layouts.standalone')

@section('title', $title)

@section('content')
<div class="w-full">
    <div class="mb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight text-slate-100">
                    Switch company
                </h1>
                <p class="mt-1 text-[12px] text-slate-400">
                    Choose a workspace to continue.
                </p>
            </div>

            @if($isOwner)
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Per-user quota badge --}}
                    <div class="text-[11px] text-slate-400 rounded-xl px-2.5 py-1 ring-1 ring-slate-800 bg-slate-900">
                        <span class="text-slate-300 font-semibold">{{ $companyCount }}</span>
                        <span class="text-slate-500">/</span>
                        <span class="text-slate-300 font-semibold">{{ $maxPerUser === 0 ? '∞' : $maxPerUser }}</span>
                        <span class="ml-1">companies</span>
                    </div>

                    <button type="button"
                            id="btnOpenCreateCompany"
                            class="h-9 px-3 rounded-xl text-[12px] font-semibold
                                   ring-1 ring-slate-800 bg-slate-900 hover:bg-slate-800 transition
                                   {{ $canCreateCompany ? '' : 'opacity-50 cursor-not-allowed' }}"
                            {{ $canCreateCompany ? '' : 'disabled' }}>
                        New
                    </button>
                </div>
            @endif
        </div>

        @if($isOwner && !$underUserCap)
            <div class="mt-3 text-[12px] text-amber-200/90 bg-amber-500/10 ring-1 ring-amber-500/20 rounded-xl px-3 py-2">
                Limit reached. Your plan allows a maximum of
                <span class="font-semibold">{{ $maxPerUser === 0 ? 'unlimited' : $maxPerUser }}</span>
                companies.
            </div>
        @elseif($isOwner && !$underAppCap)
            <div class="mt-3 text-[12px] text-amber-200/90 bg-amber-500/10 ring-1 ring-amber-500/20 rounded-xl px-3 py-2">
                App limit reached. This system allows a maximum of
                <span class="font-semibold">{{ $maxInApp }}</span>
                companies in total.
            </div>
        @endif

        {{-- OPTIONAL: show app cap status (only owners) --}}
        @if($isOwner && $maxInApp !== 0)
            <div class="mt-2 text-[11px] text-slate-400">
                App capacity: <span class="text-slate-200 font-semibold">{{ $appCount }}</span>/<span class="text-slate-200 font-semibold">{{ $maxInApp }}</span>
                @if($atAppCap)
                    <span class="ml-2 text-amber-200">• app limit reached</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Search --}}
    <div class="mb-3">
        <div class="relative max-w-[520px]">
            <div class="absolute inset-y-0 left-3 grid place-items-center text-slate-500">
                <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                </svg>
            </div>

            <input id="twCompanySearch"
                   class="w-full h-10 pl-9 pr-3 rounded-xl bg-slate-900 ring-1 ring-slate-800
                          text-[13px] placeholder:text-slate-500
                          focus:outline-none focus:ring-2 focus:ring-slate-700"
                   placeholder="Search companies…"
                   autocomplete="off">
        </div>
    </div>

    {{-- List --}}
    <div class="rounded-2xl ring-1 ring-slate-800 bg-slate-950 overflow-hidden">
        <div class="px-3 py-2 border-b border-slate-800 flex items-center justify-between">
            <div class="text-[11px] uppercase tracking-wide text-slate-500">Companies</div>
            <div class="text-[11px] text-slate-500">{{ $companyCount }} total</div>
        </div>

        <div id="twCompanyList" class="divide-y divide-slate-800">
            @forelse($companies as $c)
                @php $isActive = ((int) $c->id === $activeId); @endphp

                <div class="px-3 py-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $isActive ? 'bg-emerald-400' : 'bg-slate-700' }} shrink-0"></span>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="tw-company-name text-[13px] font-semibold text-slate-100 truncate">
                                        {{ $c->name }}
                                    </div>

                                    @if($isActive)
                                        <span class="text-[11px] text-emerald-300 bg-emerald-500/10 ring-1 ring-emerald-500/20 px-2 py-0.5 rounded-lg">
                                            Active
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-400">Switch</span>
                                    @endif
                                </div>

                                <div class="text-[11px] text-slate-500 truncate">
                                    Updated recently • Settings, stock, users
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if($isActive)
                                <a href="{{ route('dashboard') }}"
                                   class="h-9 inline-flex items-center px-3 rounded-xl text-[12px] font-semibold
                                          bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                                    Open
                                </a>
                            @else
                                <a href="{{ route('companies.switch', $c) }}"
                                   class="h-9 inline-flex items-center px-3 rounded-xl text-[12px] font-semibold
                                          bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                                    Switch
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-4">
                    <div class="text-[13px] font-semibold text-slate-200">No companies</div>
                    <div class="text-[12px] text-slate-400 mt-1">
                        If this is a fresh system, run the initial setup wizard.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- modal stays exactly as you already have it, BUT keep the button disabled via $canCreateCompany --}}
@if($isOwner)
    <div id="twCreateCompanyOverlay" class="hidden fixed inset-0 z-[80] bg-black/55"></div>

    <div id="twCreateCompanyModal"
         class="hidden fixed z-[90] left-1/2 top-[14%] -translate-x-1/2
                w-[92vw] max-w-[520px] rounded-2xl overflow-hidden
                bg-slate-950 ring-1 ring-slate-800 shadow-[0_30px_90px_rgba(0,0,0,.70)]">
        <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold text-slate-100">Create company</div>
                <div class="text-[11px] text-slate-400">Owner only</div>
            </div>

            <button type="button"
                    id="btnCloseCreateCompany"
                    class="h-9 w-9 grid place-items-center rounded-xl bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition"
                    aria-label="Close">
                <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>
        </div>

        <form method="post" action="{{ route('companies.store') }}" class="p-4">
            @csrf

            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Company name</label>
                    <input name="name" required
                           class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                  placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                           placeholder="e.g. Twins Lubumbashi">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Country</label>
                        <input name="country"
                               class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                      placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                               placeholder="e.g. DRC">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Currency</label>
                        <input name="default_currency"
                               class="w-full h-10 px-3 rounded-xl bg-slate-900 ring-1 ring-slate-800 text-[13px]
                                      placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-700"
                               placeholder="USD">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button"
                            id="btnCancelCreateCompany"
                            class="h-9 px-3 rounded-xl text-[12px] font-semibold
                                   bg-slate-900 ring-1 ring-slate-800 hover:bg-slate-800 transition">
                        Cancel
                    </button>

                    <button type="submit"
                            class="h-9 px-3 rounded-xl text-[12px] font-semibold
                                   bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-500/25 hover:bg-emerald-500/20 transition"
                            {{ $canCreateCompany ? '' : 'disabled' }}>
                        Create
                    </button>
                </div>
            </div>
        </form>
    </div>
@endif

<script>
(function(){
    const input = document.getElementById('twCompanySearch');
    const list  = document.getElementById('twCompanyList');

    if (input && list) {
        input.addEventListener('input', () => {
            const q = (input.value || '').toLowerCase().trim();
            list.querySelectorAll('.tw-company-name').forEach(nameEl => {
                const row = nameEl.closest('.px-3.py-2');
                const txt = (nameEl.textContent || '').toLowerCase();
                row.style.display = (!q || txt.includes(q)) ? '' : 'none';
            });
        });
    }

    const openBtn = document.getElementById('btnOpenCreateCompany');
    const overlay = document.getElementById('twCreateCompanyOverlay');
    const modal   = document.getElementById('twCreateCompanyModal');

    const closeBtn  = document.getElementById('btnCloseCreateCompany');
    const cancelBtn = document.getElementById('btnCancelCreateCompany');

    function open(){
        if (!overlay || !modal) return;
        if (openBtn && openBtn.hasAttribute('disabled')) return;
        overlay.classList.remove('hidden');
        modal.classList.remove('hidden');
        setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
    }
    function close(){
        if (!overlay || !modal) return;
        overlay.classList.add('hidden');
        modal.classList.add('hidden');
    }

    openBtn?.addEventListener('click', open);
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
})();
</script>
@endsection