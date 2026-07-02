@php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';
  $label    = 'block text-xs font-semibold ' . $fg . ' mb-1';
  $input    = 'w-full h-10 rounded-xl border ' . $border . ' ' . $surface . ' ' . $fg . ' text-sm px-3 focus:outline-none focus:ring-2 focus:ring-rose-500/40';
@endphp

@extends('layouts.app')

@section('title', 'Record Write-off')
@section('subtitle', 'Post a manual stock reduction — write-off, meter variance, or count correction')

@section('content')

<div class="max-w-xl mx-auto">
  <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">

    {{-- Header --}}
    <div class="px-6 py-5 border-b {{ $border }} {{ $surface2 }}">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3)">
          <svg class="w-5 h-5 text-rose-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <div>
          <div class="text-sm font-semibold {{ $fg }}">Manual stock write-off</div>
          <div class="text-xs {{ $muted }} mt-0.5">The loss value (qty × current unit cost) will be recorded for financial tracking.</div>
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('inventory-adjustments.store') }}" class="px-6 py-5 space-y-5" id="writeOffForm">
      @csrf

      @if($errors->any())
        <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 text-rose-400 text-sm px-4 py-3">
          @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
        </div>
      @endif

      {{-- Depot --}}
      <div>
        <label class="{{ $label }}">Depot</label>
        <select name="depot_id" id="depotSel" required
                class="{{ $input }}" onchange="loadStock()">
          <option value="">— Select depot —</option>
          @foreach($depots as $d)
            <option value="{{ $d->id }}" {{ (old('depot_id', $selectedDepotId) == $d->id) ? 'selected' : '' }}>
              {{ $d->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Product --}}
      <div>
        <label class="{{ $label }}">Product</label>
        <select name="product_id" id="productSel" required
                class="{{ $input }}" onchange="loadStock()">
          <option value="">— Select product —</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}" {{ (old('product_id', $selectedProductId) == $p->id) ? 'selected' : '' }}>
              {{ $p->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Stock info banner --}}
      <div id="stockBanner" class="{{ $stockInfo ? '' : 'hidden' }} rounded-xl border {{ $border }} {{ $surface2 }} px-4 py-3 text-sm">
        @if($stockInfo)
          <span class="{{ $muted }}">On hand: </span>
          <span class="{{ $fg }} font-semibold">{{ number_format((float)$stockInfo->total_qty, 3) }}</span>
          <span class="{{ $muted }} ml-3">Avg cost: </span>
          <span class="{{ $fg }} font-semibold">{{ number_format((float)$stockInfo->avg_cost, 4) }}</span>
        @endif
      </div>

      {{-- Reason type --}}
      <div>
        <label class="{{ $label }}">Reason</label>
        <select name="reason_type" required class="{{ $input }}">
          <option value="">— Select reason —</option>
          <option value="write_off"              {{ old('reason_type') === 'write_off' ? 'selected' : '' }}>Write-off (fire, theft, disaster)</option>
          <option value="meter_variance"         {{ old('reason_type') === 'meter_variance' ? 'selected' : '' }}>Meter variance</option>
          <option value="stock_count_correction" {{ old('reason_type') === 'stock_count_correction' ? 'selected' : '' }}>Stock count correction</option>
          <option value="transit_loss"           {{ old('reason_type') === 'transit_loss' ? 'selected' : '' }}>Transit loss (manual)</option>
        </select>
        <div class="text-[11px] {{ $muted }} mt-1">Depot shrinkage is auto-posted on receipt — do not record it manually.</div>
      </div>

      {{-- Recoverable --}}
      <div>
        <label class="{{ $label }}">Is this value recoverable?</label>
        <select name="recoverable" required class="{{ $input }}">
          <option value="0" {{ old('recoverable', '0') === '0' ? 'selected' : '' }}>No — non-recoverable (absorbed as a straight loss)</option>
          <option value="1" {{ old('recoverable') === '1' ? 'selected' : '' }}>Yes — recoverable (e.g. insurance claim, chargeable to a third party)</option>
        </select>
        <div class="text-[11px] {{ $muted }} mt-1">This classification drives the recoverable / non-recoverable split on the Inventory Position report.</div>
      </div>

      {{-- Qty --}}
      <div>
        <label class="{{ $label }}">Quantity to write off</label>
        <input type="number" name="qty" step="0.001" min="0.001" required
               value="{{ old('qty') }}" placeholder="0.000"
               class="{{ $input }}" oninput="updateEstimate()">
        <div class="text-[11px] {{ $muted }} mt-1" id="estValue"></div>
      </div>

      {{-- Notes --}}
      <div>
        <label class="{{ $label }}">Notes <span class="{{ $muted }}">(optional)</span></label>
        <textarea name="notes" rows="3" placeholder="Describe the loss event…"
                  class="w-full rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rose-500/40 resize-none">{{ old('notes') }}</textarea>
      </div>

      {{-- Warning --}}
      <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 text-amber-400 text-xs px-4 py-3">
        <strong>Note:</strong> This action cannot be undone without a manual offsetting entry. The loss value will be recorded as a financial expense entry.
      </div>

      {{-- Actions --}}
      <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('inventory-adjustments.index') }}"
           class="h-9 px-4 rounded-xl border {{ $border }} {{ $fg }} text-sm font-semibold hover:opacity-70 transition">
          Cancel
        </a>
        <button type="submit"
                class="h-9 px-5 rounded-xl border border-rose-600 bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 transition">
          Post write-off
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const avgCostByDepotProduct = {};

@if($stockInfo && $selectedDepotId && $selectedProductId)
avgCostByDepotProduct['{{ $selectedDepotId }}_{{ $selectedProductId }}'] = {
  qty: {{ (float)$stockInfo->total_qty }},
  cost: {{ (float)$stockInfo->avg_cost }}
};
@endif

function loadStock() {
  const depot   = document.getElementById('depotSel').value;
  const product = document.getElementById('productSel').value;
  if (!depot || !product) {
    document.getElementById('stockBanner').classList.add('hidden');
    document.getElementById('estValue').textContent = '';
    return;
  }
  const key = depot + '_' + product;
  if (avgCostByDepotProduct[key]) {
    showBanner(avgCostByDepotProduct[key]);
  } else {
    fetch(`/depot-stock/available?depot_id=${depot}&product_id=${product}`)
      .then(r => r.json())
      .then(d => {
        avgCostByDepotProduct[key] = { qty: d.qty || 0, cost: d.unit_cost || 0 };
        showBanner(avgCostByDepotProduct[key]);
      })
      .catch(() => document.getElementById('stockBanner').classList.add('hidden'));
  }
  updateEstimate();
}

function showBanner(info) {
  const banner = document.getElementById('stockBanner');
  banner.innerHTML = `<span class="tw-muted">On hand: </span><span class="tw-fg font-semibold">${info.qty.toLocaleString(undefined, {minimumFractionDigits:3,maximumFractionDigits:3})}</span>
    <span class="tw-muted ml-3">Avg cost: </span><span class="tw-fg font-semibold">${info.cost.toLocaleString(undefined, {minimumFractionDigits:4,maximumFractionDigits:4})}</span>`;
  banner.classList.remove('hidden');
  updateEstimate();
}

function updateEstimate() {
  const depot   = document.getElementById('depotSel').value;
  const product = document.getElementById('productSel').value;
  const qty     = parseFloat(document.querySelector('[name="qty"]').value) || 0;
  const key     = depot + '_' + product;
  const info    = avgCostByDepotProduct[key];
  const el      = document.getElementById('estValue');
  if (info && info.cost > 0 && qty > 0) {
    const val = qty * info.cost;
    el.textContent = `Estimated loss value: ${val.toLocaleString(undefined, {minimumFractionDigits:2,maximumFractionDigits:2})}`;
    el.className = 'text-[11px] text-rose-400 mt-1';
  } else {
    el.textContent = '';
  }
}
</script>
@endsection
