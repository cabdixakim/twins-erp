{{-- resources/views/purchases/edit.blade.php --}}

@php
  /** @var \App\Models\Purchase $purchase */
  $suppliers = $suppliers ?? collect();
  $products  = $products ?? collect();
  $depots    = $depots ?? collect();

  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $btnGhost = "inline-flex items-center gap-2 rounded-xl border $border $surface2 px-4 py-2 text-sm font-semibold $fg hover:bg-[color:var(--tw-surface)] transition";

  $errRing   = 'focus:ring-2 focus:ring-rose-500/35';
  $errBorder = 'border-rose-500/50';
  $errBg     = 'bg-rose-500/5';
  $errText   = 'text-rose-700 dark:text-rose-200';

  $fieldBase = "mt-1 w-full h-10 rounded-xl border $border $surface2 px-3 text-sm $fg
                placeholder:text-[color:var(--tw-muted)]
                focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

  $areaBase  = "mt-1 w-full rounded-xl border $border $surface2 p-3 text-sm $fg
                placeholder:text-[color:var(--tw-muted)]
                focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

  $typeLabel = match($purchase->type) {
    'import'      => 'Import',
    'local_depot' => 'Local depot',
    'cross_dock'  => 'Cross dock',
    default       => ucfirst(str_replace('_', ' ', (string) $purchase->type)),
  };

  $ref = $purchase->reference ?? ($purchase->display_ref ?? $purchase->id);
@endphp

@extends('layouts.app')

@section('title', 'Edit purchase')
@section('subtitle', 'Edit draft')

@section('content')

{{-- Header --}}
<div class="flex items-start justify-between gap-4">
  <div class="min-w-0">
    <div class="flex items-center gap-3">
      <h1 class="text-xl font-semibold {{ $fg }}">Edit purchase #{{ $ref }}</h1>
      <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }} px-2.5 py-1 text-xs font-semibold {{ $fg }}">
        Draft
      </span>
    </div>
    <p class="mt-1 text-sm {{ $muted }}">
      {{ $typeLabel }} · Update fields, then save.
    </p>
  </div>

  <a href="{{ route('purchases.show', $purchase) }}" class="{{ $btnGhost }}">
    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
    Back
  </a>
</div>

@if(session('error'))
  <div class="alert-err mt-4 rounded-xl p-3 text-sm font-medium">
    {{ session('error') }}
  </div>
@endif

<form method="POST" action="{{ route('purchases.update', $purchase) }}" class="mt-6">
  @csrf
  @method('PATCH')

  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">

    <div class="flex items-start justify-between gap-4 mb-5">
      <div>
        <div class="text-sm font-semibold {{ $fg }}">Purchase details</div>
        <div class="mt-0.5 text-xs {{ $muted }}">Type cannot be changed on an existing draft.</div>
      </div>

      {{-- Type badge (read-only) --}}
      <span class="inline-flex items-center rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-1.5 text-xs font-semibold {{ $muted }}">
        {{ $typeLabel }}
      </span>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">

      {{-- Reference --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Reference</label>
        <input name="reference" value="{{ old('reference', $purchase->reference) }}"
               class="{{ $fieldBase }} @error('reference') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="e.g. PO-2026-00001">
        @error('reference')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Product --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Product</label>
        <select name="product_id"
                class="{{ $fieldBase }} @error('product_id') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror">
          <option value="">Select…</option>
          @foreach($products as $p)
            <option value="{{ $p->id }}"
              {{ (string)old('product_id', $purchase->product_id) === (string)$p->id ? 'selected' : '' }}>
              {{ $p->name }}
            </option>
          @endforeach
        </select>
        @error('product_id')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Supplier --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Supplier (optional)</label>
        <select name="supplier_id"
                class="{{ $fieldBase }} @error('supplier_id') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror">
          <option value="">—</option>
          @foreach($suppliers as $s)
            <option value="{{ $s->id }}"
              {{ (string)old('supplier_id', $purchase->supplier_id) === (string)$s->id ? 'selected' : '' }}>
              {{ $s->name }}
            </option>
          @endforeach
        </select>
        @error('supplier_id')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Depot (local_depot only) --}}
      @if($purchase->type === 'local_depot')
        <div>
          <label class="text-xs font-semibold {{ $muted }}">Depot</label>
          <select name="depot_id"
                  class="{{ $fieldBase }} @error('depot_id') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror">
            <option value="">Select…</option>
            @foreach($depots as $d)
              <option value="{{ $d->id }}"
                {{ (string)old('depot_id', $purchase->depot_id) === (string)$d->id ? 'selected' : '' }}>
                {{ $d->name }}
              </option>
            @endforeach
          </select>
          @error('depot_id')
            <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
          @enderror
        </div>
      @endif

      {{-- Transporter & Freight (local depot only) --}}
      @if($purchase->type === 'local_depot')
        <div class="space-y-4">
          <div>
            <label class="text-xs font-semibold {{ $muted }}">Transporter <span class="{{ $hintText ?? 'text-slate-400' }} font-normal">(optional)</span></label>
            <select name="transporter_id" id="editTransporterSelect"
                    class="{{ $fieldBase }}">
              <option value="">No transporter / self-delivery</option>
              @foreach($transporters as $t)
                <option value="{{ $t->id }}"
                  {{ (string)old('transporter_id', $purchase->transporter_id) === (string)$t->id ? 'selected' : '' }}>
                  {{ $t->name }}
                </option>
              @endforeach
            </select>
            <div class="mt-1 text-xs {{ $hintText ?? 'text-slate-400' }}">Freight charge posts to their ledger when you receive the purchase.</div>
          </div>
          <div id="edit-freight-wrap"
               class="{{ old('transporter_id', $purchase->transporter_id) ? '' : 'hidden' }}">
            <label class="text-xs font-semibold {{ $muted }}">Freight amount <span class="{{ $hintText ?? 'text-slate-400' }} font-normal">(optional)</span></label>
            <div class="flex gap-2">
              <input name="freight_amount"
                     value="{{ old('freight_amount', $purchase->freight_amount) }}"
                     inputmode="decimal"
                     class="{{ $fieldBase }} flex-1" placeholder="e.g. 450.00">
              <input name="freight_currency"
                     value="{{ old('freight_currency', $purchase->freight_currency ?? 'USD') }}"
                     class="{{ $fieldBase }} w-24" placeholder="USD" maxlength="8">
            </div>
            <div class="mt-1 text-xs {{ $hintText ?? 'text-slate-400' }}">Total freight cost · Currency.</div>
            @error('freight_amount')
              <div class="mt-1 text-xs {{ $errText ?? 'text-red-400' }}">{{ $message }}</div>
            @enderror
          </div>
        </div>
      @endif

      {{-- Quantity --}}
      @php
        $uomLabel = ($volumeUnit ?? 'L') === 'M3' ? 'M³' : 'L';
        $uomLong  = ($volumeUnit ?? 'L') === 'M3' ? 'cubic metres (M³)' : 'litres (L)';
      @endphp
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Quantity <span class="font-normal opacity-60">({{ $uomLabel }})</span></label>
        <input name="qty" value="{{ old('qty', $purchase->qty) }}" inputmode="decimal"
               class="{{ $fieldBase }} @error('qty') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="e.g. 9 000 {{ $uomLabel }}">
        <div class="mt-1 text-xs {{ $hintText ?? '' }}">Volume in {{ $uomLong }}.</div>
        @error('qty')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Unit price --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Unit price <span class="font-normal opacity-60">(per {{ $uomLabel }})</span></label>
        <input name="unit_price" value="{{ old('unit_price', $purchase->unit_price) }}" inputmode="decimal"
               class="{{ $fieldBase }} @error('unit_price') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="e.g. 0.65 per {{ $uomLabel }}">
        @error('unit_price')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Currency --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Currency</label>
        <input name="currency" value="{{ old('currency', $purchase->currency ?? 'USD') }}"
               class="{{ $fieldBase }} @error('currency') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="USD">
        @error('currency')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Purchase date --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Purchase date (optional)</label>
        <input type="date" name="purchase_date"
               value="{{ old('purchase_date', $purchase->purchase_date?->format('Y-m-d')) }}"
               class="{{ $fieldBase }} @error('purchase_date') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror">
        @error('purchase_date')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Notes --}}
      <div class="sm:col-span-2">
        <label class="text-xs font-semibold {{ $muted }}">Notes (optional)</label>
        <textarea name="notes" rows="3"
                  class="{{ $areaBase }} @error('notes') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
                  placeholder="Any extra context…">{{ old('notes', $purchase->notes) }}</textarea>
        @error('notes')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

    </div>

    <div class="mt-6 flex items-center justify-between">
      <a href="{{ route('purchases.show', $purchase) }}" class="text-sm {{ $muted }} hover:text-[color:var(--tw-fg)]">
        Discard changes
      </a>

      <button type="submit"
              class="inline-flex items-center gap-2 rounded-xl border border-emerald-600 bg-emerald-600
                     px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 transition">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Save changes
      </button>
    </div>

  </div>
</form>

<script>
  document.getElementById('editTransporterSelect')?.addEventListener('change', function () {
    document.getElementById('edit-freight-wrap')?.classList.toggle('hidden', !this.value);
  });
</script>

@endsection
