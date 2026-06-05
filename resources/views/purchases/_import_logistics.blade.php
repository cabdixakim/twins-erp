{{-- ==================================================================
     IMPORT LOGISTICS — truck nominations, loading, transit, delivery
     Variables expected: $purchase, $importNomination, $transporters,
     $depots, $qty, $currency, + theme vars ($fg $muted $border $surface $surface2)
     ================================================================== --}}
@php
  $nom  = $importNomination;   // shorthand
  $trucks = $nom ? $nom->trucks : collect();
  $unitLabel  = ($volumeUnit ?? 'L') === 'M3' ? 'M³' : 'L';
  $rateLabel  = ($volumeUnit ?? 'L') === 'M3' ? '/M³' : '/1000L';

  // Summary totals — loading_failed trucks excluded from nominated capacity
  $failedCount    = $trucks->where('status', 'loading_failed')->count();
  $totalCapacity  = $trucks->whereNotIn('status', ['loading_failed'])->sum('capacity');
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
        <button type="button" id="btnImportTrucks"
                class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }}
                       hover:bg-[color:var(--tw-surface-2)] transition inline-flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
          </svg>
          Import
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
        <div class="text-sm font-semibold {{ $fg }}">{{ $nom->currency }} {{ number_format($nom->rate_per_1000l, 2) }}<span class="text-xs font-normal {{ $muted }}"> {{ $rateLabel }}</span></div>
      </div>
      <div>
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Allowed loss</div>
        <div class="text-sm font-semibold {{ $fg }}">{{ number_format($nom->allowed_loss_pct, 2) }}%</div>
      </div>
      <div>
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Short charge</div>
        <div class="text-sm font-semibold {{ $fg }}">{{ $nom->short_charge_currency }} {{ number_format($nom->short_charge_rate, 2) }}<span class="text-xs font-normal {{ $muted }}"> {{ $rateLabel }}</span></div>
      </div>
      <div>
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Trucks</div>
        <div class="text-sm font-semibold {{ $fg }}">
          {{ $trucks->whereNotIn('status', ['loading_failed'])->count() }}
          @if($failedCount > 0)
            <span class="text-xs font-normal text-rose-400 ml-1">({{ $failedCount }} failed)</span>
          @endif
        </div>
      </div>
    </div>

    {{-- Summary metrics --}}
    <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 gap-3 border-b {{ $border }}">
      @php $overNominated = $totalCapacity > $qty; @endphp
      <div class="rounded-xl border {{ $overNominated ? 'border-amber-400/50' : $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Nominated capacity</div>
        <div class="mt-1 text-base font-bold {{ $overNominated ? 's-amber' : $fg }}">{{ number_format($totalCapacity, 0) }}</div>
        <div class="text-[10px] {{ $muted }}">
          {{ $unitLabel }}
          @if($overNominated)
            <span class="ml-1 text-amber-500 font-semibold">+{{ number_format($totalCapacity - $qty, 0) }} over PO qty</span>
          @endif
        </div>
      </div>
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Qty loaded</div>
        <div class="mt-1 text-base font-bold {{ $fg }}">{{ number_format($qtyLoaded, 0) }}</div>
        <div class="text-[10px] {{ $muted }}">{{ $unitLabel }}</div>
      </div>
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Qty delivered</div>
        <div class="mt-1 text-base font-bold s-green">{{ number_format($qtyDelivered, 0) }}</div>
        <div class="text-[10px] {{ $muted }}">{{ $unitLabel }}</div>
      </div>
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3">
        <div class="text-[10px] {{ $muted }} uppercase tracking-wide">Remaining at shipper</div>
        <div class="mt-1 text-base font-bold {{ $remainingAtShipper > 0 ? 's-amber' : $fg }}">
          {{ number_format($remainingAtShipper, 0) }}
        </div>
        <div class="text-[10px] {{ $muted }}">{{ $unitLabel }}</div>
      </div>
    </div>

    {{-- Truck table --}}
    @if($trucks->isNotEmpty())
    @php
      $justImportedIds = collect(
        array_filter(
          array_map('intval', explode(',', request()->query('imported', '')))
        )
      )->flip()->all();
    @endphp
      <div id="truck-table-section" class="overflow-x-auto">
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
              @php $isJustImported = isset($justImportedIds[$truck->id]); @endphp
              <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors {{ $isJustImported ? 'just-imported-row' : '' }}"
                  @if($isJustImported) data-just-imported="{{ $truck->id }}" style="background:rgba(16,185,129,.06)" @endif>
                <td class="py-2.5 pl-5 pr-3 {{ $fg }} font-mono font-semibold whitespace-nowrap">
                  {{ $truck->truck_reg ?: '—' }}
                  @if($truck->trailer_reg)
                    <span class="font-normal {{ $muted }}"> / {{ $truck->trailer_reg }}</span>
                  @endif
                  @if($isJustImported)
                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                          style="background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3)">
                      Just imported
                    </span>
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
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Transport rate <span class="{{ $muted }}">{{ $rateLabel }}</span></label>
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
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Short charge rate <span class="{{ $muted }}">{{ $rateLabel }} of excess loss</span></label>
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
            <input type="text" name="truck_reg" value="{{ old('truck_reg') }}" placeholder="e.g. KCA 123A" maxlength="40"
                   class="w-full h-10 rounded-xl border {{ $errors->has('truck_reg') ? 'border-rose-400' : $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            @error('truck_reg')
              <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
            @enderror
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Trailer registration</label>
            <input type="text" name="trailer_reg" value="{{ old('trailer_reg') }}" placeholder="e.g. TRLR-001" maxlength="40"
                   class="w-full h-10 rounded-xl border {{ $errors->has('trailer_reg') && !session('edit_error_truck_id') ? 'border-rose-400' : $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
            @if($errors->has('trailer_reg') && !session('edit_error_truck_id'))
              <p class="mt-1 text-xs text-rose-400">{{ $errors->first('trailer_reg') }}</p>
            @endif
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Capacity (litres) <span class="text-rose-400">*</span></label>
          <input type="number" name="capacity" value="{{ old('capacity') }}" step="0.001" min="1" required placeholder="e.g. 45000"
                 class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Driver name</label>
            <input type="text" name="driver_name" value="{{ old('driver_name') }}" maxlength="150"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Driver phone</label>
            <input type="text" name="driver_phone" value="{{ old('driver_phone') }}" maxlength="30"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Passport #</label>
            <input type="text" name="driver_passport" value="{{ old('driver_passport') }}" maxlength="60"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
          <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">License #</label>
            <input type="text" name="driver_license" value="{{ old('driver_license') }}" maxlength="60"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold {{ $fg }} mb-1">Notes</label>
          <input type="text" name="notes" value="{{ old('notes') }}" maxlength="1000"
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
              <input type="text" name="truck_reg" value="{{ session('edit_error_truck_id') == $truck->id ? old('truck_reg', $truck->truck_reg) : $truck->truck_reg }}" maxlength="40"
                     class="w-full h-10 rounded-xl border {{ session('edit_error_truck_id') == $truck->id && $errors->has('truck_reg') ? 'border-rose-400' : $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
              @if(session('edit_error_truck_id') == $truck->id && $errors->has('truck_reg'))
                <p class="mt-1 text-xs text-rose-400">{{ $errors->first('truck_reg') }}</p>
              @endif
            </div>
            <div>
              <label class="block text-xs font-semibold {{ $fg }} mb-1">Trailer registration</label>
              <input type="text" name="trailer_reg" value="{{ session('edit_error_truck_id') == $truck->id ? old('trailer_reg', $truck->trailer_reg) : $truck->trailer_reg }}" maxlength="40"
                     class="w-full h-10 rounded-xl border {{ session('edit_error_truck_id') == $truck->id && $errors->has('trailer_reg') ? 'border-rose-400' : $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40" />
              @if(session('edit_error_truck_id') == $truck->id && $errors->has('trailer_reg'))
                <p class="mt-1 text-xs text-rose-400">{{ $errors->first('trailer_reg') }}</p>
              @endif
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

{{-- ── Import trucks wizard modal ── --}}
@if($nom)
<div id="importTrucksModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
  <div class="w-full max-w-3xl rounded-2xl border {{ $border }} {{ $surface }} shadow-2xl flex flex-col"
       style="max-height:90vh;">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b {{ $border }} {{ $surface2 }} shrink-0">
      <div class="flex items-center gap-3">
        <span class="text-base font-semibold {{ $fg }}">Import trucks</span>
        <div class="flex items-center gap-1.5">
          <span id="wiStepDot1" class="h-5 w-5 rounded-full text-[10px] font-bold grid place-items-center bg-[color:var(--tw-accent)] text-white">1</span>
          <div class="w-5 h-px" style="background:var(--tw-border)"></div>
          <span id="wiStepDot2" class="h-5 w-5 rounded-full text-[10px] font-bold grid place-items-center border {{ $border }}" style="background:var(--tw-surface-2);color:var(--tw-muted)">2</span>
          <div class="w-5 h-px" style="background:var(--tw-border)"></div>
          <span id="wiStepDot3" class="h-5 w-5 rounded-full text-[10px] font-bold grid place-items-center border {{ $border }}" style="background:var(--tw-surface-2);color:var(--tw-muted)">3</span>
        </div>
      </div>
      <button type="button" id="btnCloseImportModal"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition" aria-label="Close">✕</button>
    </div>

    {{-- Step 1 — Upload --}}
    <div id="wiStep1" class="p-5 space-y-4 shrink-0">
      <div id="wiDropZone"
           class="rounded-2xl border-2 border-dashed p-10 text-center cursor-pointer transition-colors select-none"
           style="border-color:var(--tw-border)">
        <svg class="w-10 h-10 mx-auto mb-3" style="color:var(--tw-muted)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
        </svg>
        <p class="text-sm font-semibold {{ $fg }}">Drop your file here</p>
        <p class="text-xs mt-1" style="color:var(--tw-muted)">Excel (.xlsx / .xls) or CSV &nbsp;·&nbsp; <span style="color:var(--tw-accent)" class="font-semibold">browse files</span></p>
        <input type="file" id="wiFileInput" accept=".xlsx,.xls,.csv" class="hidden">
      </div>

      <div id="wiFileBar" class="hidden rounded-xl px-3 py-2.5 flex items-center gap-2 text-sm border" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
        <svg class="w-4 h-4 shrink-0" style="color:var(--tw-accent)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
        </svg>
        <span id="wiFileName" class="flex-1 truncate font-medium {{ $fg }}"></span>
        <span id="wiFileRowCount" class="text-xs shrink-0" style="color:var(--tw-muted)"></span>
        <button type="button" id="wiClearFile" class="shrink-0 text-xs hover:opacity-70 transition" style="color:var(--tw-muted)">✕</button>
      </div>

      {{-- Multi-section picker — shown only when file contains multiple product blocks --}}
      <div id="wiSectionPicker" class="hidden rounded-xl border p-3 space-y-2 text-xs" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
        <div class="flex items-center gap-1.5 font-semibold" style="color:var(--tw-fg)">
          <svg class="w-3.5 h-3.5 shrink-0" style="color:#f59e0b" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Multiple product sections found — pick the one to import:
        </div>
        <div id="wiSectionList" class="space-y-1"></div>
      </div>

      {{-- Column picker — shown when the file has separate capacity columns per product (e.g. "Petrol Capacity | Diesel Capacity") --}}
      <div id="wiColumnPicker" class="hidden rounded-xl border p-3 space-y-2 text-xs" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
        <div class="flex items-center gap-1.5 font-semibold" style="color:var(--tw-fg)">
          <svg class="w-3.5 h-3.5 shrink-0" style="color:#f59e0b" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
          </svg>
          Multiple capacity columns found — which one should we use?
        </div>
        <div id="wiColumnList" class="space-y-1"></div>
      </div>

      <div class="flex items-center justify-between">
        <a id="wiTemplateLink"
           href="{{ route('purchases.import-nomination.trucks.template', [$purchase, $nom]) }}"
           class="inline-flex items-center gap-1.5 text-xs font-semibold hover:underline"
           style="color:var(--tw-accent)">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
          </svg>
          Download template
        </a>
        <span class="text-xs" style="color:var(--tw-muted)">Required: Truck Reg · Driver Name · Capacity ({{ $unitLabel }})</span>
      </div>
    </div>

    {{-- Step 2 — Review --}}
    <div id="wiStep2" class="hidden flex flex-col overflow-hidden flex-1">
      <div id="wiSummaryBar" class="px-5 py-2.5 shrink-0 flex items-center justify-between text-xs font-semibold border-b" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
        <span id="wiReadyCount" style="color:var(--tw-fg)"></span>
        <div class="flex items-center gap-3">
          <span id="wiOverNomWarn" class="hidden items-center gap-1" style="color:#f59e0b">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span id="wiOverNomMsg"></span>
          </span>
          <span id="wiErrorCount" class="hidden" style="color:#f87171"></span>
        </div>
      </div>
      {{-- Unit conversion banner — shown when file unit looks different from system unit --}}
      <div id="wiConvertBanner" class="hidden px-5 py-2.5 shrink-0 flex items-center justify-between gap-3 text-xs border-b" style="background:rgba(251,191,36,.08);border-color:rgba(251,191,36,.3)">
        <div class="flex items-center gap-2 min-w-0">
          <svg class="w-3.5 h-3.5 shrink-0" style="color:#f59e0b" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span id="wiConvertMsg" class="truncate" style="color:var(--tw-fg)"></span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <button type="button" id="wiConvertBtn" class="h-7 px-3 rounded-lg text-[11px] font-semibold transition whitespace-nowrap"
                  style="background:rgba(245,158,11,.18);color:#d97706;border:1px solid rgba(245,158,11,.35)">Convert all</button>
          <button type="button" id="wiDismissConvert" class="h-7 px-2 rounded-lg text-[11px] transition opacity-60 hover:opacity-100 whitespace-nowrap"
                  style="color:var(--tw-muted)">Keep as-is</button>
        </div>
      </div>
      <div class="overflow-auto flex-1">
        <table class="w-full text-xs" style="min-width:680px">
          <thead>
            <tr class="border-b" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
              <th class="text-left py-2 pl-4 pr-1 font-semibold whitespace-nowrap" style="color:var(--tw-muted)">Truck Reg <span style="color:#f87171">*</span></th>
              <th class="text-left py-2 px-1 font-semibold whitespace-nowrap" style="color:var(--tw-muted)">Trailer Reg</th>
              <th class="text-left py-2 px-1 font-semibold whitespace-nowrap" style="color:var(--tw-muted)">Driver Name <span style="color:#f87171">*</span></th>
              <th class="text-left py-2 px-1 font-semibold whitespace-nowrap" style="color:var(--tw-muted)">Passport</th>
              <th class="text-left py-2 px-1 font-semibold whitespace-nowrap" style="color:var(--tw-muted)">License</th>
              <th class="text-left py-2 px-1 font-semibold whitespace-nowrap" style="color:var(--tw-muted)">Phone</th>
              <th class="text-right py-2 px-1 font-semibold whitespace-nowrap" style="color:var(--tw-muted)">Capacity ({{ $unitLabel }}) <span style="color:#f87171">*</span></th>
              <th class="py-2 pr-3 w-8"></th>
            </tr>
          </thead>
          <tbody id="wiReviewBody"></tbody>
        </table>
      </div>
    </div>

    {{-- Step 3 — Done --}}
    <div id="wiStep3" class="hidden p-10 text-center shrink-0">
      <div class="w-14 h-14 rounded-full border mx-auto mb-5 grid place-items-center" style="background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.3)">
        <svg class="w-7 h-7" style="color:#10b981" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
        </svg>
      </div>
      <p class="text-lg font-semibold mb-1 {{ $fg }}" id="wiSuccessMsg"></p>
      <p class="text-sm" style="color:var(--tw-muted)" id="wiSkippedMsg"></p>
      <div id="wiSkippedErrors" class="hidden mt-4 text-left w-full max-h-48 overflow-y-auto rounded-xl border" style="border-color:var(--tw-border);background:var(--tw-surface-2)">
        <ul id="wiSkippedErrorList" class="divide-y text-xs" style="divide-color:var(--tw-border)"></ul>
      </div>
      <div class="mt-5 flex flex-col items-center gap-3">
        <button type="button" id="wiDoneBtn"
                class="h-10 px-6 rounded-xl text-sm font-semibold text-white transition hover:opacity-90"
                style="background:var(--tw-accent)">
          Done
        </button>
        <p class="text-xs" id="wiCountdownMsg" style="color:var(--tw-muted)">Page will reload automatically…</p>
      </div>
    </div>

    {{-- Footer --}}
    <div class="px-5 py-4 border-t shrink-0 flex items-center justify-between gap-2" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
      <button type="button" id="wiBackBtn" class="hidden h-10 px-4 rounded-xl border text-sm font-semibold transition hover:opacity-80" style="background:var(--tw-surface);border-color:var(--tw-border);color:var(--tw-fg)">
        ← Back
      </button>
      <div class="flex items-center gap-2 ml-auto">
        <button type="button" id="wiCancelBtn" class="h-10 px-4 rounded-xl border text-sm font-semibold transition hover:opacity-80" style="background:var(--tw-surface);border-color:var(--tw-border);color:var(--tw-fg)">
          Cancel
        </button>
        <button type="button" id="wiNextBtn" disabled
                class="h-10 px-5 rounded-xl text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed"
                style="background:var(--tw-accent);border:1px solid rgba(var(--tw-accent-rgb),.4)">
          Review rows →
        </button>
      </div>
    </div>

  </div>
</div>
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

  // ── Post-import highlight + auto-scroll ──────────────────────────────────
  (function handleJustImported() {
    const params = new URLSearchParams(window.location.search);
    if (!params.has('imported')) return;

    const section = document.getElementById('truck-table-section');
    if (section) {
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Strip the ?imported= param from the URL without reloading
    const clean = window.location.pathname + window.location.hash.replace('#truck-table-section', '');
    history.replaceState(null, '', clean || window.location.pathname);
  })();

  // Setup / Edit nomination buttons
  const btnSetup = document.getElementById('btnSetupNomination');
  const btnEdit  = document.getElementById('btnEditNomination');
  if (btnSetup) btnSetup.addEventListener('click', () => openTruckModal('nominationModal'));
  if (btnEdit)  btnEdit.addEventListener('click',  () => openTruckModal('nominationModal'));

  // Add truck button
  const btnAdd = document.getElementById('btnAddTruck');
  if (btnAdd) btnAdd.addEventListener('click', () => openTruckModal('addTruckModal'));

  // Auto-open add-truck modal if server returned a truck_reg or trailer_reg validation error (add form only)
  @if (($errors->has('truck_reg') || $errors->has('trailer_reg')) && !session('edit_error_truck_id'))
  openTruckModal('addTruckModal');
  @endif

  // Auto-open the correct edit-truck modal on a trailer_reg / truck_reg validation error from updateTruck
  @if (session('edit_error_truck_id'))
  openTruckModal('editTruckModal-{{ session('edit_error_truck_id') }}');
  @endif

  // ESC closes all truck modals
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('[id^="loadModal-"], [id^="failLoadModal-"], [id^="borderModal-"], [id^="deliveryModal-"], [id^="editTruckModal-"], #addTruckModal, #nominationModal, #importTrucksModal')
      .forEach(el => el.classList.add('hidden'));
    document.documentElement.classList.remove('overflow-hidden');
  });
})();
</script>

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
<script>
(function () {
  'use strict';

  const IMPORT_URL = @json($nom ? route('purchases.import-nomination.trucks.import', [$purchase, $nom]) : '');
  const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content
                     || @json(csrf_token());

  // ── Elements ──────────────────────────────────────────────────────────────
  const modal     = document.getElementById('importTrucksModal');
  if (!modal) return; // no nomination yet, modal not rendered

  const dropZone  = document.getElementById('wiDropZone');
  const fileInput = document.getElementById('wiFileInput');
  const fileBar   = document.getElementById('wiFileBar');
  const fileNameEl= document.getElementById('wiFileName');
  const fileRowEl = document.getElementById('wiFileRowCount');
  const clearBtn  = document.getElementById('wiClearFile');
  const nextBtn   = document.getElementById('wiNextBtn');
  const backBtn   = document.getElementById('wiBackBtn');
  const cancelBtn = document.getElementById('wiCancelBtn');
  const closeBtn  = document.getElementById('btnCloseImportModal');
  const openBtn   = document.getElementById('btnImportTrucks');
  const reviewBody      = document.getElementById('wiReviewBody');
  const summaryBar      = document.getElementById('wiSummaryBar');
  const readyEl         = document.getElementById('wiReadyCount');
  const errorEl         = document.getElementById('wiErrorCount');
  const successEl       = document.getElementById('wiSuccessMsg');
  const skippedEl       = document.getElementById('wiSkippedMsg');
  const dots            = [1,2,3].map(n => document.getElementById('wiStepDot' + n));
  const steps           = [1,2,3].map(n => document.getElementById('wiStep' + n));
  const convertBanner   = document.getElementById('wiConvertBanner');
  const convertMsgEl    = document.getElementById('wiConvertMsg');
  const convertBtn      = document.getElementById('wiConvertBtn');
  const dismissConvertBtn = document.getElementById('wiDismissConvert');
  const overNomWarn     = document.getElementById('wiOverNomWarn');
  const overNomMsg      = document.getElementById('wiOverNomMsg');

  const VOLUME_UNIT   = @json($volumeUnit ?? 'L');
  const PO_QTY        = {{ (float) $qty }};
  const CURRENT_NOM   = {{ (float) $totalCapacity }};
  const PRODUCT_CODE  = @json(strtolower($purchase->product->code ?? ''));
  const PRODUCT_NAME  = @json(strtolower($purchase->product->name ?? ''));

  const sectionPicker = document.getElementById('wiSectionPicker');
  const sectionList   = document.getElementById('wiSectionList');
  const columnPicker  = document.getElementById('wiColumnPicker');
  const columnList    = document.getElementById('wiColumnList');

  let importRows     = [];
  let currentStep    = 1;
  let convertFactor  = null;
  let parsedSections = [];  // [{label, rows}] when file has multiple product sections
  let columnSets     = [];  // [{label, rows}] when file has multiple capacity columns

  // ── Open / close ──────────────────────────────────────────────────────────
  function openWizard() {
    resetWizard();
    modal.classList.remove('hidden');
    document.documentElement.classList.add('overflow-hidden');
  }
  function closeWizard() {
    modal.classList.add('hidden');
    document.documentElement.classList.remove('overflow-hidden');
  }

  if (openBtn)   openBtn.addEventListener('click', openWizard);
  if (closeBtn)  closeBtn.addEventListener('click', closeWizard);
  if (cancelBtn) cancelBtn.addEventListener('click', () => {
    if (currentStep === 3) { closeWizard(); window.location.reload(); }
    else closeWizard();
  });

  // ── Step navigation ───────────────────────────────────────────────────────
  function goToStep(n) {
    currentStep = n;

    steps.forEach((el, i) => {
      if (el) el.classList.toggle('hidden', i + 1 !== n);
    });

    dots.forEach((dot, i) => {
      if (!dot) return;
      const num = i + 1;
      if (num < n) {
        dot.style.cssText = 'background:var(--tw-accent);color:#fff;border:none';
        dot.textContent = '✓';
      } else if (num === n) {
        dot.style.cssText = 'background:var(--tw-accent);color:#fff;border:none';
        dot.textContent = num;
      } else {
        dot.style.cssText = 'background:var(--tw-surface-2);color:var(--tw-muted);border:1px solid var(--tw-border)';
        dot.textContent = num;
      }
    });

    if (backBtn)   backBtn.classList.toggle('hidden', n !== 2);
    if (cancelBtn) cancelBtn.textContent = n === 3 ? 'Close' : 'Cancel';

    if (nextBtn) {
      if (n === 3) {
        nextBtn.classList.add('hidden');
      } else {
        nextBtn.classList.remove('hidden');
        if (n === 1) {
          nextBtn.textContent = 'Review rows →';
          nextBtn.disabled = importRows.length === 0;
        } else if (n === 2) {
          updateImportButton();
        }
      }
    }
  }

  // ── Reset ─────────────────────────────────────────────────────────────────
  function resetWizard() {
    importRows     = [];
    parsedSections = [];
    columnSets     = [];
    if (fileBar)        fileBar.classList.add('hidden');
    if (fileInput)      fileInput.value = '';
    if (dropZone)       dropZone.style.background = '';
    if (nextBtn)        { nextBtn.classList.remove('hidden'); nextBtn.disabled = true; nextBtn.textContent = 'Review rows →'; }
    if (cancelBtn)      cancelBtn.textContent = 'Cancel';
    if (reviewBody)     reviewBody.innerHTML = '';
    if (sectionPicker)  sectionPicker.classList.add('hidden');
    if (sectionList)    sectionList.innerHTML = '';
    if (columnPicker)   columnPicker.classList.add('hidden');
    if (columnList)     columnList.innerHTML = '';
    hideConvertBanner();
    goToStep(1);
  }

  // ── Unit conversion detection ──────────────────────────────────────────────
  function detectUnitMismatch() {
    hideConvertBanner();
    if (!convertBanner || importRows.length === 0) return;
    const nums = importRows.map(r => parseFloat(r.capacity)).filter(n => !isNaN(n) && n > 0);
    if (nums.length === 0) return;
    const sorted = [...nums].sort((a, b) => a - b);
    const median = sorted[Math.floor(sorted.length / 2)];
    if (VOLUME_UNIT === 'L' && median < 200) {
      convertFactor = 1000;
      convertMsgEl.textContent =
        `These capacities look like M³ (e.g. ${median.toLocaleString()} M³). Your system uses Litres — multiply by 1,000?`;
      convertBanner.classList.remove('hidden');
    } else if (VOLUME_UNIT === 'M3' && median > 1000) {
      convertFactor = 0.001;
      convertMsgEl.textContent =
        `These capacities look like Litres (e.g. ${median.toLocaleString()} L). Your system uses M³ — divide by 1,000?`;
      convertBanner.classList.remove('hidden');
    }
  }
  function hideConvertBanner() {
    convertFactor = null;
    if (convertBanner) convertBanner.classList.add('hidden');
  }
  function applyConversion() {
    if (!convertFactor) return;
    const factor = convertFactor;
    importRows.forEach(row => {
      const n = parseFloat(row.capacity);
      if (!isNaN(n) && n > 0) {
        row.capacity = (factor >= 1)
          ? String(Math.round(n * factor))
          : String(parseFloat((n * factor).toFixed(3)));
      }
    });
    hideConvertBanner();
    renderReviewTable();
    updateSummaryBar();
    updateImportButton();
  }
  if (convertBtn)        convertBtn.addEventListener('click', applyConversion);
  if (dismissConvertBtn) dismissConvertBtn.addEventListener('click', hideConvertBanner);

  // ── Drag & drop / file input ──────────────────────────────────────────────
  if (dropZone) {
    dropZone.addEventListener('click', () => fileInput && fileInput.click());
    dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropZone.style.borderColor = 'var(--tw-accent)';
      dropZone.style.background  = 'rgba(var(--tw-accent-rgb),.06)';
    });
    dropZone.addEventListener('dragleave', () => {
      dropZone.style.borderColor = 'var(--tw-border)';
      dropZone.style.background  = '';
    });
    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.style.borderColor = 'var(--tw-border)';
      dropZone.style.background  = '';
      const f = e.dataTransfer.files[0];
      if (f) parseFile(f);
    });
  }
  if (fileInput) fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) parseFile(fileInput.files[0]);
  });
  if (clearBtn) clearBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    importRows = [];
    if (fileBar) fileBar.classList.add('hidden');
    if (fileInput) fileInput.value = '';
    if (nextBtn) nextBtn.disabled = true;
  });

  // ── Parse file via SheetJS ────────────────────────────────────────────────
  const HEADER_KEYWORDS = ['truck', 'driver', 'capacity', 'trailer', 'passport', 'licence', 'license', 'phone'];

  function isHeaderRow(row) {
    const joined = row.join(' ').toLowerCase();
    return HEADER_KEYWORDS.filter(k => joined.includes(k)).length >= 2;
  }

  // Look above a header row for a short non-empty label (product name / section title)
  function getSectionLabel(raw, headerIdx) {
    for (let i = headerIdx - 1; i >= Math.max(0, headerIdx - 6); i--) {
      const text = raw[i].map(c => String(c).trim()).filter(Boolean).join(' ').trim();
      if (text.length > 0 && text.length < 120) return text;
    }
    return 'Section ' + (headerIdx + 1);
  }

  // Does this label likely match the purchase product?
  function labelMatchesProduct(label) {
    const lbl = label.toLowerCase();
    if (PRODUCT_CODE && lbl.includes(PRODUCT_CODE)) return true;
    if (PRODUCT_NAME) {
      const firstWord = PRODUCT_NAME.split(/\s+/)[0];
      if (firstWord.length > 2 && lbl.includes(firstWord)) return true;
    }
    // Common fuel synonyms
    const synonyms = { ago: ['diesel','ago','gasoil'], pms: ['petrol','pms','gasoline','mogas'] };
    for (const [code, words] of Object.entries(synonyms)) {
      if (PRODUCT_CODE === code && words.some(w => lbl.includes(w))) return true;
    }
    return false;
  }

  function extractRows(raw, headerIdx, endIdx, cmap) {
    return raw.slice(headerIdx + 1, endIdx)
      .filter(r => {
        if (!r.some(c => String(c).trim() !== '')) return false;
        if (cmap.truck_reg >= 0 && String(r[cmap.truck_reg] ?? '').trim() === '') return false;
        return true;
      })
      .map(r => ({
        truck_reg:       String(r[cmap.truck_reg]       ?? '').trim(),
        trailer_reg:     String(r[cmap.trailer_reg]     ?? '').trim(),
        driver_name:     String(r[cmap.driver_name]     ?? '').trim(),
        driver_passport: String(r[cmap.driver_passport] ?? '').trim(),
        driver_license:  String(r[cmap.driver_license]  ?? '').trim(),
        driver_phone:    String(r[cmap.driver_phone]    ?? '').trim(),
        // Strip spaces/commas used as thousands separators (e.g. "42 000" → "42000")
        capacity:        String(r[cmap.capacity]        ?? '').trim().replace(/[\s\u00a0,]/g, ''),
      }));
  }

  function applySection(section) {
    importRows = section.rows;
    if (fileRowEl) fileRowEl.textContent = importRows.length + ' row' + (importRows.length !== 1 ? 's' : '') + ' ready';
    if (nextBtn)   nextBtn.disabled = importRows.length === 0;
  }

  // ── Multi-capacity-column support ─────────────────────────────────────────
  // Returns all column indices whose header looks like a capacity/volume field.
  function findCapacityColumns(hdr) {
    const capKeywords = ['capacity', 'cap (l)', 'cap (m', 'volume', 'litres', 'liters'];
    const results = [];
    hdr.forEach((h, i) => {
      if (capKeywords.some(k => h.includes(k))) results.push({ label: h, colIdx: i });
    });
    return results;
  }

  // Returns true if a capacity column label (e.g. "diesel capacity") matches
  // the current purchase product via code, name, or fuel-type synonym.
  function matchCapacityToProduct(label) {
    const lbl = label.toLowerCase();
    if (PRODUCT_CODE && lbl.includes(PRODUCT_CODE)) return true;
    if (PRODUCT_NAME) {
      const fw = PRODUCT_NAME.split(/\s+/)[0];
      if (fw.length > 2 && lbl.includes(fw)) return true;
    }
    const synonyms = {
      ago: ['diesel', 'gasoil', 'gas oil', 'ago'],
      pms: ['petrol', 'gasoline', 'mogas', 'motor spirit', 'pms'],
      jet: ['jet', 'avjet', 'kerosene', 'kero'],
    };
    for (const [code, words] of Object.entries(synonyms)) {
      const codeMatch = PRODUCT_CODE === code || PRODUCT_NAME.startsWith(code);
      if (codeMatch && words.some(w => lbl.includes(w))) return true;
    }
    return false;
  }

  function applyColumnSet(idx) {
    if (!columnSets[idx]) return;
    importRows = columnSets[idx].rows;
    if (fileRowEl) fileRowEl.textContent = importRows.length + ' row' + (importRows.length !== 1 ? 's' : '') + ' ready';
    if (nextBtn)   nextBtn.disabled = importRows.length === 0;
  }

  function renderColumnPicker(sets, autoMatchIdx) {
    if (!columnList) return;
    columnList.innerHTML = '';
    sets.forEach((cs, idx) => {
      const matched = idx === autoMatchIdx;
      const lbl = document.createElement('label');
      lbl.style.cssText = `display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:10px;cursor:pointer;border:1px solid ${matched ? 'rgba(16,185,129,.4)' : 'var(--tw-border)'};background:${matched ? 'rgba(16,185,129,.07)' : 'var(--tw-surface)'};transition:background .15s`;
      lbl.innerHTML = `
        <input type="radio" name="wiColumn" value="${idx}" ${matched ? 'checked' : ''} style="accent-color:var(--tw-accent);margin:0">
        <span style="flex:1;min-width:0">
          <span style="font-weight:600;color:var(--tw-fg)">${escHtml(cs.label)}</span>
          ${matched ? '<span style="font-size:10px;color:#10b981;margin-left:6px">✓ matches purchase product</span>' : ''}
        </span>`;
      const radio = lbl.querySelector('input');
      radio.addEventListener('change', () => {
        columnList.querySelectorAll('label').forEach((l, i) => {
          l.style.borderColor = i === idx ? 'rgba(16,185,129,.4)' : 'var(--tw-border)';
          l.style.background  = i === idx ? 'rgba(16,185,129,.07)' : 'var(--tw-surface)';
        });
        applyColumnSet(idx);
      });
      columnList.appendChild(lbl);
    });
    if (columnPicker) columnPicker.classList.remove('hidden');
    applyColumnSet(autoMatchIdx ?? 0);
  }

  function renderSectionPicker(sections, autoMatchIdx) {
    if (!sectionList) return;
    sectionList.innerHTML = '';
    sections.forEach((sec, idx) => {
      const matched = idx === autoMatchIdx;
      const btn = document.createElement('label');
      btn.style.cssText = `display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:10px;cursor:pointer;border:1px solid ${matched ? 'rgba(16,185,129,.4)' : 'var(--tw-border)'};background:${matched ? 'rgba(16,185,129,.07)' : 'var(--tw-surface)'};transition:background .15s`;
      btn.innerHTML = `
        <input type="radio" name="wiSection" value="${idx}" ${matched ? 'checked' : ''} style="accent-color:var(--tw-accent);margin:0">
        <span style="flex:1;min-width:0">
          <span style="font-weight:600;color:var(--tw-fg)">${escHtml(sec.label)}</span>
          ${matched ? '<span style="font-size:10px;color:#10b981;margin-left:6px">✓ matches purchase product</span>' : ''}
          <span style="font-size:10px;color:var(--tw-muted);margin-left:6px">${sec.rows.length} truck${sec.rows.length !== 1 ? 's' : ''}</span>
        </span>`;
      const radio = btn.querySelector('input');
      radio.addEventListener('change', () => {
        // Update border highlights
        sectionList.querySelectorAll('label').forEach((l, i) => {
          l.style.borderColor = i === idx ? 'rgba(16,185,129,.4)' : 'var(--tw-border)';
          l.style.background  = i === idx ? 'rgba(16,185,129,.07)' : 'var(--tw-surface)';
        });
        applySection(sections[idx]);
      });
      sectionList.appendChild(btn);
    });
    if (sectionPicker) sectionPicker.classList.remove('hidden');
    // Apply the auto-matched or first section immediately
    applySection(sections[autoMatchIdx ?? 0]);
  }

  function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function parseFile(file) {
    const reader = new FileReader();
    reader.onload = (ev) => {
      try {
        const wb  = XLSX.read(new Uint8Array(ev.target.result), { type: 'array' });
        const ws  = wb.Sheets[wb.SheetNames[0]];
        const raw = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });

        if (raw.length < 2) {
          showFileError(file.name, 'File appears to be empty.');
          return;
        }

        // Find ALL header rows in the file
        const headerIdxs = [];
        for (let i = 0; i < raw.length - 1; i++) {
          if (isHeaderRow(raw[i])) headerIdxs.push(i);
        }
        if (headerIdxs.length === 0) {
          showFileError(file.name, 'Could not find a truck header row (need "Truck", "Driver", "Capacity" etc.).');
          return;
        }

        // Build sections: each header to the next (or end of file)
        parsedSections = headerIdxs.map((hIdx, si) => {
          const hdr  = raw[hIdx].map(h => String(h).toLowerCase().trim());
          const cmap = mapColumns(hdr);
          const end  = headerIdxs[si + 1] ?? raw.length;
          return {
            label: getSectionLabel(raw, hIdx),
            rows:  extractRows(raw, hIdx, end, cmap),
          };
        });

        if (fileNameEl) fileNameEl.textContent = file.name;
        if (fileBar)    fileBar.classList.remove('hidden');

        if (parsedSections.length === 1) {
          // Single section — check for multiple capacity columns
          if (sectionPicker) sectionPicker.classList.add('hidden');
          const hIdx0   = headerIdxs[0];
          const hdr0    = raw[hIdx0].map(h => String(h).toLowerCase().trim());
          const allCaps = findCapacityColumns(hdr0);

          if (allCaps.length > 1) {
            // Build one row-set per capacity column and show the picker
            const baseCmap = mapColumns(hdr0);
            columnSets = allCaps.map(({ label, colIdx }) => ({
              label,
              rows: extractRows(raw, hIdx0, raw.length, { ...baseCmap, capacity: colIdx }),
            }));
            let autoCapIdx = columnSets.findIndex(cs => matchCapacityToProduct(cs.label));
            if (autoCapIdx < 0) autoCapIdx = 0;
            if (fileRowEl) fileRowEl.textContent = columnSets[autoCapIdx].rows.length + ' row' + (columnSets[autoCapIdx].rows.length !== 1 ? 's' : '') + ' ready';
            renderColumnPicker(columnSets, autoCapIdx);
          } else {
            // Single capacity column — load directly, hide column picker
            if (columnPicker) columnPicker.classList.add('hidden');
            applySection(parsedSections[0]);
          }
        } else {
          // Multiple sections — find best match and show picker
          let autoMatchIdx = null;
          parsedSections.forEach((sec, i) => {
            if (autoMatchIdx === null && labelMatchesProduct(sec.label)) autoMatchIdx = i;
          });
          if (fileRowEl) fileRowEl.textContent = parsedSections.length + ' sections found';
          renderSectionPicker(parsedSections, autoMatchIdx);
        }
      } catch (err) {
        showFileError(file.name, 'Could not parse: ' + err.message);
      }
    };
    reader.readAsArrayBuffer(file);
  }

  function mapColumns(hdr) {
    // Each entry: preferred matches listed most-specific first so a precise
    // match wins over a broad one.
    const find = (...names) => {
      for (const n of names) {
        const i = hdr.findIndex(h => h.includes(n));
        if (i >= 0) return i;
      }
      return -1;
    };
    return {
      truck_reg:       find('truck no', 'truck reg', 'truck_reg', 'truck'),
      trailer_reg:     find('trailer no', 'trailer reg', 'trailer_reg', 'trailer'),
      driver_name:     find('driver name', 'driver_name', 'driver'),
      driver_passport: find('passport'),
      driver_license:  find('driving licence', 'driving license', 'licence no', 'license no', 'licence', 'license'),
      driver_phone:    find('driver phone', 'phone', 'mobile', 'tel', 'contact no', 'contact'),
      capacity:        find('capacity', 'cap (l)', 'cap'),
    };
  }

  function showFileError(name, msg) {
    if (fileNameEl) fileNameEl.textContent = '⚠ ' + msg;
    if (fileRowEl)  fileRowEl.textContent  = '';
    if (fileBar)    fileBar.classList.remove('hidden');
    if (nextBtn)    nextBtn.disabled = true;
    importRows = [];
  }

  // ── Review table ──────────────────────────────────────────────────────────
  const FIELDS   = ['truck_reg','trailer_reg','driver_name','driver_passport','driver_license','driver_phone','capacity'];
  const REQUIRED = new Set(['truck_reg','driver_name','capacity']);
  const COL_WIDTHS = { truck_reg:90, trailer_reg:80, driver_name:120, driver_passport:85, driver_license:80, driver_phone:95, capacity:80 };

  function renderReviewTable() {
    if (!reviewBody) return;
    reviewBody.innerHTML = '';
    importRows.forEach((row, i) => buildRow(row, i));
    revalidateDuplicateRegs();
  }

  function buildRow(row, i) {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid var(--tw-border)';

    FIELDS.forEach(field => {
      const td  = document.createElement('td');
      td.className = 'py-1 px-1';

      const inp = document.createElement('input');
      inp.type  = field === 'capacity' ? 'number' : 'text';
      inp.value = row[field];
      inp.dataset.field = field;
      inp.style.cssText = [
        'height:28px',
        'padding:0 8px',
        'border-radius:8px',
        'border:1px solid var(--tw-border)',
        'background:var(--tw-surface-2)',
        'color:var(--tw-fg)',
        'font-size:11px',
        'outline:none',
        'width:100%',
        'min-width:' + (COL_WIDTHS[field] || 80) + 'px',
        'box-sizing:border-box',
      ].join(';');
      if (field === 'capacity') { inp.step = '0.001'; inp.min = '0'; }

      validateCell(inp, field);

      inp.addEventListener('input', () => {
        importRows[i][field] = inp.value;
        validateCell(inp, field);
        if (field === 'truck_reg' || field === 'trailer_reg') revalidateDuplicateRegs();
        updateSummaryBar();
        updateImportButton();
      });
      inp.addEventListener('focus', () => {
        inp.style.boxShadow = '0 0 0 2px rgba(var(--tw-accent-rgb),.35)';
      });
      inp.addEventListener('blur', () => {
        inp.style.boxShadow = '';
      });

      td.appendChild(inp);
      tr.appendChild(td);
    });

    // Delete row
    const tdDel = document.createElement('td');
    tdDel.className = 'py-1 pr-3 text-right';
    tdDel.style.width = '32px';
    const del = document.createElement('button');
    del.type = 'button';
    del.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
    del.style.cssText = 'width:24px;height:24px;border-radius:6px;border:1px solid var(--tw-border);background:var(--tw-surface);color:var(--tw-muted);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;';
    del.addEventListener('click', () => {
      importRows.splice(i, 1);
      renderReviewTable();
      updateSummaryBar();
      updateImportButton();
    });
    tdDel.appendChild(del);
    tr.appendChild(tdDel);

    reviewBody.appendChild(tr);
  }

  function validateCell(inp, field) {
    const val  = inp.value.trim();
    const bad  = REQUIRED.has(field) && (field === 'capacity' ? (val === '' || parseFloat(val) <= 0) : val === '');
    inp.style.borderColor = bad ? '#f87171' : 'var(--tw-border)';
    inp.style.background  = bad ? 'rgba(239,68,68,.09)' : 'var(--tw-surface-2)';
  }

  function revalidateDuplicateRegs() {
    if (!reviewBody) return;

    // Helper: highlight a single field group for duplicates
    function highlightDups(fieldName, dupMsg) {
      const inputs = Array.from(reviewBody.querySelectorAll('[data-field="' + fieldName + '"]'));
      const counts = {};
      inputs.forEach(inp => {
        const key = inp.value.trim().toLowerCase();
        if (key) counts[key] = (counts[key] || 0) + 1;
      });
      inputs.forEach(inp => {
        const key = inp.value.trim().toLowerCase();
        const isDup = key && counts[key] > 1;
        if (isDup) {
          inp.style.borderColor = '#f59e0b';
          inp.style.background  = 'rgba(245,158,11,.09)';
          inp.title = dupMsg;
        } else if (inp.value.trim() !== '') {
          // restore valid state (validateCell handles empty)
          inp.style.borderColor = 'var(--tw-border)';
          inp.style.background  = 'var(--tw-surface-2)';
          inp.title = '';
        }
      });
    }

    highlightDups('truck_reg',   'Duplicate truck reg — this row will be skipped');
    highlightDups('trailer_reg', 'Duplicate trailer reg — this row will be skipped');
  }

  function hasDuplicateRegs() {
    if (!reviewBody) return false;
    for (const fieldName of ['truck_reg', 'trailer_reg']) {
      const inputs = Array.from(reviewBody.querySelectorAll('[data-field="' + fieldName + '"]'));
      const counts = {};
      inputs.forEach(inp => {
        const key = inp.value.trim().toLowerCase();
        if (key) counts[key] = (counts[key] || 0) + 1;
      });
      if (inputs.some(inp => { const k = inp.value.trim().toLowerCase(); return k && counts[k] > 1; })) return true;
    }
    return false;
  }

  function countReady() {
    return importRows.filter(r =>
      r.truck_reg.trim() && r.driver_name.trim() && parseFloat(r.capacity) > 0
    ).length;
  }

  function updateSummaryBar() {
    const ready  = countReady();
    const errors = importRows.length - ready;
    if (readyEl)  readyEl.textContent = ready + ' row' + (ready !== 1 ? 's' : '') + ' ready';
    if (errorEl) {
      if (errors > 0) {
        errorEl.textContent = '· ' + errors + ' with missing required fields';
        errorEl.classList.remove('hidden');
      } else {
        errorEl.classList.add('hidden');
      }
    }
    // Over-nomination warning
    if (overNomWarn && overNomMsg) {
      const newCap = importRows.reduce((sum, r) => {
        const n = parseFloat(r.capacity); return sum + (isNaN(n) ? 0 : n);
      }, 0);
      const projectedTotal = CURRENT_NOM + newCap;
      if (projectedTotal > PO_QTY) {
        const over = Math.round(projectedTotal - PO_QTY).toLocaleString();
        overNomMsg.textContent = 'Adds ' + over + ' ' + VOLUME_UNIT + ' over PO qty — OK if supplier will top up';
        overNomWarn.classList.remove('hidden');
        overNomWarn.style.display = 'inline-flex';
      } else {
        overNomWarn.classList.add('hidden');
        overNomWarn.style.display = '';
      }
    }
  }

  function updateImportButton() {
    if (!nextBtn || currentStep !== 2) return;
    const n = countReady();
    nextBtn.textContent = 'Import ' + n + ' truck' + (n !== 1 ? 's' : '') + ' →';
    nextBtn.disabled    = n === 0 || hasDuplicateRegs();
  }

  // ── Next / Back ───────────────────────────────────────────────────────────
  if (nextBtn) nextBtn.addEventListener('click', async () => {
    if (currentStep === 1) {
      renderReviewTable();
      goToStep(2);
      detectUnitMismatch();
      updateSummaryBar();
      updateImportButton();
    } else if (currentStep === 2) {
      await submitImport();
    }
  });
  if (backBtn) backBtn.addEventListener('click', () => {
    if (currentStep === 2) goToStep(1);
  });

  // ── Submit ────────────────────────────────────────────────────────────────
  async function submitImport() {
    nextBtn.disabled   = true;
    nextBtn.textContent = 'Importing…';

    // Send every row to the server — it validates and classifies each one,
    // returning the true committed/skipped split.
    const allRows = importRows.map(r => ({
      truck_reg:       r.truck_reg.trim(),
      trailer_reg:     r.trailer_reg.trim(),
      driver_name:     r.driver_name.trim(),
      driver_passport: r.driver_passport.trim(),
      driver_license:  r.driver_license.trim(),
      driver_phone:    r.driver_phone.trim(),
      capacity:        r.capacity.trim() === '' ? null : parseFloat(r.capacity),
    }));

    try {
      const resp   = await fetch(IMPORT_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept':       'application/json',
          'X-CSRF-TOKEN': CSRF,
        },
        body: JSON.stringify({ rows: allRows }),
      });
      const result = await resp.json();
      if (!resp.ok) throw new Error(result.error || 'Server error ' + resp.status);

      if (successEl) successEl.textContent = result.committed + ' truck' + (result.committed !== 1 ? 's' : '') + ' imported successfully';
      if (skippedEl) skippedEl.textContent = result.skipped > 0
        ? result.skipped + ' row' + (result.skipped !== 1 ? 's' : '') + ' skipped'
        : '';

      const errorsWrap = document.getElementById('wiSkippedErrors');
      const errorsList = document.getElementById('wiSkippedErrorList');
      if (errorsWrap && errorsList && result.errors && result.errors.length > 0) {
        errorsList.innerHTML = '';
        result.errors.forEach(e => {
          const li = document.createElement('li');
          li.className = 'px-3 py-2';

          const rowLabel = document.createElement('span');
          rowLabel.className = 'font-semibold';
          rowLabel.style.color = 'var(--tw-fg)';
          rowLabel.textContent = 'Row ' + (e.row ?? '?') + ': ';

          const reasons = Array.isArray(e.messages) ? e.messages.join('; ') : String(e.messages ?? '');
          const reasonSpan = document.createElement('span');
          reasonSpan.style.color = 'var(--tw-muted)';
          reasonSpan.textContent = reasons;

          li.appendChild(rowLabel);
          li.appendChild(reasonSpan);
          errorsList.appendChild(li);
        });
        errorsWrap.classList.remove('hidden');
      } else if (errorsWrap) {
        errorsWrap.classList.add('hidden');
      }

      goToStep(3);
      const importedParam = (result.importedIds && result.importedIds.length > 0)
        ? '?imported=' + result.importedIds.join(',')
        : '';
      const reloadUrl = window.location.pathname + importedParam + '#truck-table-section';
      const doneBtn = document.getElementById('wiDoneBtn');
      const countdownEl = document.getElementById('wiCountdownMsg');
      let reloadTimer;
      const reloadDelay = (result.errors && result.errors.length > 0) ? 6000 : 1800;
      let secondsLeft = Math.round(reloadDelay / 1000);
      if (countdownEl) countdownEl.textContent = 'Closing in ' + secondsLeft + ' s\u2026';
      const countdownInterval = setInterval(() => {
        secondsLeft--;
        if (secondsLeft <= 0) {
          clearInterval(countdownInterval);
        } else if (countdownEl) {
          countdownEl.textContent = 'Closing in ' + secondsLeft + ' s\u2026';
        }
      }, 1000);
      if (doneBtn) {
        doneBtn.addEventListener('click', () => { clearTimeout(reloadTimer); clearInterval(countdownInterval); closeWizard(); window.location.href = reloadUrl; });
      }
      reloadTimer = setTimeout(() => { clearInterval(countdownInterval); closeWizard(); window.location.href = reloadUrl; }, reloadDelay);
    } catch (err) {
      nextBtn.disabled = false;
      updateImportButton();
      alert('Import failed: ' + err.message);
    }
  }

})();
</script>
@endpush
