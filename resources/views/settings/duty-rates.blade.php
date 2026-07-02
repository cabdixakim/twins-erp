@extends('layouts.app')

@section('title', 'Duty Rates')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';
@endphp

<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Settings</div>
            <h1 class="text-xl font-bold {{ $fg }}">Duty Rate Schedule</h1>
            <p class="text-sm {{ $muted }} mt-0.5">Define duty rates per product per 1000 litres. Multiple rows per product allow tracking rate changes over time.</p>
        </div>
        <a href="{{ route('settings.hub') }}" class="text-xs {{ $muted }} hover:underline">← Back to Settings</a>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    {{-- Add form --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
        <h2 class="text-sm font-semibold {{ $fg }} mb-4">Add duty rate</h2>
        <form method="POST" action="{{ route('settings.duty-rates.store') }}" class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Product *</label>
                <select name="product_id" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
                    <option value="">Select product</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('product_id')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold {{ $muted }} mb-1">
                    Rate per 1000 L *
                    <span class="font-normal opacity-70 ml-1">(e.g. enter 350, not 0.35)</span>
                </label>
                <input type="number" name="rate_per_1000l" id="add_rate_input" value="{{ old('rate_per_1000l') }}" step="0.0001" min="0" required
                    oninput="checkDutyRate(this, 'add_rate_warning')"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
                <div id="add_rate_warning" class="hidden mt-1.5 rounded-lg border border-amber-400/40 bg-amber-400/10 px-3 py-2 text-xs text-amber-700 dark:text-amber-300"></div>
                @error('rate_per_1000l')<p class="text-xs text-rose-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Currency *</label>
                <input type="text" name="currency" value="{{ old('currency', 'USD') }}" placeholder="USD" maxlength="8" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Effective from *</label>
                <input type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" required
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Effective to (optional)</label>
                <input type="date" name="effective_to" value="{{ old('effective_to') }}"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
            </div>
            <div class="col-span-2 sm:col-span-3 flex justify-end">
                <button type="submit"
                    class="h-9 px-5 rounded-xl bg-[color:var(--tw-accent)] text-white text-sm font-semibold hover:opacity-90 transition">
                    Add rate
                </button>
            </div>
        </form>
    </div>

    {{-- Rate table --}}
    @if($rates->isNotEmpty())
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-sm">
            <thead class="{{ $muted }} border-b {{ $border }} {{ $surface2 }}">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Product</th>
                    <th class="text-right px-3 py-3 font-semibold">Rate / 1000L</th>
                    <th class="text-left px-3 py-3 font-semibold">Currency</th>
                    <th class="text-left px-3 py-3 font-semibold">Effective from</th>
                    <th class="text-left px-3 py-3 font-semibold">Effective to</th>
                    <th class="text-left px-3 py-3 font-semibold">Notes</th>
                    <th class="text-center px-3 py-3 font-semibold">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y {{ $border }}">
                @foreach($rates as $r)
                @php
                    $today = now()->toDateString();
                    $isCurrentlyActive = $r->is_active
                        && $r->effective_from->format('Y-m-d') <= $today
                        && (!$r->effective_to || $r->effective_to->format('Y-m-d') >= $today);
                @endphp
                <tr class="{{ $isCurrentlyActive ? '' : 'opacity-60' }}">
                    <td class="px-5 py-3 font-semibold {{ $fg }}">{{ $r->product?->name ?? '—' }}</td>
                    <td class="px-3 py-3 text-right {{ $fg }} font-mono">{{ number_format($r->rate_per_1000l, 4) }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $r->currency }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $r->effective_from->format('d M Y') }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $r->effective_to ? $r->effective_to->format('d M Y') : '—' }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $r->notes ?? '—' }}</td>
                    <td class="px-3 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border
                            {{ $isCurrentlyActive
                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
                                : 'bg-slate-500/10 text-slate-500 border-slate-500/20' }}">
                            {{ $isCurrentlyActive ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-3 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button"
                                onclick="openEditRate({{ $r->id }}, {{ $r->rate_per_1000l }}, '{{ $r->currency }}', '{{ $r->effective_from->format('Y-m-d') }}', '{{ $r->effective_to?->format('Y-m-d') }}', '{{ addslashes($r->notes ?? '') }}')"
                                class="text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] underline">Edit</button>
                            <form method="POST" action="{{ route('settings.duty-rates.destroy', $r) }}"
                                  onsubmit="return confirm('Delete this duty rate?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-rose-500 hover:underline">Delete</button>
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
        No duty rates defined yet. Add one above.
    </div>
    @endif
</div>

{{-- Edit modal --}}
<div id="editRateModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5)">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} p-6 shadow-2xl">
        <h3 class="text-sm font-bold {{ $fg }} mb-4">Edit duty rate</h3>
        <form id="editRateForm" method="POST" class="grid grid-cols-2 gap-4">
            @csrf @method('PATCH')
            <div class="col-span-2">
                <label class="block text-xs font-semibold {{ $muted }} mb-1">
                    Rate per 1000 L *
                    <span class="font-normal opacity-70 ml-1">(e.g. enter 350, not 0.35)</span>
                </label>
                <input type="number" name="rate_per_1000l" id="er_rate" step="0.0001" min="0" required
                    oninput="checkDutyRate(this, 'edit_rate_warning')"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
                <div id="edit_rate_warning" class="hidden mt-1.5 rounded-lg border border-amber-400/40 bg-amber-400/10 px-3 py-2 text-xs text-amber-700 dark:text-amber-300"></div>
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Currency</label>
                <input type="text" name="currency" id="er_currency"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Effective from</label>
                <input type="date" name="effective_from" id="er_from"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Effective to</label>
                <input type="date" name="effective_to" id="er_to"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-semibold {{ $muted }} mb-1">Notes</label>
                <input type="text" name="notes" id="er_notes"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none">
            </div>
            <div class="col-span-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('editRateModal').classList.add('hidden')"
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
function checkDutyRate(input, warningId) {
    const val  = parseFloat(input.value);
    const warn = document.getElementById(warningId);
    if (!warn) return;

    if (!input.value || isNaN(val)) {
        warn.classList.add('hidden');
        return;
    }

    if (val < 1) {
        const suggested = Math.round(val * 1000 * 10000) / 10000;
        warn.textContent = '⚠ This looks like a per-litre rate. Rates here are per 1000 L — did you mean ' + suggested + '? (multiply your per-litre rate by 1000)';
        warn.classList.remove('hidden');
    } else if (val > 5000) {
        const suggested = Math.round(val / 1000 * 10000) / 10000;
        warn.textContent = '⚠ This seems very high for a per-1000L rate. If you entered a per-litre rate × 1000 by mistake, the correct value would be ' + suggested + '.';
        warn.classList.remove('hidden');
    } else {
        warn.classList.add('hidden');
    }
}

function openEditRate(id, rate, currency, from, to, notes) {
    document.getElementById('er_rate').value     = rate;
    document.getElementById('er_currency').value = currency;
    document.getElementById('er_from').value     = from;
    document.getElementById('er_to').value       = to || '';
    document.getElementById('er_notes').value    = notes;
    document.getElementById('editRateForm').action = '/settings/duty-rates/' + id;
    document.getElementById('editRateModal').classList.remove('hidden');
    // Trigger warning check when modal opens with existing value
    checkDutyRate(document.getElementById('er_rate'), 'edit_rate_warning');
}
</script>
@endsection
