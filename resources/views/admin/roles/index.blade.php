@php
    $title = 'Roles & permissions';
    $subtitle = 'Define what each role can see and do across depots, trips, sales and finance.';

    /** @var \Illuminate\Support\Collection|\App\Models\Role[] $roles */
    $currentRole = $roles->firstWhere('slug', request('role')) ?? $roles->first();

    $assignedIds = $currentRole
        ? $currentRole->permissions->pluck('id')->all()
        : [];
@endphp

@extends('layouts.app')

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-900/40 border border-emerald-500/60 px-3 py-2 text-xs text-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    {{-- Layout: roles ≈ 35%, permissions ≈ 65% on desktop --}}
    <div class="grid gap-5 md:grid-cols-[minmax(0,280px)_minmax(0,1fr)] xl:grid-cols-[minmax(0,320px)_minmax(0,1.2fr)]">

        {{-- ROLES PANEL --}}
        <div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-3 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold truncate">Roles</h2>
                        <p class="text-[11px] text-slate-400 truncate">
                            Pick a role to see or tweak its access.
                        </p>
                    </div>

                    {{-- Smaller add button --}}
                    <button
                        type="button"
                        id="openCreateRoleModal"
                        class="inline-flex items-center justify-center rounded-lg border border-emerald-500/50 bg-emerald-500/10 px-2 py-1 text-[11px] font-semibold text-emerald-300 hover:bg-emerald-500/20 transition"
                        title="Create new role"
                    >
                        <span class="text-base leading-none mr-1">+</span>
                        <span class="hidden sm:inline">New</span>
                    </button>
                </div>

                @if ($roles->isEmpty())
                    <p class="text-xs text-slate-400 mt-1">
                        No roles yet. Click <span class="font-semibold text-emerald-300">New</span> to create one.
                    </p>
                @else
                    <ul class="space-y-1.5 text-xs max-h-[60vh] overflow-y-auto pr-1">
                        @foreach($roles as $role)
                            @php
                                $isActive = $currentRole && $currentRole->id === $role->id;
                            @endphp
                            <li>
                                <a
                                    href="{{ route('admin.roles.index', ['role' => $role->slug]) }}"
                                    class="flex items-center justify-between rounded-xl px-3 py-2 transition
                                        {{ $isActive
                                            ? 'bg-slate-800 text-slate-50 shadow-inner shadow-slate-900/80'
                                            : 'bg-slate-950/50 text-slate-300 hover:bg-slate-900 hover:text-slate-50' }}"
                                >
                                    <div class="min-w-0">
                                        <div class="font-semibold text-[13px] truncate">
                                            {{ $role->name }}
                                        </div>

                                        @if($role->description)
                                            <div class="text-[10px] text-slate-500 truncate">
                                                {{ $role->description }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col items-end gap-1 ml-2 shrink-0">
                                        @if($role->is_system)
                                            <span class="text-[9px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">
                                                system
                                            </span>
                                        @endif

                                        @if($isActive)
                                            <span class="text-[9px] text-emerald-400">active</span>
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- PERMISSIONS PANEL --}}
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 space-y-4">
            @if(!$currentRole)
                <p class="text-xs text-slate-400">
                    No role selected. Create or pick a role on the left to edit its permissions.
                </p>
            @else
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-1">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold flex items-center gap-2">
                            <span class="truncate">{{ $currentRole->name }}</span>
                            @if($currentRole->is_system)
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 shrink-0">
                                    system
                                </span>
                            @endif
                        </h2>
                        <p class="text-[11px] text-slate-400 truncate">
                            What can <span class="font-semibold text-slate-100">{{ $currentRole->name }}</span> do in Twins?
                        </p>
                    </div>
                </div>

                @if($currentRole->slug === 'owner')
                    <div class="rounded-xl border border-emerald-500/50 bg-emerald-950/40 px-3 py-2 text-[11px] text-emerald-100 mb-2">
                        Owner always has full access to all modules. Permissions below are informational only.
                    </div>
                @endif

                <form
                    method="post"
                    action="{{ route('admin.roles.permissions.sync', $currentRole) }}"
                    class="space-y-3"
                >
                    @csrf

                    {{-- expect $permissionsByModule from controller --}}
                    @foreach($permissionsByModule as $module => $perms)
                        <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="text-[11px] uppercase tracking-wide text-slate-400">
                                    {{ $module === 'other' ? 'System' : ucfirst($module) }}
                                </div>
                            </div>

                            {{-- make chips more compact and two-column feeling --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 mt-1">
                                @foreach($perms as $perm)
                                    <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-200">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $perm->id }}"
                                            class="h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500/60"
                                            @checked(in_array($perm->id, $assignedIds))
                                            @disabled($currentRole->slug === 'owner')
                                        >
                                        <span class="truncate">{{ $perm->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if($currentRole->slug !== 'owner')
                        <div class="flex justify-end pt-1">
                            <button
                                type="submit"
                                class="px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-[13px] font-semibold text-slate-950 transition"
                            >
                                Save permissions
                            </button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    {{-- CREATE ROLE MODAL (smaller) --}}
    <div
        id="createRoleModal"
        class="fixed inset-0 z-40 hidden items-center justify-center bg-slate-950/70 px-4"
        aria-hidden="true"
    >
        <div class="w-full max-w-sm rounded-2xl border border-slate-800 bg-slate-900 p-4 shadow-xl">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div>
                    <h2 class="text-sm font-semibold">New role</h2>
                    <p class="text-[11px] text-slate-400">
                        Give the role a clear name and optional description.
                    </p>
                </div>
                <button
                    type="button"
                    id="closeCreateRoleModal"
                    class="text-slate-500 hover:text-slate-200 text-lg leading-none px-1"
                >
                    &times;
                </button>
            </div>

            <form method="post" action="{{ route('admin.roles.store') }}" class="space-y-3">
                @csrf

                <div class="space-y-1">
                    <label class="block text-[11px] font-semibold text-slate-200">
                        Role name
                    </label>
                    <input
                        type="text"
                        name="name"
                        required
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-slate-50 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        placeholder="e.g. Depot manager"
                    >
                </div>

                <div class="space-y-1">
                    <label class="block text-[11px] font-semibold text-slate-200">
                        Slug (optional)
                    </label>
                    <input
                        type="text"
                        name="slug"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-slate-50 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        placeholder="manager, accountant, transport..."
                    >
                    <p class="text-[10px] text-slate-500">
                        Leave blank to generate from the name.
                    </p>
                </div>

                <div class="space-y-1">
                    <label class="block text-[11px] font-semibold text-slate-200">
                        Description (optional)
                    </label>
                    <textarea
                        name="description"
                        rows="2"
                        class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-1.5 text-sm text-slate-50 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        placeholder="What this role is responsible for..."
                    ></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="is_system"
                        type="checkbox"
                        name="is_system"
                        value="1"
                        class="h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500/60"
                    >
                    <label for="is_system" class="text-[11px] text-slate-300">
                        Mark as system role (protected)
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        id="cancelCreateRole"
                        class="px-3 py-1.5 rounded-xl border border-slate-700 text-[11px] font-semibold text-slate-200 hover:bg-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-950"
                    >
                        Create
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('createRoleModal');
            const openBtn = document.getElementById('openCreateRoleModal');
            const closeBtn = document.getElementById('closeCreateRoleModal');
            const cancelBtn = document.getElementById('cancelCreateRole');

            if (!modal || !openBtn) return;

            const open = () => modal.classList.remove('hidden', 'opacity-0');
            const close = () => modal.classList.add('hidden');

            openBtn.addEventListener('click', open);
            if (closeBtn) closeBtn.addEventListener('click', close);
            if (cancelBtn) cancelBtn.addEventListener('click', close);

            modal.addEventListener('click', function (e) {
                if (e.target === modal) close();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    close();
                }
            });
        })();
    </script>
@endsection