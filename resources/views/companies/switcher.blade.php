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

    // Token-ish helpers (theme aware via CSS vars)
    $card   = "tw-surface border border-[color:var(--tw-border)]";
    $muted  = "text-[color:var(--tw-muted)]";
    $fg     = "text-[color:var(--tw-fg)]";
    $btn    = "bg-[color:var(--tw-btn)] border border-[color:var(--tw-border)] hover:bg-[color:var(--tw-btn-hover)]";
@endphp

@extends('layouts.standalone')

@section('title', $title)

@section('content')
<div class="w-full">

    {{-- Header --}}
    <div class="mb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight {{ $fg }}">
                    Switch company
                </h1>
                <p class="mt-1 text-[12px] {{ $muted }}">
                    Choose a workspace to continue.
                </p>
            </div>

            @if($isOwner)
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Per-user quota badge --}}
                    <div class="text-[11px] rounded-2xl px-2.5 py-1 {{ $card }}">
                        <span class="font-semibold {{ $fg }}">{{ $companyCount }}</span>
                        <span class="{{ $muted }}">/</span>
                        <span class="font-semibold {{ $fg }}">{{ $maxPerUser === 0 ? '∞' : $maxPerUser }}</span>
                        <span class="ml-1 {{ $muted }}">companies</span>
                    </div>

                    <button type="button"
                            id="btnOpenCreateCompany"
                            class="h-9 px-3 rounded-2xl text-[12px] font-semibold transition
                                   {{ $btn }}
                                   {{ $canCreateCompany ? '' : 'opacity-50 cursor-not-allowed' }}"
                            {{ $canCreateCompany ? '' : 'disabled' }}>
                        New
                    </button>
                </div>
            @endif
        </div>

        {{-- Limits --}}
        @if($isOwner && !$underUserCap)
            <div class="mt-3 text-[12px] rounded-2xl px-3 py-2
                        bg-amber-500/10 border border-amber-500/20 text-amber-200/90">
                Limit reached. Your plan allows a maximum of
                <span class="font-semibold">{{ $maxPerUser === 0 ? 'unlimited' : $maxPerUser }}</span>
                companies.
            </div>
        @elseif($isOwner && !$underAppCap)
            <div class="mt-3 text-[12px] rounded-2xl px-3 py-2
                        bg-amber-500/10 border border-amber-500/20 text-amber-200/90">
                App limit reached. This system allows a maximum of
                <span class="font-semibold">{{ $maxInApp }}</span>
                companies in total.
            </div>
        @endif

        @if($isOwner && $maxInApp !== 0)
            <div class="mt-2 text-[11px] {{ $muted }}">
                App capacity:
                <span class="font-semibold {{ $fg }}">{{ $appCount }}</span>/<span class="font-semibold {{ $fg }}">{{ $maxInApp }}</span>
                @if($atAppCap)
                    <span class="ml-2 text-amber-200">• app limit reached</span>
                @endif
            </div>
        @endif
    </div>

    {{-- Search --}}
    <div class="mb-3">
        <div class="relative max-w-[520px]">
            <div class="absolute inset-y-0 left-3 grid place-items-center {{ $muted }}">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                </svg>
            </div>

            <input id="twCompanySearch"
                   class="w-full h-10 pl-9 pr-3 rounded-2xl text-[13px]
                          bg-[color:var(--tw-bg)] border border-[color:var(--tw-border)]
                          text-[color:var(--tw-fg)] placeholder:text-[color:var(--tw-muted)]
                          focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                   placeholder="Search companies…"
                   autocomplete="off">
        </div>
    </div>

    {{-- List --}}
    <div class="rounded-2xl overflow-hidden {{ $card }}">
        <div class="px-3 py-2 border-b border-[color:var(--tw-border)] flex items-center justify-between">
            <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Companies</div>
            <div class="text-[11px] {{ $muted }}">{{ $companyCount }} total</div>
        </div>

        <div id="twCompanyList" class="divide-y divide-[color:var(--tw-border)]">
            @forelse($companies as $c)
                @php $isActive = ((int) $c->id === $activeId); @endphp

                <div class="px-3 py-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 flex items-center gap-3">
                            <span class="h-2 w-2 rounded-full shrink-0
                                         {{ $isActive ? 'bg-emerald-400' : 'bg-[color:var(--tw-border)]' }}"></span>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="tw-company-name text-[13px] font-semibold truncate {{ $fg }}">
                                        {{ $c->name }}
                                        @if($c->code)
                                            <span class="ml-2 text-xs {{ $muted }}">[{{ $c->code }}]</span>
                                        @endif
                                    </div>

                                        @if($isActive)
                                            <span
                                                class="text-[11px] px-2 py-0.5 rounded-xl border"
                                                style="
                                                    background: var(--tw-accent-soft);
                                                    border-color: var(--tw-accent-soft-border, var(--tw-border));
                                                    color: var(--tw-accent);
                                                "
                                            >
                                                Active
                                            </span>
                                        @else
                                            <span class="text-[11px] {{ $muted }}">Switch</span>
                                        @endif
                                </div>

                                <div class="text-[11px] {{ $muted }} truncate">
                                    Updated recently • Settings, stock, users
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if($isActive)
                                <a href="{{ route('dashboard') }}"
                                   class="h-9 inline-flex items-center px-3 rounded-2xl text-[12px] font-semibold transition {{ $btn }}">
                                    Open
                                </a>
                            @else
                                <a href="{{ route('companies.switch', $c) }}"
                                   class="h-9 inline-flex items-center px-3 rounded-2xl text-[12px] font-semibold transition {{ $btn }}">
                                    Switch
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-4">
                    <div class="text-[13px] font-semibold {{ $fg }}">No companies</div>
                    <div class="text-[12px] {{ $muted }} mt-1">
                        If this is a fresh system, run the initial setup wizard.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Create company modal (premium + NOT full-page) --}}
@if($isOwner)
 <div id="twCreateCompanyOverlay"
         class="hidden fixed inset-0 z-[80] bg-black/55 backdrop-blur-sm"></div>

    {{-- Modal (theme-aware: uses your CSS tokens) --}}
    <div id="twCreateCompanyModal"
         class="hidden fixed z-[90] left-1/2 top-[14%] -translate-x-1/2
                w-[92vw] max-w-[520px] rounded-2xl overflow-hidden
                ring-1 shadow-[0_30px_90px_rgba(0,0,0,.55)]
                isolate"
         style="
            background: var(--tw-surface);
            color: var(--tw-fg);
            border-color: var(--tw-border);
         ">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-[color:var(--tw-border)] flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold {{ $fg }}">Create company</div>
                <div class="text-[11px] {{ $muted }}">Owner only</div>
            </div>

            <button type="button"
                    id="btnCloseCreateCompany"
                    class="h-9 w-9 grid place-items-center rounded-2xl transition {{ $btn }}"
                    aria-label="Close">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body (scrolls if content grows) --}}
        <div class="overflow-auto">
            <form method="post" action="{{ route('companies.store') }}" class="p-4">
                @csrf

                <div class="space-y-3">
                    <div class="rounded-2xl p-3 bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                        <div class="text-[12px] font-semibold {{ $fg }}">New workspace</div>
                        <div class="text-[11px] {{ $muted }} mt-0.5">
                            Keep names short & recognisable (e.g. “Twins Lubumbashi”).
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] {{ $muted }} mb-1">Company name</label>
                        <input name="name"
                               class="w-full h-10 px-3 rounded-2xl text-[13px]
                                      bg-[--tw-bg] border {{ $errors->has('name') ? 'border-rose-500 ring-2 ring-rose-400' : 'border-[--tw-border]'}}
                                      text-[--tw-fg] placeholder:text-[--tw-muted]
                                      focus:outline-none focus:ring-2 focus:ring-[--tw-accent-soft]"
                               placeholder="e.g. Twins Lubumbashi"
                               value="{{ old('name') }}">
                        @error('name')
                            <div class="mt-1 text-[11px] text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] {{ $muted }} mb-1">Company code</label>
                        <input name="code"
                               class="w-full h-10 px-3 rounded-2xl text-[13px]
                                      bg-[--tw-bg] border {{ $errors->has('code') ? 'border-rose-500 ring-2 ring-rose-400' : 'border-[--tw-border]'}}
                                      text-[--tw-fg] placeholder:text-[--tw-muted]
                                      focus:outline-none focus:ring-2 focus:ring-[--tw-accent-soft]"
                               placeholder="Unique code (e.g. TWINS-LUB)"
                               value="{{ old('code') }}">
                        @error('code')
                            <div class="mt-1 text-[11px] text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] {{ $muted }} mb-1">Country</label>
                            <input name="country"
                                   class="w-full h-10 px-3 rounded-2xl text-[13px]
                                          bg-[color:var(--tw-bg)] border border-[color:var(--tw-border)]
                                          text-[color:var(--tw-fg)] placeholder:text-[color:var(--tw-muted)]
                                          focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                                   placeholder="e.g. DRC">
                        </div>

                        <div>
                            <label class="block text-[11px] {{ $muted }} mb-1">Currency</label>
                            <input name="default_currency"
                                   class="w-full h-10 px-3 rounded-2xl text-[13px]
                                          bg-[color:var(--tw-bg)] border border-[color:var(--tw-border)]
                                          text-[color:var(--tw-fg)] placeholder:text-[color:var(--tw-muted)]
                                          focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                                   placeholder="USD">
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button"
                                id="btnCancelCreateCompany"
                                class="h-9 px-3 rounded-2xl text-[12px] font-semibold transition {{ $btn }}">
                            Cancel
                        </button>

                        <button type="submit"
                                class="h-9 px-3 rounded-2xl text-[12px] font-semibold transition
                                    disabled:opacity-50 disabled:cursor-not-allowed"
                                style="
                                    background: var(--tw-surface);
                                    color: var(--tw-accent-fg);
                                    border: 1px solid var(--tw-border);
                                "
                                onmouseover="
                                    this.style.background='var(--tw-accent-soft)';
                                    this.style.borderColor='var(--tw-accent-border)';
                                "
                                onmouseout="
                                    this.style.background='var(--tw-surface)';
                                    this.style.borderColor='var(--tw-border)';
                                "
                                {{ $canCreateCompany ? '' : 'disabled' }}>
                            Create
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
(function(){
    // Search filter
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

    // Modal
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

        // lock page scroll (prevents “full page stretch” feeling)
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';

        setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
    }

    function close(){
        if (!overlay || !modal) return;

        overlay.classList.add('hidden');
        modal.classList.add('hidden');

        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
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

@if ($errors->any())
<script>
window.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('twCreateCompanyOverlay');
    const modal   = document.getElementById('twCreateCompanyModal');
    if (overlay && modal) {
        overlay.classList.remove('hidden');
        modal.classList.remove('hidden');
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
        setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
    }
});
</script>
@endif
@endsection

