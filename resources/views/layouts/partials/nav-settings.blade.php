{{-- resources/views/layouts/partials/nav-settings.blade.php --}}
{{-- Single "Settings" link — replaces the old accordion --}}
@php
    $isActive = $onSettingsRoute ?? false;

    $btnBase =
        "group relative flex items-center gap-2.5 rounded-xl px-2.5 py-2 border transition
         focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]
         hover:-translate-y-[1px] active:translate-y-0";

    $btnIdle =
        "bg-[color:var(--tw-surface)] border-[color:var(--tw-border)]
         text-[color:var(--tw-fg)]
         hover:bg-[color:var(--tw-surface-2)]";

    $btnActive =
        "bg-[linear-gradient(90deg,var(--tw-accent-soft),transparent)]
         border-[color:rgba(16,185,129,.45)]
         text-[color:var(--tw-fg)]
         shadow-[0_8px_24px_rgba(2,6,23,.08)]";

    $iconWrapBase =
        "flex h-7 w-7 items-center justify-center rounded-lg border transition flex-shrink-0";

    $iconWrapIdle  = "bg-[color:var(--tw-surface-2)] border-[color:var(--tw-border)] group-hover:bg-[color:var(--tw-btn-hover)]";
    $iconWrapActive = "bg-[color:var(--tw-accent-soft)] border-[color:rgba(16,185,129,.35)]";
@endphp

<div class="pt-2 mt-1 border-t border-[color:var(--tw-border)]">
    <a href="{{ route('settings.hub') }}"
       class="{{ $btnBase }} {{ $isActive ? $btnActive : $btnIdle }}">
        <span class="absolute left-0 top-2.5 bottom-2.5 w-[3px] rounded-full
                     {{ $isActive ? 'bg-[color:var(--tw-accent)]' : 'bg-transparent' }}"></span>

        <span class="{{ $iconWrapBase }} {{ $isActive ? $iconWrapActive : $iconWrapIdle }}">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </span>

        <div class="min-w-0 sidebar-label">
            <div class="text-[12px] font-semibold truncate">Settings</div>
        </div>
    </a>
</div>
