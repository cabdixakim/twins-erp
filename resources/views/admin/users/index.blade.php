@extends('layouts.app')

@section('title', 'Users')
@section('subtitle', 'Manage who can sign in to Twins and what they can do.')

@section('content')
@php
    $authUser      = auth()->user();
    $authRoleName  = $authUser?->role?->name;
    $isAdmin       = in_array($authRoleName, ['Admin', 'Owner']);

    // Theme tokens (premium + theme aware)
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost   = "inline-flex items-center justify-center cursor-pointer rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition font-semibold";
    $btnPrimary = "inline-flex items-center justify-center cursor-pointer rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
    $btnDanger  = "inline-flex items-center justify-center cursor-pointer rounded-xl border border-rose-500/50 bg-rose-600 text-white hover:bg-rose-500 transition font-semibold";
    $btnInfo    = "inline-flex items-center justify-center cursor-pointer rounded-xl border border-sky-500/40 bg-sky-600 text-white hover:bg-sky-500 transition font-semibold";

    $label = "block text-[11px] $muted mb-1";
    $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg placeholder:opacity-70 focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
@endphp

<div class="max-w-6xl mx-auto space-y-6">

    {{-- Flash message --}}
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-500/35 bg-emerald-600 text-white px-4 py-3 text-[12px] font-semibold">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-sm font-semibold {{ $fg }}">Team members</h2>
            <p class="text-[11px] {{ $muted }}">Manage sign-in, roles, and security actions.</p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <div class="hidden sm:flex items-center text-[11px] {{ $muted }} rounded-xl px-2.5 py-1 border {{ $border }} {{ $surface }}">
                <span class="{{ $fg }} font-semibold">{{ $users->count() }}</span>
                <span class="ml-1">total</span>
            </div>

            @if($isAdmin)
                <button type="button"
                        class="{{ $btnPrimary }} h-9 px-3 text-[12px]"
                        onclick="openCreateUserModal()">
                    + New user
                </button>
            @endif
        </div>
    </div>

    {{-- DESKTOP TABLE --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden hidden md:block">
        <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
            <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Users</div>
            <div class="text-[11px] {{ $muted }}">{{ $users->count() }} total</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="{{ $surface2 }} border-b {{ $border }} {{ $muted }} uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2.5 text-left w-[28%] font-semibold">User</th>
                        <th class="px-4 py-2.5 text-left w-[26%] font-semibold">Email</th>
                        <th class="px-4 py-2.5 text-left w-[16%] font-semibold">Role</th>
                        <th class="px-4 py-2.5 text-left w-[12%] font-semibold">Status</th>
                        <th class="px-4 py-2.5 text-right w-[18%] font-semibold">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-[color:var(--tw-border)]">
                    @forelse($users as $user)
                        @php
                            $isOwnerAccount = $user->id === 1;
                            $isActive = ($user->status === 'active');
                            $initials = collect(explode(' ', trim($user->name ?? 'U')))
                                ->filter()
                                ->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))
                                ->take(2)
                                ->join('');
                        @endphp

                        <tr class="hover:bg-[color:var(--tw-btn-hover)] transition">
                            {{-- USER --}}
                            <td class="px-4 py-3 {{ $fg }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-9 w-9 rounded-2xl border {{ $border }} {{ $surface2 }} grid place-items-center shrink-0">
                                        <span class="text-[12px] font-extrabold tracking-tight {{ $fg }}">{{ $initials }}</span>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="font-semibold text-[13px] truncate">{{ $user->name }}</span>

                                            @if($isOwnerAccount)
                                                <span class="text-[10px] px-2 py-0.5 rounded-full border {{ $border }} {{ $surface2 }} {{ $muted }}">
                                                    owner
                                                </span>
                                            @endif

                                            <span class="h-2 w-2 rounded-full {{ $isActive ? 'bg-emerald-400' : 'bg-[color:var(--tw-border)]' }} shrink-0"></span>
                                        </div>

                                        <div class="text-[11px] {{ $muted }} truncate">
                                            {{ $user->role?->name ?? 'No role' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- EMAIL --}}
                            <td class="px-4 py-3 {{ $muted }}">
                                <span class="text-[12px]">{{ $user->email }}</span>
                            </td>

                            {{-- ROLE --}}
                            <td class="px-4 py-3 {{ $fg }}">
                                <span class="text-[12px]">{{ $user->role?->name ?? '—' }}</span>
                            </td>

                            {{-- STATUS --}}
                            <td class="px-4 py-3">
                                @if($isActive)
                                    <span class="inline-flex items-center text-[11px] font-semibold text-white bg-emerald-600 border border-emerald-500/50 px-2 py-0.5 rounded-lg">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[11px] font-semibold {{ $fg }} border {{ $border }} {{ $surface2 }} px-2 py-0.5 rounded-lg">
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- ACTIONS --}}
                            <td class="px-4 py-3 text-right">
                                @if($isAdmin)
                                    <div class="inline-flex items-center justify-end gap-2 whitespace-nowrap">

                                        {{-- Edit --}}
                                        <button type="button"
                                                class="btnEditUser {{ $btnGhost }} h-8 px-3 text-[11px] active:scale-[.98]"
                                                data-id="{{ $user->id }}"
                                                data-name="{{ e($user->name) }}"
                                                data-email="{{ e($user->email) }}"
                                                data-role-id="{{ $user->role_id ?? '' }}"
                                                data-status="{{ $user->status ?? 'active' }}">
                                            Edit
                                        </button>

                                        {{-- Reset --}}
                                        <button type="button"
                                                class="btnResetUser {{ $btnInfo }} h-8 px-3 text-[11px] active:scale-[.98]"
                                                data-id="{{ $user->id }}"
                                                data-name="{{ e($user->name) }}">
                                            Reset
                                        </button>

                                        @if(!$isOwnerAccount)
                                            {{-- Activate / Deactivate --}}
                                            <form method="post"
                                                  action="{{ route('admin.users.toggle-status', $user) }}"
                                                  class="inline-block m-0 p-0">
                                                @csrf
                                                <button type="submit"
                                                        class="{{ $isActive ? $btnDanger : $btnPrimary }} h-8 px-3 text-[11px] active:scale-[.98]">
                                                    {{ $isActive ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>

                                            {{-- Delete --}}
                                            <button type="button"
                                                    class="btnDeleteUser {{ $btnDanger }} h-8 px-3 text-[11px] active:scale-[.98]"
                                                    data-id="{{ $user->id }}"
                                                    data-name="{{ e($user->name) }}">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-[11px] {{ $muted }} italic">No permission</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center {{ $muted }}">
                                No users yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MOBILE LIST --}}
    <div class="md:hidden space-y-2">
        @forelse($users as $user)
            @php
                $isOwnerAccount = $user->id === 1;
                $isActive = ($user->status === 'active');
                $initials = collect(explode(' ', trim($user->name ?? 'U')))
                    ->filter()
                    ->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))
                    ->take(2)
                    ->join('');
            @endphp

            <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 rounded-2xl border {{ $border }} {{ $surface2 }} grid place-items-center shrink-0">
                            <span class="text-[12px] font-extrabold tracking-tight {{ $fg }}">{{ $initials }}</span>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="text-[13px] {{ $fg }} font-semibold truncate">{{ $user->name }}</div>
                                @if($isOwnerAccount)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full border {{ $border }} {{ $surface2 }} {{ $muted }}">owner</span>
                                @endif
                                <span class="h-2 w-2 rounded-full {{ $isActive ? 'bg-emerald-400' : 'bg-[color:var(--tw-border)]' }}"></span>
                            </div>
                            <div class="text-[11px] {{ $muted }} truncate">{{ $user->email }}</div>
                        </div>
                    </div>

                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold shrink-0
                        {{ $isActive ? 'bg-emerald-600 text-white border border-emerald-500/50' : 'border '.$border.' '.$surface2.' '.$fg }}">
                        {{ $user->role?->name ?? 'No role' }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-[11px] {{ $muted }}">
                        Status:
                        <span class="font-semibold {{ $fg }}">{{ ucfirst($user->status) }}</span>
                    </span>
                </div>

                @if($isAdmin)
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button type="button"
                                class="btnEditUser {{ $btnGhost }} h-9 px-3 text-[12px]"
                                data-id="{{ $user->id }}"
                                data-name="{{ e($user->name) }}"
                                data-email="{{ e($user->email) }}"
                                data-role-id="{{ $user->role_id ?? '' }}"
                                data-status="{{ $user->status ?? 'active' }}">
                            Edit
                        </button>

                        <button type="button"
                                class="btnResetUser {{ $btnInfo }} h-9 px-3 text-[12px]"
                                data-id="{{ $user->id }}"
                                data-name="{{ e($user->name) }}">
                            Reset
                        </button>

                        @if(!$isOwnerAccount)
                            <form method="post" action="{{ route('admin.users.toggle-status', $user) }}">
                                @csrf
                                <button type="submit"
                                        class="{{ $isActive ? $btnDanger : $btnPrimary }} w-full h-9 px-3 text-[12px]">
                                    {{ $isActive ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            <button type="button"
                                    class="btnDeleteUser {{ $btnDanger }} h-9 px-3 text-[12px]"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ e($user->name) }}">
                                Delete
                            </button>
                        @endif
                    </div>
                @else
                    <div class="pt-1 text-[11px] {{ $muted }} italic">
                        You don't have permission to manage users.
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center {{ $muted }} text-xs">
                No users yet.
            </div>
        @endforelse
    </div>
</div>

{{-- MODALS --}}
{{-- 1) Create user --}}
<div id="createUserModal" class="fixed inset-0 z-50 hidden bg-black/60 p-3 sm:p-6 items-center justify-center">
    <div class="absolute inset-0" onclick="closeCreateUserModal()"></div>

    <div class="relative mx-auto w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }}
                shadow-[0_35px_120px_rgba(0,0,0,.55)]
                overflow-hidden">
        <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold {{ $fg }}">New user</h2>
                <p class="text-[11px] {{ $muted }}">Create a sign-in and assign a role.</p>
            </div>
            <button type="button" class="{{ $btnGhost }} h-9 w-9 text-lg leading-none" onclick="closeCreateUserModal()">×</button>
        </div>

        <div class="p-4 space-y-3">
            <p class="text-[11px] {{ $muted }}">
                You can type a password or let Twins generate one.
                The final password will be shown after you save the user.
            </p>

            <form method="post" action="{{ route('admin.users.store') }}" class="space-y-3" id="createUserForm">
                @csrf

                <div>
                    <label class="{{ $label }}">Name</label>
                    <input name="name" class="{{ $input }}" required>
                </div>

                <div>
                    <label class="{{ $label }}">Email</label>
                    <input name="email" type="email" class="{{ $input }}" required>
                </div>

                <div>
                    <label class="{{ $label }}">Role</label>
                    <select name="role_id" class="{{ $input }}">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="{{ $label }} mb-0">Password</label>
                        <button type="button"
                                class="text-[11px] text-emerald-500 hover:text-emerald-400 font-semibold cursor-pointer"
                                onclick="generateUserPassword()">
                            Generate random
                        </button>
                    </div>

                    <input id="createUserPassword" name="password" type="text"
                           placeholder="Leave empty to auto-generate"
                           class="{{ $input }}">

                    <p class="mt-1 text-[10px] {{ $muted }}">
                        If left blank, Twins will generate a strong password.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="{{ $btnGhost }} h-9 px-3 text-[12px]" onclick="closeCreateUserModal()">Cancel</button>
                    <button class="{{ $btnPrimary }} h-9 px-3 text-[12px]">Save user</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 2) Edit user --}}
<div id="editUserModal" class="fixed inset-0 z-50 hidden bg-black/60 p-3 sm:p-6 items-center justify-center">
    <div class="absolute inset-0" onclick="closeEditUserModal()"></div>

    <div class="relative mx-auto w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }}
                shadow-[0_35px_120px_rgba(0,0,0,.55)]
                overflow-hidden">
        <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold {{ $fg }}">Edit user</h2>
                <p class="text-[11px] {{ $muted }}">Update profile, role, or status.</p>
            </div>
            <button type="button" class="{{ $btnGhost }} h-9 w-9 text-lg leading-none" onclick="closeEditUserModal()">×</button>
        </div>

        <form id="editUserForm" method="post" class="p-4 space-y-3">
            @csrf
            @method('PATCH')

            <div>
                <label class="{{ $label }}">Name</label>
                <input id="editUserName" name="name" class="{{ $input }}" required>
            </div>

            <div>
                <label class="{{ $label }}">Email</label>
                <input id="editUserEmail" name="email" type="email" class="{{ $input }}" required>
            </div>

            <div>
                <label class="{{ $label }}">Role</label>
                <select id="editUserRole" name="role_id" class="{{ $input }}">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $label }}">Status</label>
                <select id="editUserStatus" name="status" class="{{ $input }}">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="{{ $btnGhost }} h-9 px-3 text-[12px]" onclick="closeEditUserModal()">Cancel</button>
                <button class="{{ $btnPrimary }} h-9 px-3 text-[12px]">Save changes</button>
            </div>
        </form>
    </div>
</div>

{{-- 3) Reset password --}}
<div id="resetPasswordModal" class="fixed inset-0 z-50 hidden bg-black/60 p-3 sm:p-6 items-center justify-center">
    <div class="absolute inset-0" onclick="closeResetPasswordModal()"></div>

    <div class="relative mx-auto w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }}
                shadow-[0_35px_120px_rgba(0,0,0,.55)]
                overflow-hidden">
        <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold {{ $fg }}">Reset password</h2>
                <p class="text-[11px] {{ $muted }}">Set a new password or generate one.</p>
            </div>
            <button type="button" class="{{ $btnGhost }} h-9 w-9 text-lg leading-none" onclick="closeResetPasswordModal()">×</button>
        </div>

        <div class="p-4 space-y-3">
            <p class="text-[11px] {{ $muted }}">
                You can type a new password or let Twins generate a secure one.
                The final password will be shown after saving.
            </p>

            <form id="resetPasswordForm" method="post" class="space-y-3">
                @csrf

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="{{ $label }} mb-0">New password</label>
                        <button type="button"
                                class="text-[11px] text-emerald-500 hover:text-emerald-400 font-semibold cursor-pointer"
                                onclick="generateResetPassword()">
                            Generate random
                        </button>
                    </div>

                    <input id="resetPasswordInput" name="password" type="text"
                           placeholder="Leave empty to auto-generate"
                           class="{{ $input }}">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="{{ $btnGhost }} h-9 px-3 text-[12px]" onclick="closeResetPasswordModal()">Cancel</button>
                    <button class="{{ $btnPrimary }} h-9 px-3 text-[12px]">Reset password</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 4) Delete confirm --}}
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden bg-black/60 p-3 sm:p-6 items-center justify-center">
    <div class="absolute inset-0" onclick="closeDeleteUserModal()"></div>

    <div class="relative mx-auto w-full max-w-sm rounded-2xl border border-rose-500/35 {{ $surface }}
                shadow-[0_35px_120px_rgba(0,0,0,.55)]
                overflow-hidden">
        <div class="px-4 py-3 border-b border-rose-500/25 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-rose-200">Delete user</h2>
                <p class="text-[11px] {{ $muted }}">This cannot be undone.</p>
            </div>
            <button type="button" class="{{ $btnGhost }} h-9 w-9 text-lg leading-none" onclick="closeDeleteUserModal()">×</button>
        </div>

        <div class="p-4 space-y-3">
            <p class="text-[12px] {{ $muted }}">
                Are you sure you want to delete <span id="deleteUserName" class="font-semibold {{ $fg }}"></span>?
            </p>

            <form id="deleteUserForm" method="post" class="flex justify-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" class="{{ $btnGhost }} h-9 px-3 text-[12px]" onclick="closeDeleteUserModal()">Cancel</button>
                <button class="{{ $btnDanger }} h-9 px-3 text-[12px]">Delete</button>
            </form>
        </div>
    </div>
</div>

{{-- 5) Generated password modal --}}
@if(session('generated_password'))
<div id="generatedPasswordModal" class="fixed inset-0 z-50 bg-black/60 p-3 sm:p-6">
    <div class="absolute inset-0" onclick="closeGeneratedPasswordModal()"></div>

    <div class="relative mx-auto w-full max-w-sm rounded-2xl border border-emerald-500/35 {{ $surface }}
                shadow-[0_35px_120px_rgba(0,0,0,.55)]
                overflow-hidden">
        <div class="px-4 py-3 border-b border-emerald-500/25 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-emerald-200">Password generated</h2>
                <p class="text-[11px] {{ $muted }}">Share it securely with the user.</p>
            </div>
            <button type="button" class="{{ $btnGhost }} h-9 w-9 text-lg leading-none" onclick="closeGeneratedPasswordModal()">×</button>
        </div>

        <div class="p-4 space-y-3">
            <p class="text-[12px] {{ $muted }}">
                Share this password with the user (<span class="{{ $fg }}">{{ session('generated_user_email') }}</span>)
                and ask them to change it after first login.
            </p>

            <div class="flex items-center gap-2">
                <input id="generatedPasswordValue" readonly class="{{ $input }} text-emerald-200"
                       value="{{ session('generated_password') }}">
                <button type="button" class="{{ $btnPrimary }} h-10 px-3 text-[12px]" onclick="copyGeneratedPassword()">
                    Copy
                </button>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" class="{{ $btnGhost }} h-9 px-3 text-[12px]" onclick="closeGeneratedPasswordModal()">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    const byId = id => document.getElementById(id);

    function showModal(id) {
        const m = byId(id);
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => m.querySelector('input,select,textarea,button')?.focus(), 30);
    }

    function hideModal(id) {
        const m = byId(id);
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // Create
    function openCreateUserModal() { showModal('createUserModal'); }
    function closeCreateUserModal() { hideModal('createUserModal'); }

    // Edit (dataset-driven)
    function openEditUserModalFromBtn(btn) {
        const form = byId('editUserForm');
        form.action = `/admin/users/${btn.dataset.id}`;

        byId('editUserName').value   = btn.dataset.name || '';
        byId('editUserEmail').value  = btn.dataset.email || '';
        byId('editUserRole').value   = btn.dataset.roleId || '';
        byId('editUserStatus').value = btn.dataset.status || 'active';

        showModal('editUserModal');
    }
    function closeEditUserModal() { hideModal('editUserModal'); }

    // Reset (dataset-driven)
    function openResetPasswordModalFromBtn(btn) {
        const form  = byId('resetPasswordForm');
        const input = byId('resetPasswordInput');

        input.value = '';
        input.placeholder = 'Leave empty to auto-generate';
        form.action = `/admin/users/${btn.dataset.id}/reset-password`;

        showModal('resetPasswordModal');
    }
    function closeResetPasswordModal() { hideModal('resetPasswordModal'); }

    // Delete (dataset-driven)
    function openDeleteUserModalFromBtn(btn) {
        const form = byId('deleteUserForm');
        byId('deleteUserName').innerText = btn.dataset.name || '';
        form.action = `/admin/users/${btn.dataset.id}`;
        showModal('deleteUserModal');
    }
    function closeDeleteUserModal() { hideModal('deleteUserModal'); }

    // Wire buttons (no inline JSON, no blade parsing problems)
    document.querySelectorAll('.btnEditUser').forEach(btn => {
        btn.addEventListener('click', () => openEditUserModalFromBtn(btn));
    });
    document.querySelectorAll('.btnResetUser').forEach(btn => {
        btn.addEventListener('click', () => openResetPasswordModalFromBtn(btn));
    });
    document.querySelectorAll('.btnDeleteUser').forEach(btn => {
        btn.addEventListener('click', () => openDeleteUserModalFromBtn(btn));
    });

    // Password generator
    function genPass(len = 12) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
        let pass = '';
        for (let i = 0; i < len; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
        return pass;
    }
    function generateUserPassword() { byId('createUserPassword').value = genPass(12); }
    function generateResetPassword() { byId('resetPasswordInput').value = genPass(12); }

    // Copy generated password
    function copyGeneratedPassword() {
        const input = byId('generatedPasswordValue');
        if (!input) return;
        const val = input.value;

        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(val);
            return;
        }

        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');
    }
    function closeGeneratedPasswordModal() { hideModal('generatedPasswordModal'); }

    // ESC closes any open modal
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        ['createUserModal','editUserModal','resetPasswordModal','deleteUserModal','generatedPasswordModal'].forEach(id => {
            const m = byId(id);
            if (m && !m.classList.contains('hidden')) hideModal(id);
        });
    });
</script>
@endsection