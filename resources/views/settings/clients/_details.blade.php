@props(['client'])

@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";

    $typeLabels = [
        'government'  => 'Government',
        'private'     => 'Private',
        'retail'      => 'Retail',
        'industrial'  => 'Industrial',
        'other'       => 'Other',
    ];

    $label  = "block text-[11px] $muted mb-1";
    $input  = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
@endphp

@if(!$client)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 text-xs {{ $muted }}">
        No client selected. Select one from the list or create a new one.
    </div>
    @php return; @endphp
@endif

@php
    $dispatchCount = $client->purchases()->where('status', 'dispatched')->count();
@endphp

<div class="space-y-4">

    {{-- Header row --}}
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide {{ $muted }}">Selected client</div>

            <div class="flex items-center gap-2 min-w-0 flex-wrap mt-0.5">
                <h2 class="text-sm font-semibold truncate {{ $fg }}">{{ $client->name }}</h2>

                @if($client->code)
                    <span class="text-[10px] px-1.5 py-0.5 rounded-lg border {{ $border }} {{ $surface2 }} {{ $fg }} font-mono">
                        {{ $client->code }}
                    </span>
                @endif

                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border
                    {{ $client->is_active
                        ? 'bg-emerald-600 text-white border-emerald-500/50'
                        : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]' }}">
                    {{ $client->is_active ? 'Active' : 'Inactive' }}
                </span>

                @if($client->type)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border s-blue">
                        {{ $typeLabels[$client->type] ?? ucfirst($client->type) }}
                    </span>
                @endif
            </div>

            <p class="text-[11px] {{ $muted }} mt-0.5">
                @if($client->city || $client->country)
                    {{ $client->city }}{{ $client->city && $client->country ? ', ' : '' }}{{ $client->country }}
                @else
                    Location not set
                @endif
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button type="button"
                    onclick="openClientEditModal()"
                    class="{{ $btnGhost }} px-3 py-1.5 text-[11px]">
                Edit
            </button>

            <button type="button"
                    onclick="openClientStatusModal()"
                    class="px-3 py-1.5 rounded-xl text-[11px] font-semibold transition
                        {{ $client->is_active
                            ? 'bg-rose-600 hover:bg-rose-500 text-white'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white' }}">
                {{ $client->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>

    {{-- Metrics row --}}
    <div class="grid gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">Dispatches</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $dispatchCount }}</div>
            <div class="text-[10px] {{ $muted }}">Total cross-dock</div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">Currency</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">{{ $client->currency ?: 'USD' }}</div>
            <div class="text-[10px] {{ $muted }}">Billing currency</div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">Credit limit</div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">
                {{ $client->credit_limit > 0 ? number_format((float)$client->credit_limit, 2) : '—' }}
            </div>
            <div class="text-[10px] {{ $muted }}">{{ $client->currency ?: 'USD' }}</div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">Contact</div>
            <div class="mt-1 text-[11px] {{ $fg }} truncate">{{ $client->contact_person ?: 'Not set' }}</div>
            <div class="text-[10px] {{ $muted }} truncate">{{ $client->phone ?: 'No phone' }}</div>
        </div>
    </div>

    {{-- Email + Notes --}}
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-3">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Email</div>
            <p class="text-[11px] {{ $fg }}">
                @if($client->email)
                    <a href="mailto:{{ $client->email }}" class="hover:text-[color:var(--tw-accent)] transition">
                        {{ $client->email }}
                    </a>
                @else
                    No email set
                @endif
            </p>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-3">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Notes</div>
            <p class="text-[11px] {{ $fg }} whitespace-pre-line">
                {{ $client->notes ?: 'No notes for this client yet.' }}
            </p>
        </div>
    </div>

</div>

{{-- EDIT MODAL --}}
<div id="clientEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-lg rounded-2xl {{ $surface }} border {{ $border }} p-4 m-3 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold {{ $fg }}">Edit client</h3>
            <button type="button"
                    class="{{ $btnGhost }} h-9 w-9 text-lg leading-none"
                    onclick="closeClientEditModal()">×</button>
        </div>

        <form method="POST" action="{{ route('settings.clients.update', $client) }}" class="space-y-3">
            @csrf
            @method('PATCH')

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required
                           value="{{ old('name', $client->name) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Code / Reference</label>
                    <input type="text" name="code"
                           value="{{ old('code', $client->code) }}" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Type</label>
                    <select name="type" class="{{ $input }}">
                        <option value="" @selected(!$client->type)>Not set</option>
                        <option value="government" @selected($client->type === 'government')>Government</option>
                        <option value="private"    @selected($client->type === 'private')>Private</option>
                        <option value="retail"     @selected($client->type === 'retail')>Retail</option>
                        <option value="industrial" @selected($client->type === 'industrial')>Industrial</option>
                        <option value="other"      @selected($client->type === 'other')>Other</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Currency</label>
                    <input type="text" name="currency" maxlength="3"
                           value="{{ old('currency', $client->currency ?? 'USD') }}" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Country</label>
                    <input type="text" name="country"
                           value="{{ old('country', $client->country) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">City</label>
                    <input type="text" name="city"
                           value="{{ old('city', $client->city) }}" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Contact person</label>
                    <input type="text" name="contact_person"
                           value="{{ old('contact_person', $client->contact_person) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Phone</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $client->phone) }}" class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $client->email) }}" class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Credit limit</label>
                    <input type="number" name="credit_limit" min="0" step="0.01"
                           value="{{ old('credit_limit', $client->credit_limit ?? 0) }}" class="{{ $input }}">
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Notes</label>
                <textarea name="notes" rows="2" class="{{ $input }}">{{ old('notes', $client->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="{{ $btnGhost }} px-3 py-1.5 text-[11px]"
                        onclick="closeClientEditModal()">Cancel</button>
                <button type="submit"
                        class="px-4 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition border border-emerald-500/50">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- STATUS TOGGLE MODAL --}}
<div id="clientStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-sm rounded-2xl {{ $surface }} border {{ $border }} p-4 m-3 shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="mb-2">
            <h3 class="text-sm font-semibold {{ $fg }} mb-1">
                {{ $client->is_active ? 'Deactivate client?' : 'Activate client?' }}
            </h3>
            <p class="text-[11px] {{ $muted }}">
                {{ $client->is_active
                    ? 'Deactivated clients will not appear in dispatch dropdowns until re-activated.'
                    : 'Once activated, this client will be available when dispatching purchases.' }}
            </p>
        </div>

        <form method="POST" action="{{ route('settings.clients.toggle-active', $client) }}"
              class="flex justify-end gap-2 pt-2">
            @csrf
            @method('PATCH')

            <button type="button"
                    class="{{ $btnGhost }} px-3 py-1.5 text-[11px]"
                    onclick="closeClientStatusModal()">Cancel</button>

            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold transition border
                        {{ $client->is_active
                            ? 'bg-rose-600 hover:bg-rose-500 text-white border-rose-500/50'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white border-emerald-500/50' }}">
                {{ $client->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
</div>

<script>
    function openClientEditModal() {
        const m = document.getElementById('clientEditModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeClientEditModal() {
        const m = document.getElementById('clientEditModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    function openClientStatusModal() {
        const m = document.getElementById('clientStatusModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeClientStatusModal() {
        const m = document.getElementById('clientStatusModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.getElementById('clientEditModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeClientEditModal();
    });
    document.getElementById('clientStatusModal')?.addEventListener('click', function (e) {
        if (e.target === this) closeClientStatusModal();
    });
</script>
