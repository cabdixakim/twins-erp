@props(['transporter'])

@php
  $border   = "border-[color:var(--tw-border)]";
  $fg       = "text-[color:var(--tw-fg)]";
  $muted    = "text-[color:var(--tw-muted)]";
  $bg       = "bg-[color:var(--tw-bg)]";
  $surface  = "bg-[color:var(--tw-surface)]";
  $surface2 = "bg-[color:var(--tw-surface-2)]";
  $btn      = "bg-[color:var(--tw-btn)]";
  $btnHover = "hover:bg-[color:var(--tw-btn-hover)]";

  // Premium button bases
  $btnGhost = "px-3 py-1.5 rounded-xl text-[11px] font-semibold border $border $btn $fg $btnHover transition";
  $btnRing  = "focus:outline-none focus:ring-2 focus:ring-emerald-500/30";

  // Theme-aware “success” (no dim)
  $btnSuccess = "px-4 py-1.5 rounded-xl text-[11px] font-semibold transition $btnRing
                 border border-emerald-500/30
                 bg-emerald-600 text-white hover:bg-emerald-700
                 dark:bg-emerald-500/15 dark:text-emerald-100 dark:hover:bg-emerald-500/25";

  // Theme-aware “danger” premium
  $btnDanger = "px-4 py-1.5 rounded-xl text-[11px] font-semibold transition
                border border-rose-500/30
                bg-rose-600 text-white hover:bg-rose-700
                dark:bg-rose-500/15 dark:text-rose-100 dark:hover:bg-rose-500/25";

  // Pills (no dim)
  $pillActive = "bg-emerald-600/15 dark:bg-emerald-500/15 text-emerald-900 dark:text-emerald-100 border border-emerald-500/40";
  $pillInactive = "$surface2 text-[color:var(--tw-muted)] border $border";
@endphp

@if(!$transporter)
    <div class="rounded-2xl border {{ $border }} {{ $surface2 }} p-4 text-xs {{ $muted }}">
        No transporter selected yet.
    </div>
    @php return; @endphp
@endif

<div class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs uppercase tracking-wide {{ $muted }}">Selected transporter</div>

            <div class="flex items-center gap-2 min-w-0">
                <h2 class="text-sm font-semibold truncate {{ $fg }}">{{ $transporter->name }}</h2>

                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                    {{ $transporter->is_active ? $pillActive : $pillInactive }}">
                    {{ $transporter->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <p class="text-[11px] {{ $muted }} truncate">
                {{ $transporter->type === 'intl' ? 'International transporter'
                    : ($transporter->type === 'local' ? 'Local transporter' : 'Type not set') }}
                @if($transporter->city || $transporter->country)
                    • {{ $transporter->city }}{{ $transporter->city && $transporter->country ? ', ' : '' }}{{ $transporter->country }}
                @endif
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <button
                type="button"
                onclick="openTransporterEditModal()"
                class="{{ $btnGhost }} {{ $btnRing }}">
                Edit
            </button>

            <button
                type="button"
                onclick="openTransporterStatusModal()"
                class="{{ $transporter->is_active ? $btnDanger : $btnSuccess }}">
                {{ $transporter->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Default rate / 1,000L
            </div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">
                @if($transporter->default_rate_per_1000_l !== null)
                    {{ number_format($transporter->default_rate_per_1000_l, 4) }}
                    {{ $transporter->default_currency ?? 'USD' }}
                @else
                    —
                @endif
            </div>
            <div class="text-[10px] {{ $muted }}">
                Baseline for freight calculations
            </div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Payment terms
            </div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">
                {{ $transporter->payment_terms ?: 'Not set' }}
            </div>
            <div class="text-[10px] {{ $muted }}">
                Used on statements & settlements
            </div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Default currency
            </div>
            <div class="mt-1 text-sm font-semibold {{ $fg }}">
                {{ $transporter->default_currency ?: 'USD' }}
            </div>
            <div class="text-[10px] {{ $muted }}">
                Used for freight & short charges
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Contact
            </div>
            <div class="mt-1 text-[11px] {{ $fg }}">
                {{ $transporter->contact_person ?: 'Not set' }}
            </div>
            <div class="text-[10px] {{ $muted }}">
                {{ $transporter->phone ?: 'No phone' }}
            </div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-2">
            <div class="text-[10px] uppercase tracking-wide {{ $muted }}">
                Email
            </div>
            <div class="mt-1 text-[11px] {{ $fg }} truncate">
                {{ $transporter->email ?: 'No email' }}
            </div>
            <div class="text-[10px] {{ $muted }}">
                For POs, invoices & docs
            </div>
        </div>
    </div>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} px-3 py-3">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">
            Notes
        </div>
        <p class="text-[11px] {{ $muted }} whitespace-pre-line">
            {{ $transporter->notes ?: 'No special notes for this transporter yet.' }}
        </p>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="transporterEditModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-md rounded-2xl {{ $bg }} border {{ $border }} p-4 m-3 max-h-[90vh] overflow-y-auto shadow-[0_30px_90px_rgba(0,0,0,.60)]"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold {{ $fg }}">Edit transporter</h3>

            <button type="button"
                    class="h-9 w-9 grid place-items-center rounded-xl border {{ $border }} {{ $btn }} {{ $fg }} {{ $btnHover }} transition"
                    onclick="closeTransporterEditModal()"
                    aria-label="Close">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>
        </div>

        <form method="post" action="{{ route('settings.transporters.update', $transporter) }}" class="space-y-3">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Name</label>
                <input type="text" name="name"
                       value="{{ old('name', $transporter->name) }}"
                       class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                              placeholder:text-[color:var(--tw-muted)]
                              focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Type</label>
                    <select name="type"
                            class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                   focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                        <option value="" @selected(!$transporter->type)>Not set</option>
                        <option value="intl" @selected($transporter->type === 'intl')>International</option>
                        <option value="local" @selected($transporter->type === 'local')>Local</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Default currency</label>
                    <input type="text" name="default_currency"
                           value="{{ old('default_currency', $transporter->default_currency) }}"
                           class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Country</label>
                    <input type="text" name="country"
                           value="{{ old('country', $transporter->country) }}"
                           class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">City</label>
                    <input type="text" name="city"
                           value="{{ old('city', $transporter->city) }}"
                           class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
            </div>

            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Contact person</label>
                <input type="text" name="contact_person"
                       value="{{ old('contact_person', $transporter->contact_person) }}"
                       class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                              focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Phone</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $transporter->phone) }}"
                           class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $transporter->email) }}"
                           class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">
                        Default rate (per 1,000L)
                    </label>
                    <input type="number" name="default_rate_per_1000_l" step="0.0001" min="0"
                           value="{{ old('default_rate_per_1000_l', $transporter->default_rate_per_1000_l) }}"
                           class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
                <div>
                    <label class="block text-[11px] {{ $muted }} mb-1">Payment terms</label>
                    <input type="text" name="payment_terms"
                           value="{{ old('payment_terms', $transporter->payment_terms) }}"
                           class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                  focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" id="edit_transporter_is_active" name="is_active" value="1"
                       class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)]
                              text-emerald-600 focus:ring-2 focus:ring-emerald-500/30"
                       @checked(old('is_active', $transporter->is_active))>
                <label for="edit_transporter_is_active" class="text-[11px] {{ $fg }}">
                    Transporter is active
                </label>
            </div>

            <div>
                <label class="block text-[11px] {{ $muted }} mb-1">Notes</label>
                <textarea name="notes" rows="2"
                          class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}
                                 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">{{ old('notes', $transporter->notes) }}</textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="{{ $btnGhost }} {{ $btnRing }}"
                        onclick="closeTransporterEditModal()">
                    Cancel
                </button>
                <button type="submit"
                        class="{{ $btnSuccess }}">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- STATUS MODAL --}}
<div id="transporterStatusModal"
     class="fixed inset-0 z-40 hidden items-end sm:items-center justify-center bg-black/55">
    <div class="w-full max-w-sm rounded-2xl {{ $bg }} border {{ $border }} p-4 m-3 shadow-[0_30px_90px_rgba(0,0,0,.60)]"
         onclick="event.stopPropagation()">

        <div class="mb-2">
            <h3 class="text-sm font-semibold mb-1 {{ $fg }}">
                {{ $transporter->is_active ? 'Deactivate transporter?' : 'Activate transporter?' }}
            </h3>
            <p class="text-[11px] {{ $muted }}">
                {{ $transporter->is_active
                    ? 'Deactivated transporters cannot receive new loads until re-activated.'
                    : 'Once activated, this transporter can be used for new intl/local trips.' }}
            </p>
        </div>

        <form method="post" action="{{ route('settings.transporters.toggle-active', $transporter) }}"
              class="flex justify-end gap-2 pt-2">
            @csrf
            @method('PATCH')

            <button type="button"
                    class="{{ $btnGhost }}"
                    onclick="closeTransporterStatusModal()">
                Cancel
            </button>

            <button type="submit"
                    class="{{ $transporter->is_active ? $btnDanger : $btnSuccess }}">
                {{ $transporter->is_active ? 'Deactivate' : 'Activate' }}
            </button>
        </form>
    </div>
</div>

<script>
    function openTransporterEditModal() {
        const m = document.getElementById('transporterEditModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeTransporterEditModal() {
        const m = document.getElementById('transporterEditModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function openTransporterStatusModal() {
        const m = document.getElementById('transporterStatusModal');
        if (!m) return;
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeTransporterStatusModal() {
        const m = document.getElementById('transporterStatusModal');
        if (!m) return;
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    document.getElementById('transporterEditModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeTransporterEditModal();
        }
    });

    document.getElementById('transporterStatusModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeTransporterStatusModal();
        }
    });
</script>