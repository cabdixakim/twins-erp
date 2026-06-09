{{-- resources/views/layouts/partials/nav-settings.blade.php --}}
{{-- Single "Settings" link using .tw-nav-item CSS class --}}

<div class="mt-1 pt-2 border-t" style="border-color:var(--tw-border)">
    <a href="{{ route('settings.hub') }}"
       class="tw-nav-item {{ ($onSettingsRoute ?? false) ? 'active' : '' }} sidebar-label-parent">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Settings</span>
    </a>

    @if(in_array($userRole ?? '', ['owner','manager'], true))
    <a href="{{ route('admin.audit-log') }}"
       class="tw-nav-item sidebar-label-parent"
       style="opacity:.75">
        <span class="tw-nav-pip"></span>
        <span class="tw-nav-icon">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </span>
        <span class="tw-nav-label sidebar-label">Audit Log</span>
    </a>
    @endif
</div>
