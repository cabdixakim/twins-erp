@extends('layouts.app')

@section('title', 'Customs Authorities')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';
@endphp

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('settings.hub') }}" class="hover:underline">Settings</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Customs Authorities</span>
</div>

<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold {{ $fg }}">Customs Authorities</h1>
            <p class="text-sm {{ $muted }} mt-0.5">Manage the customs authorities you pay duty to on import shipments.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    {{-- Add form --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
        <h2 class="text-sm font-semibold {{ $fg }} mb-4">Add customs authority</h2>
        <form method="POST" action="{{ route('settings.duty-vendors.store') }}" class="grid grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
                @error('name')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Code (e.g. ZRA)</label>
                <input type="text" name="code" value="{{ old('code') }}" placeholder="ZRA"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Country</label>
                <input type="text" name="country" value="{{ old('country') }}"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Currency</label>
                <input type="text" name="default_currency" value="{{ old('default_currency', 'USD') }}" placeholder="USD"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Contact person</label>
                <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div class="col-span-2 flex justify-end">
                <button type="submit"
                    class="h-9 px-5 rounded-xl bg-[color:var(--tw-accent)] text-white text-sm font-semibold hover:opacity-90 transition">
                    Add authority
                </button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    @if($vendors->isNotEmpty())
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-sm">
            <thead class="{{ $muted }} border-b {{ $border }} {{ $surface2 }}">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Name</th>
                    <th class="text-left px-3 py-3 font-semibold">Code</th>
                    <th class="text-left px-3 py-3 font-semibold">Country</th>
                    <th class="text-left px-3 py-3 font-semibold">Currency</th>
                    <th class="text-left px-3 py-3 font-semibold">Contact</th>
                    <th class="text-center px-3 py-3 font-semibold">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y {{ $border }}">
                @foreach($vendors as $v)
                <tr class="{{ $v->is_active ? '' : 'opacity-50' }}">
                    <td class="px-5 py-3 font-semibold {{ $fg }}">
                        <a href="{{ route('duty-vendors.show', $v) }}" class="hover:underline">{{ $v->name }}</a>
                    </td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $v->code ?? '—' }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $v->country ?? '—' }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $v->default_currency }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $v->contact_person ?? '—' }}</td>
                    <td class="px-3 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border
                            {{ $v->is_active
                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                                : 'bg-slate-500/10 text-slate-500 border-slate-500/20' }}">
                            {{ $v->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button"
                                onclick="openEditVendor({{ $v->id }}, '{{ addslashes($v->name) }}', '{{ $v->code }}', '{{ $v->country }}', '{{ $v->city }}', '{{ $v->contact_person }}', '{{ $v->phone }}', '{{ $v->default_currency }}', '{{ addslashes($v->notes ?? '') }}')"
                                class="text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] underline">Edit</button>
                            <form method="POST" action="{{ route('settings.duty-vendors.toggle', $v) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] underline">
                                    {{ $v->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-10 text-center {{ $muted }} text-sm">
        No customs authorities yet. Add one above.
    </div>
    @endif
</div>

{{-- Edit modal --}}
<div id="editVendorModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5)">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} p-6 shadow-2xl">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">Edit customs authority</h3>
        <form id="editVendorForm" method="POST" class="grid grid-cols-2 gap-4">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Name *</label>
                <input type="text" name="name" id="ev_name" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Code</label>
                <input type="text" name="code" id="ev_code"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Country</label>
                <input type="text" name="country" id="ev_country"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">City</label>
                <input type="text" name="city" id="ev_city"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Contact person</label>
                <input type="text" name="contact_person" id="ev_contact"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Phone</label>
                <input type="text" name="phone" id="ev_phone"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Currency</label>
                <input type="text" name="default_currency" id="ev_currency"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Notes</label>
                <input type="text" name="notes" id="ev_notes"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div class="col-span-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('editVendorModal').classList.add('hidden')"
                    class="h-9 px-4 rounded-xl border {{ $border }} text-sm {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
                    Cancel
                </button>
                <button type="submit"
                    class="h-9 px-5 rounded-xl bg-[color:var(--tw-accent)] text-white text-sm font-semibold hover:opacity-90 transition">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditVendor(id, name, code, country, city, contact, phone, currency, notes) {
    document.getElementById('ev_name').value     = name;
    document.getElementById('ev_code').value     = code;
    document.getElementById('ev_country').value  = country;
    document.getElementById('ev_city').value     = city;
    document.getElementById('ev_contact').value  = contact;
    document.getElementById('ev_phone').value    = phone;
    document.getElementById('ev_currency').value = currency;
    document.getElementById('ev_notes').value    = notes;
    document.getElementById('editVendorForm').action = '/settings/duty-vendors/' + id;
    document.getElementById('editVendorModal').classList.remove('hidden');
}
</script>
@endsection
