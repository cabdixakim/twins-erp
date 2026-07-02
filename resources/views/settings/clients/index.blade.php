@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost   = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
    $btnPrimary = "inline-flex items-center justify-center rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";

    $typeLabels = [
        'government'  => 'Government',
        'private'     => 'Private',
        'retail'      => 'Retail',
        'industrial'  => 'Industrial',
        'other'       => 'Other',
    ];

    $label = "block text-[11px] $muted mb-1";
    $input = "w-full rounded-xl border $border bg-[color:var(--tw-bg)] px-3 py-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
@endphp

@extends('layouts.app')

@section('title', 'Clients')
@section('subtitle', 'Manage the companies and people you sell and dispatch fuel to.')

@section('content')

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('settings.hub') }}" class="hover:underline">Settings</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Clients</span>
</div>

@if(session('status'))
    <div class="alert-ok mb-4 rounded-xl px-3 py-2 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

@if(session('error'))
    <div class="alert-err mb-4 rounded-xl px-3 py-2 text-xs font-semibold">
        {{ session('error') }}
    </div>
@endif

<div class="grid md:grid-cols-3 gap-6">

    {{-- LEFT SIDEBAR: client list --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold {{ $fg }}">Clients</h2>
            <div class="flex items-center gap-1.5">
                <a href="{{ route('settings.clients.export') }}"
                   class="inline-flex items-center gap-1 h-8 px-2.5 rounded-xl border {{ $border }} {{ $surface2 }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface)] transition">
                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                    </svg>
                    CSV
                </a>
                <button type="button"
                        onclick="openClientCreateModal()"
                        class="{{ $btnPrimary }} px-3 py-1.5 text-xs">
                    + New client
                </button>
            </div>
        </div>

        {{-- Quick search --}}
        <form method="GET" action="{{ route('settings.clients.index') }}"
              class="flex gap-1.5"
              id="clientSearchForm">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="type"   value="{{ $type }}">
            <input type="text" name="q" value="{{ $q }}"
                   placeholder="Search name, city…"
                   oninput="this.form.submit()"
                   class="{{ $input }} text-xs py-1.5 px-2.5">
            @if($q)
                <a href="{{ route('settings.clients.index') }}"
                   class="{{ $btnGhost }} px-2.5 py-1.5 text-xs shrink-0">✕</a>
            @endif
        </form>

        <ul class="space-y-1 text-xs">
            @forelse($clients as $client)
                <li>
                    <a href="{{ route('settings.clients.index', array_filter(['client' => $client->id, 'q' => $q, 'status' => $status, 'type' => $type])) }}"
                       class="flex items-center justify-between px-3 py-2 rounded-xl border transition
                              {{ $currentClient && $currentClient->id === $client->id
                                    ? 'border-emerald-500/45 bg-[color:var(--tw-surface-2)] shadow-sm'
                                    : 'border-[color:var(--tw-border)] hover:bg-[color:var(--tw-surface-2)]' }}">
                        <div class="min-w-0">
                            <div class="font-semibold text-[13px] truncate {{ $fg }}">
                                {{ $client->name }}
                            </div>
                            <div class="text-[10px] {{ $muted }} truncate">
                                {{ $typeLabels[$client->type] ?? ($client->type ? ucfirst($client->type) : 'Unclassified') }}
                                @if($client->city || $client->country)
                                    · {{ $client->city }}{{ $client->city && $client->country ? ', ' : '' }}{{ $client->country }}
                                @endif
                            </div>
                        </div>

                        <span class="text-[9px] px-2 py-0.5 rounded-full border font-semibold shrink-0 ml-2
                            {{ $client->is_active
                                ? 'bg-emerald-600 text-white border-emerald-500/50'
                                : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]' }}">
                            {{ $client->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </a>
                </li>
            @empty
                <li class="text-[11px] {{ $muted }} px-1 py-2">
                    No clients yet. Create your first one to start tracking dispatches.
                </li>
            @endforelse
        </ul>
    </div>

    {{-- RIGHT PANEL: details --}}
    <div class="md:col-span-2">
        @include('settings.clients._details', ['client' => $currentClient])
    </div>

</div>

{{-- CREATE CLIENT MODAL --}}
<div id="clientCreateModal"
     class="fixed inset-0 bg-black/55 hidden items-end sm:items-center justify-center p-4 z-50">
    <div class="w-full max-w-lg rounded-2xl {{ $surface }} border {{ $border }} p-4 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold {{ $fg }}">New client</h2>
            <button type="button"
                    class="{{ $btnGhost }} h-9 w-9 text-lg leading-none"
                    onclick="closeClientCreateModal()">×</button>
        </div>

        <form method="POST" action="{{ route('settings.clients.store') }}" class="space-y-3">
            @csrf

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Code / Reference</label>
                    <input type="text" name="code" placeholder="e.g. CLT-001" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Type</label>
                    <select name="type" class="{{ $input }}">
                        <option value="">Not set</option>
                        <option value="government">Government</option>
                        <option value="private">Private</option>
                        <option value="retail">Retail</option>
                        <option value="industrial">Industrial</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Currency</label>
                    <input type="text" name="currency" value="USD" maxlength="3" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Country</label>
                    <input type="text" name="country" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">City</label>
                    <input type="text" name="city" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Contact person</label>
                    <input type="text" name="contact_person" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Phone</label>
                    <input type="text" name="phone" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Email</label>
                    <input type="email" name="email" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Credit limit</label>
                    <input type="number" name="credit_limit" value="0" min="0" step="0.01" class="{{ $input }}">
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Notes</label>
                <textarea name="notes" rows="2" class="{{ $input }}"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="{{ $btnGhost }} px-3 py-1.5 text-[11px]"
                        onclick="closeClientCreateModal()">Cancel</button>
                <button type="submit"
                        class="{{ $btnPrimary }} px-4 py-1.5 text-[11px] border border-emerald-500/50">
                    Save client
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openClientCreateModal() {
        const m = document.getElementById('clientCreateModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeClientCreateModal() {
        const m = document.getElementById('clientCreateModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.getElementById('clientCreateModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeClientCreateModal();
    });
</script>

@endsection
