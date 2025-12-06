@props(['supplier'])

@if(!$supplier)
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-xs text-slate-400">
        No supplier selected yet.
    </div>
    @php return; @endphp
@endif

<div class="space-y-4">

    {{-- Header row --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide text-slate-500">Selected supplier</div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold truncate">{{ $supplier->name }}</h2>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                    {{ $supplier->is_active ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/50'
                                             : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <p class="text-[11px] text-slate-400">
                {{ $supplier->type ?: 'Type not set' }}
                @if($supplier->city || $supplier->country)
                    • {{ $supplier->city }}{{ $supplier->city && $supplier->country ? ', ' : '' }}{{ $supplier->country }}
                @endif
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button type="button"
                    onclick="openSupplierEditModal()"
                    class="px-3 py-1.5 rounded-xl border border-slate-700 bg-slate-900/70 text-[11px] hover:bg-slate-800">
                Edit
            </button>

            <button type="button"
                    onclick="openSupplierStatusModal()"
                    class="px-3 py-1.5 rounded-xl text-[11px]
                        {{ $supplier->is_active
                            ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                            : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' }}">
                {{ $supplier->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>

    {{-- Small metrics/cards --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Default currency
            </div>
            <div class="mt-1 text-sm font-semibold">
                {{ $supplier->default_currency ?: '—' }}
            </div>
            <div class="text-[10px] text-slate-500">
                Used as default on purchases
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Contact
            </div>
            <div class="mt-1 text-[11px] text-slate-200">
                {{ $supplier->contact_person ?: 'Not set' }}
            </div>
            <div class="text-[10px] text-slate-500">
                {{ $supplier->phone ?: 'No phone' }}
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Email
            </div>
            <div class="mt-1 text-[11px] text-slate-200 truncate">
                {{ $supplier->email ?: 'No email' }}
            </div>
            <div class="text-[10px] text-slate-500">
                For POs & documents
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-3">
        <div class="text-[10px] uppercase tracking-wide text-slate-500 mb-1">
            Notes
        </div>
        <p class="text-[11px] text-slate-300 whitespace-pre-line">
            {{ $supplier->notes ?: 'No special notes for this supplier yet.' }}
        </p>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="supplierEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40">
    <div class="w-full max-w-md rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold">Edit supplier</h3>
            <button type="button" class="text-slate-500 text-lg leading-none"
                    onclick="closeSupplierEditModal()">×</button>
        </div>

        <form method="post" action="{{ route('settings.suppliers.update', $supplier) }}" class="space-y-3">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Name</label>
                <input type="text" name="name"
                       value="{{ old('name', $supplier->name) }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Type</label>
                    <select name="type"
                            class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                        <option value="" @selected(!$supplier->type)>Not set</option>
                        <option value="port" @selected($supplier->type === 'port')>Port / terminal</option>
                        <option value="local_depot" @selected($supplier->type === 'local_depot')>Local depot</option>
                        <option value="trader" @selected($supplier->type === 'trader')>Trader</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Default currency</label>
                    <input type="text" name="default_currency"
                           value="{{ old('default_currency', $supplier->default_currency) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Country</label>
                    <input type="text" name="country"
                           value="{{ old('country', $supplier->country) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">City</label>
                    <input type="text" name="city"
                           value="{{ old('city', $supplier->city) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Contact person</label>
                <input type="text" name="contact_person"
                       value="{{ old('contact_person', $supplier->contact_person) }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Phone</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $supplier->phone) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $supplier->email) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500/60"
                       @checked(old('is_active', $supplier->is_active))>
                <label for="edit_is_active" class="text-[11px] text-slate-300">
                    Supplier is active
                </label>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">{{ old('notes', $supplier->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                        onclick="closeSupplierEditModal()">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-1.5 rounded-xl text-[11px] font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- STATUS MODAL --}}
<div id="supplierStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40">
    <div class="w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">
        <div class="mb-2">
            <h3 class="text-sm font-semibold mb-1">
                {{ $supplier->is_active ? 'Deactivate supplier?' : 'Activate supplier?' }}
            </h3>
            <p class="text-[11px] text-slate-400">
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
                    class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                    onclick="closeSupplierStatusModal()">
                Cancel
            </button>

            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold
                        {{ $supplier->is_active
                            ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                            : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' }}">
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