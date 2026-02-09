{{-- resources/views/purchases/create.blade.php --}}

@php
  $suppliers = $suppliers ?? collect();
  $products  = $products ?? collect();
  $depots    = $depots ?? collect();

  // Theme tokens (from app.css)
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  // Buttons (stand out in BOTH light + dark)
  $btnGhost = "inline-flex items-center gap-2 rounded-xl border $border $surface2 px-4 py-2 text-sm font-semibold $fg hover:bg-[color:var(--tw-surface)]";
  $btnLink  = "text-sm $muted hover:text-[color:var(--tw-fg)]";

  // Strong primary button (bright in dark, crisp in light)
  // Uses your accent token + emerald text (like you’ve been doing successfully).
  $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold
                 px-4 py-2 text-sm font-semibold hover:bg-emerald-500/20";
@endphp

@extends('layouts.app')

@section('title', 'New purchase')
@section('subtitle', 'Create a draft purchase')

@section('content')

{{-- Header --}}
<div class="flex items-start justify-between gap-4">
  <div class="min-w-0">
    <h1 class="text-xl font-semibold {{ $fg }}">New purchase</h1>
    <p class="mt-1 text-sm {{ $muted }}">
      Create a draft purchase now. Confirm later to create the batch.
    </p>
  </div>

  <a href="{{ route('purchases.index') }}" class="{{ $btnGhost }}">
    <span class="text-base">←</span>
    Back
  </a>
</div>



<form method="POST" action="{{ route('purchases.store') }}" class="mt-6">
  @csrf

  @php
    // Validation UI tokens
    $errRing   = 'focus:ring-2 focus:ring-rose-500/35';
    $errBorder = 'border-rose-500/50';
    $errBg     = 'bg-rose-500/5';
    $errText   = 'text-rose-700 dark:text-rose-200';
    $hintText  = $muted;

    // Helpers (field classes)
    $fieldBase = "mt-1 w-full h-10 rounded-xl border $border $surface2 px-3 text-sm $fg
                  placeholder:text-[color:var(--tw-muted)]
                  focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

    $areaBase  = "mt-1 w-full rounded-xl border $border $surface2 p-3 text-sm $fg
                  placeholder:text-[color:var(--tw-muted)]
                  focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";
  @endphp

  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">

    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-sm font-semibold {{ $fg }}">Purchase details</div>
        <div class="mt-1 text-xs {{ $muted }}">
          Choose a type, fill the fields, then save as draft.
        </div>
      </div>
    </div>

    {{-- TYPE SELECTOR --}}
    <div class="mt-5">
      <div class="text-xs font-semibold {{ $muted }}">Purchase type</div>

      <div class="mt-2 grid gap-3 sm:grid-cols-3" id="type-grid">

        @php
          $typeErrCard = $errors->has('type')
            ? "border-rose-500/50 bg-rose-500/5 ring-1 ring-rose-500/15"
            : "";
        @endphp

        <label data-type-card="import"
               class="type-card cursor-pointer rounded-xl border {{ $border }} {{ $surface2 }} p-3 hover:border-emerald-500/40 transition {{ $typeErrCard }}">
          <input type="radio" name="type" value="import" class="sr-only js-type"
                 {{ old('type','import') === 'import' ? 'checked' : '' }}>

          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold {{ $fg }}">Import</div>
              <div class="mt-1 text-xs {{ $muted }}">Transport → TR8 → Offload</div>
            </div>

            <div class="type-check hidden mt-0.5 rounded-full border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold">
              Selected
            </div>
          </div>
        </label>

        <label data-type-card="local_depot"
               class="type-card cursor-pointer rounded-xl border {{ $border }} {{ $surface2 }} p-3 hover:border-emerald-500/40 transition {{ $typeErrCard }}">
          <input type="radio" name="type" value="local_depot" class="sr-only js-type"
                 {{ old('type') === 'local_depot' ? 'checked' : '' }}>

          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold {{ $fg }}">Local depot</div>
              <div class="mt-1 text-xs {{ $muted }}">Ownership change only</div>
            </div>

            <div class="type-check hidden mt-0.5 rounded-full border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold">
              Selected
            </div>
          </div>
        </label>

        <label data-type-card="cross_dock"
               class="type-card cursor-pointer rounded-xl border {{ $border }} {{ $surface2 }} p-3 hover:border-emerald-500/40 transition {{ $typeErrCard }}">
          <input type="radio" name="type" value="cross_dock" class="sr-only js-type"
                 {{ old('type') === 'cross_dock' ? 'checked' : '' }}>

          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold {{ $fg }}">Cross dock</div>
              <div class="mt-1 text-xs {{ $muted }}">Loaded truck → direct delivery</div>
            </div>

            <div class="type-check hidden mt-0.5 rounded-full border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold">
              Selected
            </div>
          </div>
        </label>

      </div>

      <div id="type-context"
           class="mt-3 rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $fg }}">
        <!-- JS will fill -->
      </div>

      @error('type')
        <div class="mt-2 flex items-center gap-2 text-xs {{ $errText }}">
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-500/15 ring-1 ring-rose-500/20">!</span>
          <span>{{ $message }}</span>
        </div>
      @enderror
    </div>

    {{-- FORM FIELDS --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2">

      {{-- Reference --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Reference (optional)</label>
        <input name="reference" value="{{ old('reference') }}"
               class="{{ $fieldBase }} @error('reference') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="Leave blank to auto-generate (e.g. PO-2026-00001)">
        <div class="mt-1 text-xs {{ $hintText }}">If blank, the system generates one.</div>
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
            <option value="{{ $p->id }}" {{ (string)old('product_id')===(string)$p->id ? 'selected' : '' }}>
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
            <option value="{{ $s->id }}" {{ (string)old('supplier_id')===(string)$s->id ? 'selected' : '' }}>
              {{ $s->name }}
            </option>
          @endforeach
        </select>
        @error('supplier_id')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Depot --}}
      <div id="depot-wrap" class="hidden">
        <label class="text-xs font-semibold {{ $muted }}">Depot (required for local depot)</label>
        <select name="depot_id"
                class="{{ $fieldBase }} @error('depot_id') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror">
          <option value="">Select…</option>
          @foreach($depots as $d)
            <option value="{{ $d->id }}" {{ (string)old('depot_id')===(string)$d->id ? 'selected' : '' }}>
              {{ $d->name }}
            </option>
          @endforeach
        </select>
        @error('depot_id')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Quantity --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Quantity</label>
        <input name="qty" value="{{ old('qty') }}" inputmode="decimal"
               class="{{ $fieldBase }} @error('qty') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="e.g. 9000">
        <div class="mt-1 text-xs {{ $hintText }}">Base unit (litres for fuel).</div>
        @error('qty')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Unit price --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Unit price</label>
        <input name="unit_price" value="{{ old('unit_price') }}" inputmode="decimal"
               class="{{ $fieldBase }} @error('unit_price') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="e.g. 0.65">
        @error('unit_price')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Currency --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Currency</label>
        <input name="currency" value="{{ old('currency','USD') }}"
               class="{{ $fieldBase }} @error('currency') {{ $errBorder }} {{ $errBg }} {{ $errRing }} @enderror"
               placeholder="USD">
        @error('currency')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

      {{-- Purchase date --}}
      <div>
        <label class="text-xs font-semibold {{ $muted }}">Purchase date (optional)</label>
        <input type="date" name="purchase_date" value="{{ old('purchase_date') }}"
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
                  placeholder="Any extra context...">{{ old('notes') }}</textarea>
        @error('notes')
          <div class="mt-1 text-xs {{ $errText }}">{{ $message }}</div>
        @enderror
      </div>

    </div>

    <div class="mt-6 flex items-center justify-between">
      <a href="{{ route('purchases.index') }}" class="{{ $btnLink }}">Cancel</a>

      <button type="submit" class="{{ $btnPrimary }}">
        Save draft
      </button>
    </div>

  </div>
</form>

{{-- tiny JS: open depot if depot has error --}}
<script>
  (function () {
    const hasDepotErr = {!! $errors->has('depot_id') ? 'true' : 'false' !!};
    if (hasDepotErr) {
      const depotWrap = document.getElementById('depot-wrap');
      depotWrap?.classList.remove('hidden');
    }
  })();
</script>
{{-- Tiny JS: mode switching --}}
<script>
  function applySelectedTypeStyles(val) {
    document.querySelectorAll('.type-card').forEach(card => {
      const cardType = card.getAttribute('data-type-card');
      const badge = card.querySelector('.type-check');

      if (cardType === val) {
        card.classList.add(
          'border-emerald-500/50',
          'bg-gradient-to-r',
          'from-emerald-500/10',
          'via-emerald-500/5',
          'to-cyan-500/10'
        );
        badge?.classList.remove('hidden');
      } else {
        card.classList.remove(
          'border-emerald-500/50',
          'bg-gradient-to-r',
          'from-emerald-500/10',
          'via-emerald-500/5',
          'to-cyan-500/10'
        );
        badge?.classList.add('hidden');
      }
    });
  }

  function syncPurchaseTypeUI() {
    const val = document.querySelector('input[name="type"]:checked')?.value || 'import';

    const ctx = document.getElementById('type-context');
    const depotWrap = document.getElementById('depot-wrap');

    applySelectedTypeStyles(val);

    if (val === 'import') {
      ctx.textContent = "This purchase will enter nominations and transport workflow after confirmation.";
      depotWrap.classList.add('hidden');
    } else if (val === 'local_depot') {
      ctx.textContent = "This is a local depot ownership change. After confirmation, receive it into the selected depot.";
      depotWrap.classList.remove('hidden');
    } else {
      ctx.textContent = "Cross dock: on confirmation we receipt into CROSS DOCK and you can sell directly.";
      depotWrap.classList.add('hidden');
    }
  }

  document.querySelectorAll('.js-type').forEach(r => r.addEventListener('change', syncPurchaseTypeUI));
  syncPurchaseTypeUI();
</script>
@endsection