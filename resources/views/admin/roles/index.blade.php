@php
    $title = 'Roles - permissions';
    $subtitle = 'Define what each role can see and do across depots, trips, sales and finance.';

    /** @var \Illuminate\Support\Collection|\App\Models\Role[] $roles */
    $currentRole = $roles->firstWhere('slug', request('role')) ?? $roles->first();

    $assignedIds = $currentRole
        ? $currentRole->permissions->pluck('id')->all()
        : [];

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

    $label = "block text-[11px] $muted mb-1";
    $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg placeholder:opacity-70 focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
@endphp

@extends('layouts.app')

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

    {{-- Flash --}}
    @if (session('status'))
        <div class="mb-4 rounded-2xl border border-emerald-500/35 bg-emerald-600 text-white px-4 py-3 text-[12px] font-semibold">
            {{ session('status') }}
        </div>
    @endif

    {{-- Layout: roles ≈ 35%, permissions ≈ 65% on desktop --}}
    <div class="grid gap-5 md:grid-cols-[minmax(0,280px)_minmax(0,1fr)] xl:grid-cols-[minmax(0,320px)_minmax(0,1.2fr)]">

        {{-- ROLES PANEL --}}
        <div>
            <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold {{ $fg }} truncate">Roles</h2>
                        <p class="text-[11px] {{ $muted }} truncate">
                            Pick a role to see or tweak its access.
                        </p>
                    </div>

                    {{-- Premium small action button --}}
                    <button
                        type="button"
                        id="openCreateRoleModal"
                        class="{{ $btnPrimary }} h-8 px-3 text-[11px]"
                        title="Create new role"
                    >
                        <span class="text-base leading-none mr-1">+</span>
                        <span class="hidden sm:inline">New</span>
                    </button>
                </div>

                @if ($roles->isEmpty())
                    <div class="rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-[12px] {{ $muted }}">
                        No roles yet. Click <span class="font-semibold {{ $fg }}">New</span> to create one.
                    </div>
                @else
                    <ul class="space-y-1.5 text-xs max-h-[60vh] overflow-y-auto pr-1">
                        @foreach($roles as $role)
                            @php
                                $isActive = $currentRole && $currentRole->id === $role->id;
                            @endphp

                            <li>
                                <a
                                    href="{{ route('admin.roles.index', ['role' => $role->slug]) }}"
                                    class="group flex items-center justify-between rounded-2xl px-3 py-2 transition border {{ $border }}
                                        {{ $isActive
                                            ? 'bg-emerald-600 text-white shadow-[0_12px_40px_rgba(16,185,129,.22)]'
                                            : $surface2.' '.$fg.' hover:bg-[color:var(--tw-btn-hover)]' }}"
                                >
                                    <div class="min-w-0">
                                        <div class="font-semibold text-[13px] truncate">
                                            {{ $role->name }}
                                        </div>

                                        @if($role->description)
                                            <div class="text-[10px] opacity-80 truncate">
                                                {{ $role->description }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-col items-end gap-1 ml-2 shrink-0">
                                        @if($role->is_system)
                                            <span class="text-[9px] px-2 py-0.5 rounded-full border {{ $border }}
                                                {{ $isActive ? 'bg-white/10 text-white' : $surface.' '.$muted }}">
                                                system
                                            </span>
                                        @endif

                                        @if($isActive)
                                            <span class="text-[9px] text-white/90">active</span>
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
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 space-y-4">
            @if(!$currentRole)
                <div class="rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-[12px] {{ $muted }}">
                    No role selected. Create or pick a role on the left to edit its permissions.
                </div>
            @else
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-1">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold flex items-center gap-2 {{ $fg }}">
                            <span class="truncate">{{ $currentRole->name }}</span>

                            @if($currentRole->is_system)
                                <span class="text-[10px] px-2 py-0.5 rounded-full border {{ $border }} {{ $surface2 }} {{ $fg }} shrink-0">
                                    system
                                </span>
                            @endif
                        </h2>

                        <p class="text-[11px] {{ $muted }} truncate">
                            What can <span class="font-semibold {{ $fg }}">{{ $currentRole->name }}</span> do in Twins?
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($currentRole->slug === 'owner')
                            <span class="inline-flex items-center text-[11px] font-semibold text-white bg-emerald-600 border border-emerald-500/50 px-2 py-1 rounded-xl">
                                Full access
                            </span>
                        @else
                            <span class="inline-flex items-center text-[11px] {{ $muted }} border {{ $border }} {{ $surface2 }} px-2 py-1 rounded-xl">
                                {{ count($assignedIds) }} selected
                            </span>
                        @endif
                    </div>
                </div>

                @if($currentRole->slug === 'owner')
                    <div class="rounded-2xl border border-emerald-500/35 bg-emerald-600 text-white px-4 py-3 text-[12px] font-semibold">
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
                        <div class="rounded-2xl border {{ $border }} {{ $surface2 }} px-3 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-[11px] uppercase tracking-wide {{ $muted }}">
                                    {{ $module === 'other' ? 'System' : ucfirst($module) }}
                                </div>

                                <div class="text-[11px] {{ $muted }}">
                                    {{ $perms->count() }} perms
                                </div>
                            </div>

                            {{-- Chip grid --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($perms as $perm)
                                    @php
                                        $checked = in_array($perm->id, $assignedIds);
                                        $disabled = ($currentRole->slug === 'owner');
                                    @endphp

                                    <label class="group flex items-center gap-2 rounded-xl border {{ $border }} {{ $bg }}
                                                  px-3 py-2 text-[12px] {{ $fg }}
                                                  hover:bg-[color:var(--tw-btn-hover)] transition
                                                  {{ $checked ? 'ring-1 ring-emerald-500/25' : '' }}
                                                  {{ $disabled ? 'opacity-70 cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $perm->id }}"
                                            class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-500 focus:ring-emerald-500/60"
                                            @checked($checked)
                                            @disabled($disabled)
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
                                class="{{ $btnPrimary }} px-4 py-2 text-[13px]"
                            >
                                Save permissions
                            </button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    {{-- CREATE ROLE MODAL (premium + theme aware) --}}
<div
  id="createRoleModal"
  class="fixed inset-0 z-40 hidden bg-black/60 flex items-end sm:items-center justify-center p-0 sm:p-6"
  aria-hidden="true"
  role="dialog"
  aria-modal="true"
  aria-labelledby="createRoleTitle"
>
  {{-- Backdrop --}}
  <button
    type="button"
    id="createRoleBackdrop"
    class="fixed inset-0 z-40 w-full h-full"
    aria-label="Close modal"
  ></button>

  {{-- Card --}}
  <div
    class="relative z-50 w-full
           sm:max-w-lg
           rounded-t-3xl sm:rounded-2xl
           border {{ $border }} {{ $surface }}
           shadow-[0_35px_120px_rgba(0,0,0,.55)]
           overflow-hidden flex flex-col
           max-h-[85vh] sm:max-h-[80vh]
           mx-auto"
  >
    {{-- Mobile handle --}}
    <div class="sm:hidden pt-3 pb-1 flex justify-center">
      <div class="h-1.5 w-12 rounded-full bg-white/20"></div>
    </div>

    {{-- Header --}}
    <div class="px-5 py-4 border-b {{ $border }} flex items-start justify-between gap-3 shrink-0">
      <div class="min-w-0">
        <h2 id="createRoleTitle" class="text-base font-semibold {{ $fg }} leading-tight">New role</h2>
        <p class="text-[12px] {{ $muted }} mt-0.5">
          Give the role a clear name and optional description.
        </p>
      </div>

      <button
        type="button"
        id="closeCreateRoleModal"
        class="{{ $btnGhost }} h-10 w-10 text-lg leading-none shrink-0"
        aria-label="Close"
      >
        ×
      </button>
    </div>

    {{-- Body --}}
    <div class="flex-1 min-h-0 overflow-y-auto" style="-webkit-overflow-scrolling: touch;">
      <form method="post" action="{{ route('admin.roles.store') }}" class="p-5 space-y-4">
        @csrf

        <div>
          <label class="{{ $label }}">Role name</label>
          <input type="text" name="name" required class="{{ $input }}" placeholder="e.g. Depot manager">
        </div>

        <div>
          <label class="{{ $label }}">Slug (optional)</label>
          <input type="text" name="slug" class="{{ $input }}" placeholder="manager, accountant, transport...">
          <p class="mt-1 text-[11px] {{ $muted }}">Leave blank to generate from the name.</p>
        </div>

        <div>
          <label class="{{ $label }}">Description (optional)</label>
          <textarea name="description" rows="3" class="{{ $input }}" placeholder="What this role is responsible for..."></textarea>
        </div>

        <label class="flex items-center gap-2 rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 cursor-pointer">
          <input id="is_system" type="checkbox" name="is_system" value="1"
                 class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-500 focus:ring-emerald-500/60">
          <span class="text-[13px] {{ $fg }}">Mark as system role (protected)</span>
        </label>

        <div class="flex justify-end gap-2 pt-1">
          <button type="button" id="cancelCreateRole" class="{{ $btnGhost }} h-10 px-4 text-[13px]">Cancel</button>
          <button type="submit" class="{{ $btnPrimary }} h-10 px-5 text-[13px]">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

    <script>
        (function () {
            const modal = document.getElementById('createRoleModal');
            const backdrop = document.getElementById('createRoleBackdrop');

            const openBtn = document.getElementById('openCreateRoleModal');
            const closeBtn = document.getElementById('closeCreateRoleModal');
            const cancelBtn = document.getElementById('cancelCreateRole');

            if (!modal || !openBtn) return;

            function open() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            modal.setAttribute('aria-hidden', 'false');

            document.body.classList.add('overflow-hidden');

            setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
            }

            function close() {
            // IMPORTANT: remove focus from anything inside before hiding
            if (document.activeElement && modal.contains(document.activeElement)) {
                document.activeElement.blur();
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');

            modal.setAttribute('aria-hidden', 'true');

            document.body.classList.remove('overflow-hidden');
            }

            openBtn.addEventListener('click', open);
            closeBtn?.addEventListener('click', close);
            cancelBtn?.addEventListener('click', close);
            backdrop?.addEventListener('click', close);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
            });
        })();
    </script>

@endsection