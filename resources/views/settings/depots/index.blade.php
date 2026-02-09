@extends('layouts.app')

@php
    $title = 'Depots';
    $subtitle = 'Configure where your AGO is stored and how storage fees apply.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost   = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
    $btnPrimary = "inline-flex items-center justify-center rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
@endphp

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

@if(session('status'))
    <div class="mb-4 rounded-xl bg-emerald-600 text-white border border-emerald-500/50 px-3 py-2 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

<div class="grid md:grid-cols-3 gap-6">

    {{-- SIDEBAR LIST OF DEPOTS --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold {{ $fg }}">Depots</h2>

            {{-- Add Depot Button --}}
            <button onclick="openModal('createDepotModal')"
                    class="{{ $btnPrimary }} px-3 py-1.5 text-xs">
                + New depot
            </button>
        </div>

        <ul class="space-y-1 text-xs">
            @foreach($depots as $depot)
                <li>
                    <a href="{{ route('settings.depots.index', ['depot' => $depot->id]) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-xl border transition
                        {{ $currentDepot && $currentDepot->id === $depot->id
                            ? 'border-emerald-500/45 bg-[color:var(--tw-surface-2)] shadow-sm'
                            : 'border-[color:var(--tw-border)] hover:bg-[color:var(--tw-surface-2)]'
                        }}">
                        <div class="min-w-0">
                            <div class="font-semibold text-[13px] truncate {{ $fg }}">{{ $depot->name }}</div>
                            <div class="text-[10px] {{ $muted }} truncate">
                                {{ $depot->city ?: 'No city set' }}
                            </div>
                        </div>

                        {{-- STATUS PILL (BRIGHT, NO DIM) --}}
                        <span class="text-[9px] px-2 py-0.5 rounded-full border font-semibold
                            {{ $depot->is_active
                                ? 'bg-emerald-600 text-white border-emerald-500/50'
                                : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]'
                            }}">
                            {{ $depot->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- RIGHT-HAND PANEL --}}
    <div class="md:col-span-2">
        @if($currentDepot)
            @include('settings.depots._details', ['depot' => $currentDepot])
        @else
            <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 text-xs {{ $muted }}">
                No depots yet. Create one on the left.
            </div>
        @endif
    </div>
</div>

{{-- CREATE DEPOT MODAL --}}
<div id="createDepotModal"
     class="fixed inset-0 bg-black/55 hidden flex items-end sm:items-center justify-center p-4 z-50"
     onclick="closeOnBg(event, 'createDepotModal')">

    <div class="w-full max-w-md rounded-2xl {{ $surface }} border {{ $border }} p-5 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold {{ $fg }}">Create depot</h2>
            <button type="button"
                    class="{{ $btnGhost }} h-9 w-9 text-lg leading-none"
                    onclick="closeModal('createDepotModal')">×</button>
        </div>

        @php
            $label = "text-xs $muted";
            $input = "w-full mt-1 rounded-xl $bg border $border p-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
        @endphp

        <form method="POST" action="{{ route('settings.depots.store') }}" class="space-y-3">
            @csrf

            <div>
                <label class="{{ $label }}">Depot name</label>
                <input type="text" name="name" class="{{ $input }}" required>
            </div>

            <div>
                <label class="{{ $label }}">City</label>
                <input type="text" name="city" class="{{ $input }}">
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" value="1" checked
                       class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-600 focus:ring-emerald-500/40">
                <span class="text-xs {{ $fg }}">Depot is active</span>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('createDepotModal')"
                        class="{{ $btnGhost }} px-3 py-1.5 text-[11px]">
                    Cancel
                </button>

                <button class="{{ $btnPrimary }} px-4 py-1.5 text-[11px] border border-emerald-500/50">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

{{-- JS --}}
<script>
    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    function closeOnBg(event, id) {
        if (event.target && event.target.id === id) {
            closeModal(id);
        }
    }
</script>

@endsection