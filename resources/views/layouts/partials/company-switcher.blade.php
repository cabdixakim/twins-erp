@php
  $u = auth()->user();
  $companies = method_exists($u,'companies')
      ? $u->companies()->orderBy('name')->get()
      : collect();

  $activeId = (int) ($u->active_company_id ?? 0);
  $active = $companies->firstWhere('id', $activeId) ?? $companies->first();

  $initial = strtoupper(mb_substr($active?->name ?? '—', 0, 1));
  $count = $companies->count();
  $activeCode = $active?->code ?? '';
@endphp

<a href="{{ route('companies.switcher') }}"
   class="group flex items-center gap-2.5 h-10 px-3 rounded-2xl
          border transition min-w-[200px] max-w-[320px]
          bg-[color:var(--tw-surface)] border-[color:var(--tw-border)]
          hover:bg-[color:var(--tw-surface-2)]
          hover:border-[color:color-mix(in_srgb,var(--tw-accent)35%,var(--tw-border))]"
   title="Switch company"
   aria-label="Switch company">

    {{-- Initial badge (always readable) --}}
    <span class="h-7 w-7 grid place-items-center rounded-xl text-[12px] font-semibold
                 border transition
                 bg-[color:var(--tw-surface-2)]
                 border-[color:var(--tw-border)]
                 text-[color:var(--tw-fg)]
                 group-hover:border-[color:color-mix(in_srgb,var(--tw-accent)45%,var(--tw-border))]">
        {{ $initial }}
    </span>

    {{-- Name + count --}}
    <span class="min-w-0 flex-1 leading-tight">
        <span class="block text-[13px] font-semibold truncate text-[color:var(--tw-fg)]">
            {{ $active?->name ?? 'Select company' }}
            @if($activeCode)
                <span class="ml-2 text-xs text-[color:var(--tw-muted)]">[{{ $activeCode }}]</span>
            @endif
        </span>
        <span class="block text-[11px] truncate text-[color:var(--tw-muted)]">
            {{ $count }} {{ $count === 1 ? 'company' : 'companies' }}
        </span>
    </span>

    {{-- Chevron --}}
    <svg class="w-4 h-4 transition opacity-70 group-hover:opacity-100"
         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
         aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
    </svg>
</a>