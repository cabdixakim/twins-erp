@props(['supplier'])

@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
@endphp

@if(!$supplier)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 text-xs {{ $muted }}">
        No supplier selected yet.
    </div>
    @php return; @endphp
@endif

<div class="space-y-4">

    {{-- Header row --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide {{ $muted }}">Selected supplier</div>

            <div class="flex items-center gap-2 min-w-0">
                <h2 class="text-sm font-semibold truncate {{ $fg }}">{{ $supplier->name }}</h2>

                {{-- STATUS PILL (BRIGHT, NO DIM) --}}
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border
                    {{ $supplier->is_active
                        ? 'bg-emerald-600 text-white border-emerald-500/50'
                        : 'bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)] border-[color:var(--tw-border)]' }}">
                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <p class="text-[11px] {{ $muted }} truncate">
                {{ $supplier->type ?: 'Type not set' }}
                @if($supplier->city || $supplier->country)
                    • {{ $supplier->city }}{{ $supplier->city && $supplier->country ? ', ' : '' }}{{ $supplier->country }}
                @endif
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button type="button"
                    onclick="openSupplierEditModal()"
                    class="{{ $btnGhost }} px-3 py-1.5 text-[11px]">
                Edit
            </button>

            {{-- ACTIVATE/DEACTIVATE (BRIGHT, text-white) --}}
            <button type="button"
                    onclick="openSupplierStatusModal()"
                    class="px-3 py-1.5 rounded-xl text-[11px] font-semibold transition
                        {{ $supplier->is_active
                            ? 'bg-rose-600 hover:bg-rose-500 text-white'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white' }}">
                {{ $supplier->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>

    {{-- Small metrics/cards --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Default currency
            </div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">
                {{ $supplier->default_currency ?: '—' }}
            </div>
            <div class="text-[10px] {{ $muted }}">
                Used as default on purchases
            </div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Contact
            </div>
            <div class="mt-1 text-[11px] {{ $fg }}">
                {{ $supplier->contact_person ?: 'Not set' }}
            </div>
            <div class="text-[10px] {{ $muted }}">
                {{ $supplier->phone ?: 'No phone' }}
            </div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Email
            </div>
            <div class="mt-1 text-[11px] {{ $fg }} truncate">
                {{ $supplier->email ?: 'No email' }}
            </div>
            <div class="text-[10px] {{ $muted }}">
                For POs & documents
            </div>
        </div>
    </div>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-3">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">
            Notes
        </div>
        <p class="text-[11px] {{ $fg }} whitespace-pre-line">
            {{ $supplier->notes ?: 'No special notes for this supplier yet.' }}
        </p>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="supplierEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-md rounded-2xl {{ $surface }} border {{ $border }} p-4 m-3 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold {{ $fg }}">Edit supplier</h3>
            <button type="button"
                    class="{{ $btnGhost }} h-9 w-9 text-lg leading-none"
                    onclick="closeSupplierEditModal()">×</button>
        </div>

        @php
            $label = "block text-[11px] $muted mb-1";
            $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
            $select = $input;
            $textarea = $input;
        @endphp

        <form method="post" action="{{ route('settings.suppliers.update', $supplier) }}" class="space-y-3">
            @csrf
            @method('PATCH')

            <div>
                <label class="{{ $label }}">Name</label>
                <input type="text" name="name"
                       value="{{ old('name', $supplier->name) }}"
                       class="{{ $input }}">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Type</label>
                    <select name="type" class="{{ $select }}">
                        <option value="" @selected(!$supplier->type)>Not set</option>
                        <option value="port" @selected($supplier->type === 'port')>Port / terminal</option>
                        <option value="local_depot" @selected($supplier->type === 'local_depot')>Local depot</option>
                        <option value="trader" @selected($supplier->type === 'trader')>Trader</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">Default currency</label>
                    <input type="text" name="default_currency"
                           value="{{ old('default_currency', $supplier->default_currency) }}"
                           class="{{ $input }}">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Country</label>
                    <input type="text" name="country"
                           value="{{ old('country', $supplier->country) }}"
                           class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">City</label>
                    <input type="text" name="city"
                           value="{{ old('city', $supplier->city) }}"
                           class="{{ $input }}">
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Contact person</label>
                <input type="text" name="contact_person"
                       value="{{ old('contact_person', $supplier->contact_person) }}"
                       class="{{ $input }}">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Phone</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $supplier->phone) }}"
                           class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $supplier->email) }}"
                           class="{{ $input }}">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-600 focus:ring-emerald-500/40"
                       @checked(old('is_active', $supplier->is_active))>
                <label for="edit_is_active" class="text-[11px] {{ $fg }}">
                    Supplier is active
                </label>
            </div>

            <div>
                <label class="{{ $label }}">Notes</label>
                <textarea name="notes" rows="2" class="{{ $textarea }}">{{ old('notes', $supplier->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="{{ $btnGhost }} px-3 py-1.5 text-[11px]"
                        onclick="closeSupplierEditModal()">
                    Cancel
                </button>

                {{-- SAVE (BRIGHT) --}}
                <button type="submit"
                        class="px-4 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition border border-emerald-500/50">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- STATUS MODAL --}}
<div id="supplierStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-sm rounded-2xl {{ $surface }} border {{ $border }} p-4 m-3 shadow-[0_30px_90px_rgba(0,0,0,.45)]"
         onclick="event.stopPropagation()">

        <div class="mb-2">
            <h3 class="text-sm font-semibold {{ $fg }} mb-1">
                {{ $supplier->is_active ? 'Deactivate supplier?' : 'Activate supplier?' }}
            </h3>
            <p class="text-[11px] {{ $muted }}">
                {{ $supplier->is_active
                    ? 'Deactivated suppliers cannot be used for new purchases until re-activated.'
                    : 'Once activated, this supplier will be available when creating new purchases.' }}
            </p>
        </div>

        <form method="post" action="{{ route('settings.suppliers.toggle-active', $supplier) }}"
              class="flex justify-end gap-2 pt-2">
            @csrf
            @method('PATCH')

            <button type="button"
                    class="{{ $btnGhost }} px-3 py-1.5 text-[11px]"
                    onclick="closeSupplierStatusModal()">
                Cancel
            </button>

            {{-- CONFIRM (BRIGHT) --}}
            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold transition border
                        {{ $supplier->is_active
                            ? 'bg-rose-600 hover:bg-rose-500 text-white border-rose-500/50'
                            : 'bg-emerald-600 hover:bg-emerald-500 text-white border-emerald-500/50' }}">
                {{ $supplier->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
</div>

<script>
    function openSupplierEditModal() {
        const m = document.getElementById('supplierEditModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeSupplierEditModal() {
        const m = document.getElementById('supplierEditModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function openSupplierStatusModal() {
        const m = document.getElementById('supplierStatusModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeSupplierStatusModal() {
        const m = document.getElementById('supplierStatusModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // click-outside close
    document.getElementById('supplierEditModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeSupplierEditModal();
        }
    });

    document.getElementById('supplierStatusModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeSupplierStatusModal();
        }
    });
</script>