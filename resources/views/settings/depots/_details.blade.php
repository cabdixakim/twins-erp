@php
    /** @var \App\Models\Depot|null $depot */
@endphp

@if(!$depot)
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 text-xs text-slate-400">
        No depot selected yet.
    </div>
    @php return; @endphp
@endif

<div class="space-y-4">

    {{-- Header row: name + status + actions --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide text-slate-500">Selected depot</div>
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold truncate">{{ $depot->name }}</h2>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]
                    {{ $depot->is_active ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/50'
                                         : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                    {{ $depot->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <p class="text-[11px] text-slate-400">
                {{ $depot->city ?: 'City not set' }}
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            {{-- Edit button --}}
            <button
                type="button"
                onclick="openDepotEditModal()"
                class="px-3 py-1.5 rounded-xl border border-slate-700 bg-slate-900/70 text-[11px] hover:bg-slate-800">
                Edit
            </button>

            {{-- Activate / deactivate --}}
            <button
                type="button"
                onclick="openDepotStatusModal()"
                class="px-3 py-1.5 rounded-xl text-[11px]
                    {{ $depot->is_active
                        ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                        : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' }}">
                {{ $depot->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>

    {{-- Tiny depot metrics --}}
    <div class="grid gap-3 sm:grid-cols-3">

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Storage fee
            </div>
            <div class="mt-1 text-sm font-semibold">
                {{ number_format($depot->storage_fee_per_1000_l, 2) }} $
            </div>
            <div class="text-[10px] text-slate-500">
                per 1,000L / day
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Default shrinkage
            </div>
            <div class="mt-1 text-sm font-semibold">
                {{ number_format($depot->default_shrinkage_pct, 3) }} %
            </div>
            <div class="text-[10px] text-slate-500">
                Used unless a custom allowance is set
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide text-slate-500">
                Notes
            </div>
            <div class="mt-1 text-[11px] text-slate-300 line-clamp-3">
                {{ $depot->notes ?: 'No special instructions for this depot yet.' }}
            </div>
        </div>

    </div>
</div>

{{-- EDIT MODAL --}}
<div id="depotEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40">
    <div class="w-full max-w-md rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold">Edit depot</h3>
            <button type="button" class="text-slate-500 text-lg leading-none"
                    onclick="closeDepotEditModal()">×</button>
        </div>

        <form method="post" action="{{ route('settings.depots.update', $depot) }}" class="space-y-3">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Name</label>
                <input type="text" name="name"
                       value="{{ old('name', $depot->name) }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">City</label>
                <input type="text" name="city"
                       value="{{ old('city', $depot->city) }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">
                        Storage fee (per 1,000L / day)
                    </label>
                    <input type="number" step="0.01" min="0"
                           name="storage_fee_per_1000_l"
                           value="{{ old('storage_fee_per_1000_l', $depot->storage_fee_per_1000_l) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>

                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">
                        Default shrinkage %
                    </label>
                    <input type="number" step="0.001" min="0"
                           name="default_shrinkage_pct"
                           value="{{ old('default_shrinkage_pct', $depot->default_shrinkage_pct) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500/60"
                       @checked(old('is_active', $depot->is_active))>
                <label for="edit_is_active" class="text-[11px] text-slate-300">
                    Depot is active
                </label>
            </div>

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">{{ old('notes', $depot->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                        onclick="closeDepotEditModal()">
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

{{-- STATUS CONFIRM MODAL --}}
<div id="depotStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/40"
     onclick="closeDepotStatusModal()">
    <div class="w-full max-w-sm rounded-2xl bg-slate-950 border border-slate-800 p-4 m-3 shadow-xl"
         onclick="event.stopPropagation()">
        <div class="mb-2">
            <h3 class="text-sm font-semibold mb-1">
                {{ $depot->is_active ? 'Deactivate depot?' : 'Activate depot?' }}
            </h3>
            <p class="text-[11px] text-slate-400">
                {{ $depot->is_active
                    ? 'Deactivated depots cannot be used for new loads or sales until re-activated.'
                    : 'Once activated, this depot can be used for stock movements and sales.' }}
            </p>
        </div>

        <form method="post" action="{{ route('settings.depots.toggle-active', $depot) }}"
              class="flex justify-end gap-2 pt-2">
            @csrf
            @method('PATCH')

            <button type="button"
                    class="px-3 py-1.5 rounded-xl text-[11px] border border-slate-700 text-slate-300 hover:bg-slate-800"
                    onclick="closeDepotStatusModal()">
                Cancel
            </button>

            <button type="submit"
                    class="px-4 py-1.5 rounded-xl text-[11px] font-semibold
                        {{ $depot->is_active
                            ? 'bg-rose-500 hover:bg-rose-400 text-slate-950'
                            : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' }}">
                {{ $depot->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
</div>

<script>
    function openDepotEditModal() {
        const m = document.getElementById('depotEditModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeDepotEditModal() {
        const m = document.getElementById('depotEditModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function openDepotStatusModal() {
        const m = document.getElementById('depotStatusModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeDepotStatusModal() {
        const m = document.getElementById('depotStatusModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
</script>