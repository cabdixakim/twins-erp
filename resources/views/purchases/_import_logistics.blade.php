{{-- ==================================================================
     IMPORT LOGISTICS — truck nominations, loading, transit, delivery
     Variables expected: $purchase, $importNomination, $transporters,
     $depots, $qty, $currency, + theme vars ($fg $muted $border $surface $surface2)
     ================================================================== --}}
@php
  $nom  = $importNomination;   // shorthand
  $trucks = $nom ? $nom->trucks : collect();

  // Summary totals
  $totalCapacity  = $trucks->sum('capacity');
  $loadedTrucks   = $trucks->whereNotIn('status', ['nominated', 'loading_failed']);
  $qtyLoaded      = $loadedTrucks->sum('qty_loaded');
  $deliveredTrucks= $trucks->where('status', 'delivered');
  $qtyDelivered   = $deliveredTrucks->sum('qty_delivered');
  $remainingAtShipper = max(0, $qty - $qtyLoaded);

  // Financial
  $grossPayable        = $nom ? ($qtyLoaded * (float)$nom->rate_per_1000l / 1000) : 0;
  $totalShortCharge    = $deliveredTrucks->sum('shortfall_charge');
  $netPayable          = $grossPayable - (float)($nom->advances ?? 0) - $totalShortCharge;

  // Default allowed loss pct (0.3% AGO, 0.5% PMS)
  $productCode = strtoupper((string)($purchase->product->code ?? ''));
  $defaultLossPct = $productCode === 'PMS' ? 0.5 : 0.3;
@endphp

{{-- =================== LOGISTICS CARD =================== --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }}">

  {{-- Header --}}
  <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b {{ $border }}">
    <div class="flex items-center gap-3">
      <svg class="w-4 h-4 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0121 11.414V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0M15 17a2 2 0 104 0"/>
      </svg>
      <span class="text-sm font-semibold {{ $fg }}">Import logistics</span>
      @if($nom)
        <span class="text-xs {{ $muted }}">· {{ $nom->transporter?->name ?? 'No transporter' }}</span>
      @endif
    </div>
    <div class="flex items-center gap-2">
      @if($nom)
        <button type="button" id="btnEditNomination"
                class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }}
                       hover:bg-[color:var(--tw-surface-2)] transition">
          Edit nomination
        </button>
        <button type="button" id="btnAddTruck"
                class="h-8 px-3 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10
                       text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
          + Add truck
        </button>
      @else
        <button type="button" id="btnSetupNomination"
                class="h-8 px-3 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10
                       text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
          Set up nomination
        </button>
      @endif
    </div>
  </div>

  @if(!$nom)
    {{-- Empty state --}}
    <div class="p-10 text-center">
      <svg class="w-10 h-10 mx-auto mb-3 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0121 11.414V16a1 1 0 01-1 1h-1"/>
      </svg>
      <div class="text-sm font-semibold {{ $fg }} mb-1">No nomination yet</div>
      <div class="text-xs {{ $muted }} mb-4">Set up the transporter, rate and allowed loss to begin tracking trucks.</div>
    </div>
  @else

    {{-- Nomination meta strip --}}
    <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div>
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Rate</div>
        <div class="text-sm font-semibold {{ $fg }}">{{ $nom->currency }} {{ number_format($nom->rate_per_1000l, 2) }}<span class="text-xs font-normal {{ $muted }}"> /1000L</span></div>
      </div>
      <div>
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Allowed loss</div>
        <div class="text-sm font-semibold {{ $fg }}">{{ number_format($nom->allowed_loss_pct, 2) }}%</div>
      </div>
      <div>
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Short charge</div>
        <div class="text-sm font-semibold {{ $fg }}">{{ $nom->short_charge_currency }} {{ number_format($nom->short_charge_rate, 2) }}<span class="text-xs font-normal {{ $muted }}"> /1000L</span></div>
      </div>
      <div>
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Trucks</div>
        <div class="text-sm font-semibold {{ $fg }}">{{ $trucks->count() }}</div>
      </div>
    </div>

    {{-- Summary metrics --}}
    <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 gap-3 border-b {{ $border }}">
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Nominated capacity</div>
        <div class="mt-1 text-base font-bold {{ $fg }}">{{ number_format($totalCapacity, 0) }}</div>
        <div class="text-[10px] {{ $muted }}">L</div>
      </div>
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Qty loaded</div>
        <div class="mt-1 text-base font-bold {{ $fg }}">{{ number_format($qtyLoaded, 0) }}</div>
        <div class="text-[10px] {{ $muted }}">L</div>
      </div>
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Qty delivered</div>
        <div class="mt-1 text-base font-bold s-green">{{ number_format($qtyDelivered, 0) }}</div>
        <div class="text-[10px] {{ $muted }}">L</div>
      </div>
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Remaining at shipper</div>
        <div class="mt-1 text-base font-bold {{ $remainingAtShipper > 0 ? 's-amber' : $fg }}">
          {{ number_format($remainingAtShipper, 0) }}
        </div>
        <div class="text-[10px] {{ $muted }}">L</div>
      </div>
    </div>

    {{-- Truck table --}}
    @if($trucks->isNotEmpty())
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="{{ $muted }} border-b {{ $border }} bg-[color:var(--tw-surface-2)]">
              <th class="text-left py-2 pl-5 pr-3 font-semibold whitespace-nowrap">Truck / Trailer</th>
              <th class="text-left py-2 pr-3 font-semibold whitespace-nowrap">Driver</th>
              <th class="text-right py-2 pr-3 font-semibold whitespace-nowrap">Capacity (L)</th>
              <th class="text-right py-2 pr-3 font-semibold whitespace-nowrap">Loaded (L)</th>
              <th class="text-right py-2 pr-3 font-semibold whitespace-nowrap">Delivered (L)</th>
              <th class="text-right py-2 pr-3 font-semibold whitespace-nowrap">Shortfall chg</th>
              <th class="text-center py-2 pr-3 font-semibold whitespace-nowrap">Status</th>
              <th class="text-right py-2 pr-5 font-semibold whitespace-nowrap">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($trucks as $truck)
              @php
                $truckActions = $truck->nextActions();
                $truckLabel   = $truck->statusLabel();
                $truckColor   = $truck->statusColor();
              @endphp
              <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                <td class="py-2.5 pl-5 pr-3 {{ $fg }} font-mono font-semibold whitespace-nowrap">
                  {{ $truck->truck_reg ?: '—' }}
                  @if($truck->trailer_reg)
                    <span class="font-normal {{ $muted }}"> / {{ $truck->trailer_reg }}</span>
                  @endif
                </td>
                <td class="py-2.5 pr-3 {{ $fg }} whitespace-nowrap">
                  {{ $truck->driver_name ?: '—' }}
                  @if($truck->driver_phone)
                    <span class="block {{ $muted }}">{{ $truck->driver_phone }}</span>
                  @endif
                </td>
                <td class="py-2.5 pr-3 text-right {{ $fg }} font-semibold">{{ number_format($truck->capacity, 0) }}</td>
                <td class="py-2.5 pr-3 text-right {{ $fg }}">
                  @if($truck->qty_loaded !== null)
                    {{ number_format($truck->qty_loaded, 0) }}
                    @if($truck->pickup_date)
                      <span class="block {{ $muted }}">{{ $truck->pickup_date->format('d M') }}</span>
                    @endif
                  @else
                    <span class="{{ $muted }}">—</span>
                  @endif
                </td>
                <td class="py-2.5 pr-3 text-right {{ $fg }}">
                  @if($truck->qty_delivered !== null)
                    {{ number_format($truck->qty_delivered, 0) }}
                    @if($truck->delivery_date)
                      <span class="block {{ $muted }}">{{ $truck->delivery_date->format('d M') }}</span>
                    @endif
                  @else
                    <span class="{{ $muted }}">—</span>
                  @endif
                </td>
                <td class="py-2.5 pr-3 text-right">
                  @if($truck->status === 'delivered')
                    @if($truck->excess_loss_qty > 0)
                      <span class="s-rose font-semibold">{{ $nom->short_charge_currency }} {{ number_format($truck->shortfall_charge, 2) }}</span>
                      <span class="block {{ $muted }}">{{ number_format($truck->excess_loss_qty, 0) }} L excess</span>
                    @else
                      <span class="s-green">Within tolerance</span>
                    @endif
                  @else
                    <span class="{{ $muted }}">—</span>
                  @endif
                </td>
                <td class="py-2.5 pr-3 text-center whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $truckColor }}">
                    {{ $truckLabel }}
                  </span>
                </td>
                <td class="py-2.5 pr-5 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1">
                    {{-- Edit (only nominated/loading_failed) --}}
                    @if(in_array($truck->status, ['nominated', 'loading_failed']))
                      <button type="button"
                              onclick="openTruckModal('editTruckModal-{{ $truck->id }}')"
                              class="h-7 px-2 rounded-lg border {{ $border }} {{ $surface }} text-[11px] {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                        Edit
                      </button>
                    @endif
                    @if(in_array('record_load', $truckActions))
                      <button type="button"
                              onclick="openTruckModal('loadModal-{{ $truck->id }}')"
                              class="h-7 px-2 rounded-lg border border-blue-500/40 bg-blue-600/10 text-[11px] font-semibold s-blue hover:bg-blue-600/20 transition">
                        Record load
                      </button>
                    @endif
                    @if(in_array('fail_load', $truckActions))
                      <button type="button"
                              onclick="openTruckModal('failLoadModal-{{ $truck->id }}')"
                              class="h-7 px-2 rounded-lg border border-rose-500/40 bg-rose-600/10 text-[11px] font-semibold s-rose hover:bg-rose-600/20 transition">
                        Fail
                      </button>
                    @endif
                    @if(in_array('mark_in_transit', $truckActions))
                      <form method="POST"
                            action="{{ route('purchases.import-nomination.trucks.mark-in-transit', [$purchase, $nom, $truck]) }}"
                            onsubmit="return confirm('Mark {{ $truck->truck_reg }} as in transit?')">
                        @csrf
                        <button type="submit"
                                class="h-7 px-2 rounded-lg border border-amber-500/40 bg-amber-600/10 text-[11px] font-semibold s-amber hover:bg-amber-600/20 transition">
                          In transit
                        </button>
                      </form>
                    @endif
                    @if(in_array('record_border', $truckActions))
                      <button type="button"
                              onclick="openTruckModal('borderModal-{{ $truck->id }}')"
                              class="h-7 px-2 rounded-lg border border-purple-500/40 bg-purple-600/10 text-[11px] font-semibold s-purple hover:bg-purple-600/20 transition">
                        Border
                      </button>
                    @endif
                    @if(in_array('record_delivery', $truckActions))
                      <button type="button"
                              onclick="openTruckModal('deliveryModal-{{ $truck->id }}')"
                              class="h-7 px-2 rounded-lg border border-green-500/40 bg-green-600/10 text-[11px] font-semibold s-green hover:bg-green-600/20 transition">
                        Deliver
                      </button>
                    @endif
                    {{-- Show clearance info on delivered --}}
                    @if($truck->status === 'delivered' && ($truck->tr8_number || $truck->t1_number))
                      <span class="text-[10px] {{ $muted }}" title="TR8: {{ $truck->tr8_number }} | T1: {{ $truck->t1_number }}">
                        TR8 ✓
                      </span>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="px-5 py-8 text-center">
        <div class="text-xs {{ $muted }}">No trucks added yet. Click "+ Add truck" to begin.</div>
      </div>
    @endif

    {{-- Financial summary --}}
    <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }}">
      <div class="text-xs font-semibold {{ $muted }} uppercase tracking-wide mb-3">Transporter payable</div>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
          <div class="text-[10px] {{ $muted }}">Gross (loaded × rate)</div>
          <div class="font-semibold {{ $fg }}">{{ $nom->currency }} {{ number_format($grossPayable, 2) }}</div>
        </div>
        <div>
          <div class="text-[10px] {{ $muted }}">Advances</div>
          <div class="font-semibold s-amber">− {{ $nom->advances_currency }} {{ number_format($nom->advances, 2) }}</div>
        </div>
        <div>
          <div class="text-[10px] {{ $muted }}">Short charges</div>
          <div class="font-semibold {{ $totalShortCharge > 0 ? 's-rose' : $muted }}">
            − {{ $nom->short_charge_currency }} {{ number_format($totalShortCharge, 2) }}
          </div>
        </div>
        <div class="border-l {{ $border }} pl-4">
          <div class="text-[10px] {{ $muted }}">Net payable</div>
          <div class="text-base font-bold {{ $netPayable >= 0 ? $fg : 's-rose' }}">
            {{ $nom->currency }} {{ number_format($netPayable, 2) }}
          </div>
        </div>
      </div>
    </div>
  @endif
</div>


{{-- ================================================================
     MODALS
     ================================================================ --}}

{{-- ── Nomination modal (create OR edit) ── --}}
<div id="nominationModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
  <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
      <div class="text-base font-semibold {{ $fg }}">{{ $nom ? 'Edit nomination' : 'Set up nomination' }}</div>
      <button type="button" onclick="closeTruckModal('nominationModal')"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
    </div>
    <form method="POST"
          action="{{ $nom
            ? route('purchases.import-nomination.update', [$purchase, $nom])
            : route('purchases.import-nomination.store', $purchase) }}">
      @csrf
      @if($nom) @method('PATCH') @endif
      <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">

        {{-- Transporter --}}
        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Transporter</label>
          <select name="transporter_id"
                  class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
            <option value="">— none —</option>
            @foreach($transporters as $tp)
              <option value="{{ $tp->id }}" {{ ($nom && $nom->transporter_id == $tp->id) ? 'selected' : '' }}>
                {{ $tp->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          {{-- Currency --}}
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Currency</label>
            <select name="currency"
                    class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
              @foreach(['USD','EUR','ZAR','CDF','ZMW'] as $cur)
                <option value="{{ $cur }}" {{ ($nom ? $nom->currency : 'USD') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
              @endforeach
            </select>
          </div>
          {{-- Rate per 1000L --}}
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Transport rate <span class="{{ $muted }}">/1000L</span></label>
            <input type="number" name="rate_per_1000l" step="0.01" min="0" required
                   value="{{ $nom ? $nom->rate_per_1000l : '' }}"
                   placeholder="0.00"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          {{-- Allowed loss pct --}}
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Allowed loss % <span class="{{ $muted }}">(default {{ $defaultLossPct }}%)</span></label>
            <input type="number" name="allowed_loss_pct" step="0.01" min="0" max="100" required
                   value="{{ $nom ? $nom->allowed_loss_pct : $defaultLossPct }}"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          {{-- Short charge currency --}}
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Short charge currency</label>
            <select name="short_charge_currency"
                    class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
              @foreach(['USD','EUR','ZAR','CDF','ZMW'] as $cur)
                <option value="{{ $cur }}" {{ ($nom ? $nom->short_charge_currency : 'USD') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Short charge rate --}}
        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Short charge rate <span class="{{ $muted }}">/1000L of excess loss</span></label>
          <input type="number" name="short_charge_rate" step="0.01" min="0" required
                 value="{{ $nom ? $nom->short_charge_rate : '' }}"
                 placeholder="0.00"
                 class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
        </div>

        <div class="grid grid-cols-2 gap-3">
          {{-- Advances --}}
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Advances paid</label>
            <input type="number" name="advances" step="0.01" min="0"
                   value="{{ $nom ? $nom->advances : '0' }}"
                   placeholder="0.00"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Advances currency</label>
            <select name="advances_currency"
                    class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
              @foreach(['USD','EUR','ZAR','CDF','ZMW'] as $cur)
                <option value="{{ $cur }}" {{ ($nom ? $nom->advances_currency : 'USD') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Notes --}}
        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Notes <span class="{{ $muted }}">(optional)</span></label>
          <textarea name="notes" rows="2" placeholder="Any remarks about this nomination…"
                    class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40 resize-none">{{ $nom?->notes }}</textarea>
        </div>
      </div>
      <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex justify-end gap-2">
        <button type="button" onclick="closeTruckModal('nominationModal')"
                class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
          Cancel
        </button>
        <button type="submit"
                class="h-10 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)] text-sm font-semibold text-white hover:opacity-90 transition">
          {{ $nom ? 'Save changes' : 'Create nomination' }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ── Add truck modal ── --}}
@if($nom)
<div id="addTruckModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
  <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
    <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
      <div class="text-base font-semibold {{ $fg }}">Add truck</div>
      <button type="button" onclick="closeTruckModal('addTruckModal')"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
    </div>
    <form method="POST" action="{{ route('purchases.import-nomination.trucks.store', [$purchase, $nom]) }}">
      @csrf
      <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Truck registration</label>
            <input type="text" name="truck_reg" placeholder="e.g. KCA 123A" maxlength="40"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Trailer registration</label>
            <input type="text" name="trailer_reg" placeholder="e.g. TRLR-001" maxlength="40"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Capacity (litres) <span class="text-rose-400">*</span></label>
          <input type="number" name="capacity" step="0.001" min="1" required placeholder="e.g. 45000"
                 class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Driver name</label>
            <input type="text" name="driver_name" maxlength="150"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Driver phone</label>
            <input type="text" name="driver_phone" maxlength="30"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Passport #</label>
            <input type="text" name="driver_passport" maxlength="60"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">License #</label>
            <input type="text" name="driver_license" maxlength="60"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Notes</label>
          <input type="text" name="notes" maxlength="1000"
                 class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
        </div>
      </div>
      <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex justify-end gap-2">
        <button type="button" onclick="closeTruckModal('addTruckModal')"
                class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
          Cancel
        </button>
        <button type="submit"
                class="h-10 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)] text-sm font-semibold text-white hover:opacity-90 transition">
          Add truck
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ── Per-truck modals ── --}}
@foreach($trucks as $truck)

  {{-- Edit truck modal --}}
  @if(in_array($truck->status, ['nominated', 'loading_failed']))
  <div id="editTruckModal-{{ $truck->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-lg rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-base font-semibold {{ $fg }}">Edit truck — {{ $truck->truck_reg }}</div>
        <button type="button" onclick="closeTruckModal('editTruckModal-{{ $truck->id }}')"
                class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
      </div>
      <form method="POST"
            action="{{ route('purchases.import-nomination.trucks.update', [$purchase, $nom, $truck]) }}">
        @csrf @method('PATCH')
        <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Truck registration</label>
              <input type="text" name="truck_reg" value="{{ $truck->truck_reg }}" maxlength="40"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Trailer registration</label>
              <input type="text" name="trailer_reg" value="{{ $truck->trailer_reg }}" maxlength="40"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Capacity (litres) <span class="text-rose-400">*</span></label>
            <input type="number" name="capacity" step="0.001" min="1" required value="{{ $truck->capacity }}"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Driver name</label>
              <input type="text" name="driver_name" value="{{ $truck->driver_name }}" maxlength="150"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Driver phone</label>
              <input type="text" name="driver_phone" value="{{ $truck->driver_phone }}" maxlength="30"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Passport #</label>
              <input type="text" name="driver_passport" value="{{ $truck->driver_passport }}" maxlength="60"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">License #</label>
              <input type="text" name="driver_license" value="{{ $truck->driver_license }}" maxlength="60"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Notes</label>
            <input type="text" name="notes" value="{{ $truck->notes }}" maxlength="1000"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
        </div>
        <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex justify-end gap-2">
          <button type="button" onclick="closeTruckModal('editTruckModal-{{ $truck->id }}')"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)] text-sm font-semibold text-white hover:opacity-90 transition">
            Save
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Record load modal --}}
  @if($truck->status === 'nominated')
  <div id="loadModal-{{ $truck->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-base font-semibold {{ $fg }}">Record loading — {{ $truck->truck_reg }}</div>
        <button type="button" onclick="closeTruckModal('loadModal-{{ $truck->id }}')"
                class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
      </div>
      <form method="POST"
            action="{{ route('purchases.import-nomination.trucks.record-load', [$purchase, $nom, $truck]) }}">
        @csrf
        <div class="p-5 space-y-4">
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $muted }}">
            Capacity: <span class="font-semibold {{ $fg }}">{{ number_format($truck->capacity, 0) }} L</span>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Qty loaded (L) <span class="text-rose-400">*</span></label>
              <input type="number" name="qty_loaded" step="0.001" min="1" required
                     placeholder="e.g. 44850"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
            </div>
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Pick-up date <span class="text-rose-400">*</span></label>
              <input type="date" name="pickup_date" required
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Loading terminal</label>
            <input type="text" name="pickup_terminal" maxlength="200" placeholder="e.g. Dar es Salaam terminal"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Notes</label>
            <input type="text" name="load_notes" maxlength="1000"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
          </div>
        </div>
        <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex justify-end gap-2">
          <button type="button" onclick="closeTruckModal('loadModal-{{ $truck->id }}')"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-blue-500/40 bg-blue-600 text-sm font-semibold text-white hover:bg-blue-500 transition">
            Record load
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Fail load modal --}}
  @if($truck->status === 'nominated')
  <div id="failLoadModal-{{ $truck->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-base font-semibold {{ $fg }}">Mark loading failed — {{ $truck->truck_reg }}</div>
        <button type="button" onclick="closeTruckModal('failLoadModal-{{ $truck->id }}')"
                class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
      </div>
      <form method="POST"
            action="{{ route('purchases.import-nomination.trucks.fail-load', [$purchase, $nom, $truck]) }}">
        @csrf
        <div class="p-5 space-y-4">
          <div class="alert-err rounded-xl p-3 text-sm">
            This truck will be marked as <strong>loading failed</strong>. The capacity ({{ number_format($truck->capacity, 0) }} L) will remain as unloaded quantity at the shipper.
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Reason</label>
            <input type="text" name="load_notes" maxlength="1000" placeholder="e.g. truck breakdown, overloading restriction…"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-rose-500/30" />
          </div>
        </div>
        <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex justify-end gap-2">
          <button type="button" onclick="closeTruckModal('failLoadModal-{{ $truck->id }}')"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-rose-500/40 bg-rose-600 text-sm font-semibold text-white hover:bg-rose-500 transition">
            Mark failed
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Border clearance modal --}}
  @if($truck->status === 'in_transit')
  <div id="borderModal-{{ $truck->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-base font-semibold {{ $fg }}">DRC border clearance — {{ $truck->truck_reg }}</div>
        <button type="button" onclick="closeTruckModal('borderModal-{{ $truck->id }}')"
                class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
      </div>
      <form method="POST"
            action="{{ route('purchases.import-nomination.trucks.record-border', [$purchase, $nom, $truck]) }}">
        @csrf
        <div class="p-5 space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">TR8 number</label>
              <input type="text" name="tr8_number" maxlength="80" placeholder="TR8-…"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-purple-500/40" />
            </div>
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">T1 number</label>
              <input type="text" name="t1_number" maxlength="80" placeholder="T1-…"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-purple-500/40" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Border date <span class="text-rose-400">*</span></label>
            <input type="date" name="border_date" required
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-purple-500/40" />
          </div>
        </div>
        <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex justify-end gap-2">
          <button type="button" onclick="closeTruckModal('borderModal-{{ $truck->id }}')"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-purple-500/40 bg-purple-600 text-sm font-semibold text-white hover:bg-purple-500 transition">
            Confirm clearance
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Delivery modal --}}
  @if($truck->status === 'border_cleared')
  <div id="deliveryModal-{{ $truck->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
    <div class="w-full max-w-md rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl overflow-hidden">
      <div class="flex items-center justify-between p-5 border-b {{ $border }} {{ $surface2 }}">
        <div class="text-base font-semibold {{ $fg }}">Record delivery — {{ $truck->truck_reg }}</div>
        <button type="button" onclick="closeTruckModal('deliveryModal-{{ $truck->id }}')"
                class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
      </div>
      <form method="POST"
            action="{{ route('purchases.import-nomination.trucks.record-delivery', [$purchase, $nom, $truck]) }}">
        @csrf
        <div class="p-5 space-y-4">
          <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 grid grid-cols-2 gap-2 text-xs">
            <div>
              <div class="{{ $muted }}">Loaded</div>
              <div class="font-semibold {{ $fg }}">{{ number_format($truck->qty_loaded, 3) }} L</div>
            </div>
            <div>
              <div class="{{ $muted }}">Allowed loss ({{ $nom->allowed_loss_pct }}%)</div>
              <div class="font-semibold {{ $fg }}">{{ number_format($truck->qty_loaded * $nom->allowed_loss_pct / 100, 3) }} L</div>
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Depot <span class="text-rose-400">*</span></label>
            <select name="depot_id" required
                    class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-green-500/40">
              <option value="">— select depot —</option>
              @foreach($depots as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Qty delivered (L) <span class="text-rose-400">*</span></label>
              <input type="number" name="qty_delivered" step="0.001" min="0" required
                     placeholder="e.g. 44720"
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-green-500/40" />
            </div>
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Delivery date <span class="text-rose-400">*</span></label>
              <input type="date" name="delivery_date" required
                     class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-green-500/40" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Delivery notes</label>
            <input type="text" name="delivery_notes" maxlength="1000"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-green-500/40" />
          </div>
          <div class="alert-warn rounded-xl p-3 text-xs">
            Shortfall beyond {{ $nom->allowed_loss_pct }}% will be charged at {{ $nom->short_charge_currency }} {{ number_format($nom->short_charge_rate, 2) }} / 1000 L.
          </div>
        </div>
        <div class="px-5 py-4 border-t {{ $border }} {{ $surface2 }} flex justify-end gap-2">
          <button type="button" onclick="closeTruckModal('deliveryModal-{{ $truck->id }}')"
                  class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>
          <button type="submit"
                  class="h-10 px-4 rounded-xl border border-green-500/40 bg-green-600 text-sm font-semibold text-white hover:bg-green-500 transition">
            Confirm delivery
          </button>
        </div>
      </form>
    </div>
  </div>
  @endif

@endforeach
@endif

{{-- ── JS for this section ── --}}
<script>
(function () {
  function openTruckModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    document.documentElement.classList.add('overflow-hidden');
  }
  function closeTruckModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    document.documentElement.classList.remove('overflow-hidden');
  }

  // Expose globally for onclick handlers
  window.openTruckModal  = openTruckModal;
  window.closeTruckModal = closeTruckModal;

  // Setup / Edit nomination buttons
  const btnSetup = document.getElementById('btnSetupNomination');
  const btnEdit  = document.getElementById('btnEditNomination');
  if (btnSetup) btnSetup.addEventListener('click', () => openTruckModal('nominationModal'));
  if (btnEdit)  btnEdit.addEventListener('click',  () => openTruckModal('nominationModal'));

  // Add truck button
  const btnAdd = document.getElementById('btnAddTruck');
  if (btnAdd) btnAdd.addEventListener('click', () => openTruckModal('addTruckModal'));

  // ESC closes all truck modals
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('[id^="loadModal-"], [id^="failLoadModal-"], [id^="borderModal-"], [id^="deliveryModal-"], [id^="editTruckModal-"], #addTruckModal, #nominationModal')
      .forEach(el => el.classList.add('hidden'));
    document.documentElement.classList.remove('overflow-hidden');
  });
})();
</script>
