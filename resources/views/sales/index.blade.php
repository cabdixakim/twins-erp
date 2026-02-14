@extends('layouts.app')

@php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $selectedId = $selected?->id ?? null;

  $fieldBase = "mt-1 w-full rounded-xl border {$border} {$surface2} p-2 text-sm {$fg} outline-none focus:ring-2 focus:ring-emerald-500/30";
  $fieldErr  = "border-rose-500/40 ring-2 ring-rose-500/20";
  $errText   = "mt-1 text-[11px] text-rose-300";

  $statusPill = fn($s) => match($s) {
    'draft'    => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'posted'   => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-100',
    default    => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };
@endphp

@section('title', 'Sales')
@section('subtitle', 'Draft → Posted issues stock (FIFO)')

@section('content')

@if(session('status'))
  <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">
    {!! nl2br(e(session('status'))) !!}
  </div>
@endif

@if(session('error'))
  <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-100">
    {{ session('error') }}
  </div>
@endif

<div class="grid gap-6 md:grid-cols-3">

  {{-- LEFT --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="text-sm font-semibold {{ $fg }}">Sales</div>
        <div class="mt-1 text-xs {{ $muted }}">Select a sale to view details.</div>
      </div>

      <button type="button" id="btnNewSale"
        class="inline-flex items-center gap-2 h-9 px-3 rounded-xl border border-emerald-500/30 bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-500/20 transition">
        + New
      </button>
    </div>

    <div class="mt-4 space-y-2">
      @forelse($sales as $s)
        @php $isActive = $selectedId === $s->id; $pill = $statusPill($s->status); @endphp
        <a href="{{ route('sales.index', ['sale' => $s->id]) }}"
           class="block rounded-xl border {{ $border }} {{ $isActive ? $surface2 : '' }} p-3 hover:bg-[color:var(--tw-surface-2)] transition">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs {{ $muted }}">#{{ $s->reference }}</div>
              <div class="mt-0.5 text-sm font-semibold {{ $fg }} truncate">
                {{ $s->client_name ?: 'Client —' }}
              </div>
              <div class="mt-1 text-[11px] {{ $muted }} truncate">
                {{ $s->depot?->name ?? 'Depot' }} · {{ $s->product?->name ?? 'Product' }}
              </div>
            </div>

            <span class="shrink-0 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $pill }}">
              {{ ucfirst($s->status) }}
            </span>
          </div>
        </a>
      @empty
        <div class="text-xs {{ $muted }}">No sales yet.</div>
      @endforelse
    </div>

    <div class="mt-4">
      {{ $sales->links() }}
    </div>
  </div>

  {{-- RIGHT --}}
  <div class="md:col-span-2 space-y-4">
    @if(!$selected)
      <div class="rounded-2xl border border-dashed {{ $border }} {{ $surface }} p-6 text-center">
        <div class="text-sm {{ $fg }}">No sale selected.</div>
        <div class="mt-1 text-xs {{ $muted }}">Create one using “New”.</div>
      </div>
    @else
      @include('sales.partials.details', ['sale' => $selected])
    @endif
  </div>

</div>

{{-- NEW SALE MODAL --}}
<div id="newSaleModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4">
  <div class="w-full max-w-2xl rounded-2xl border {{ $border }} {{ $surface }} shadow-xl overflow-hidden">
    {{-- Make modal scrollable --}}
    <div class="max-h-[85vh] overflow-y-auto">
      <div class="p-5 border-b {{ $border }} {{ $surface2 }}">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-base font-semibold {{ $fg }}">New sale</div>
            <div class="mt-1 text-xs {{ $muted }}">Draft first, then post to issue stock FIFO.</div>
          </div>
          <button type="button" id="closeNewSale"
            class="h-9 w-9 inline-flex items-center justify-center rounded-xl border {{ $border }} {{ $surface }}
                   {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
        </div>
      </div>

      <form method="POST" action="{{ route('sales.store') }}" class="p-5" novalidate>
        @csrf

        {{-- Top-level error summary --}}
        @if($errors->any())
          <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-100">
            Please fix the highlighted fields.
          </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="text-xs font-semibold {{ $muted }}">Depot</label>
            <select name="depot_id" class="{{ $fieldBase }} @error('depot_id') {{ $fieldErr }} @enderror">
              <option value="">— Select depot —</option>
              @foreach($depots as $d)
                <option value="{{ $d->id }}" @selected(old('depot_id') == $d->id)>{{ $d->name }}</option>
              @endforeach
            </select>
            @error('depot_id') <div class="{{ $errText }}">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-xs font-semibold {{ $muted }}">Product</label>
            <select name="product_id" class="{{ $fieldBase }} @error('product_id') {{ $fieldErr }} @enderror">
              <option value="">— Select product —</option>
              @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }}</option>
              @endforeach
            </select>
            @error('product_id') <div class="{{ $errText }}">{{ $message }}</div> @enderror
          </div>

          <div class="sm:col-span-2">
            <label class="text-xs font-semibold {{ $muted }}">Client name</label>
            <input name="client_name" value="{{ old('client_name') }}"
                   class="{{ $fieldBase }} @error('client_name') {{ $fieldErr }} @enderror"
                   placeholder="e.g. Katanga Mining" />
            @error('client_name') <div class="{{ $errText }}">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-xs font-semibold {{ $muted }}">Sale date</label>
            <input type="date" name="sale_date" value="{{ old('sale_date') }}"
                   class="{{ $fieldBase }} @error('sale_date') {{ $fieldErr }} @enderror" />
            @error('sale_date') <div class="{{ $errText }}">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-xs font-semibold {{ $muted }}">Currency</label>
            <input name="currency" value="{{ old('currency', 'USD') }}"
                   class="{{ $fieldBase }} @error('currency') {{ $fieldErr }} @enderror" />
            @error('currency') <div class="{{ $errText }}">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-xs font-semibold {{ $muted }}">Quantity</label>
            <input name="qty" inputmode="decimal" value="{{ old('qty') }}"
                   class="{{ $fieldBase }} @error('qty') {{ $fieldErr }} @enderror"
                   placeholder="e.g. 20000" />
            @error('qty') <div class="{{ $errText }}">{{ $message }}</div> @enderror
          </div>

          <div>
            <label class="text-xs font-semibold {{ $muted }}">Unit price</label>
            <input name="unit_price" inputmode="decimal" value="{{ old('unit_price') }}"
                   class="{{ $fieldBase }} @error('unit_price') {{ $fieldErr }} @enderror"
                   placeholder="e.g. 1.35" />
            @error('unit_price') <div class="{{ $errText }}">{{ $message }}</div> @enderror
          </div>

          {{-- Delivery --}}
          <div class="sm:col-span-2 rounded-xl border {{ $border }} {{ $surface2 }} p-3">
            <div class="text-xs font-semibold {{ $fg }}">Delivery</div>
            <div class="mt-1 text-[11px] {{ $muted }}">Choose one mode.</div>

            @php $oldMode = old('delivery_mode', 'ex_depot'); @endphp

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <label class="cursor-pointer rounded-xl border {{ $border }} {{ $surface }} p-3 text-xs {{ $fg }}
                            transition peer-checked:border-emerald-500/50 peer-checked:bg-emerald-500/10"
                     id="modeExLabel">
                <input type="radio" name="delivery_mode" value="ex_depot" class="sr-only js-delivery" {{ $oldMode === 'ex_depot' ? 'checked' : '' }}>
                <div class="font-semibold">Ex-depot</div>
                <div class="mt-1 text-[11px] {{ $muted }}">Client collects. No transport capture.</div>
              </label>

              <label class="cursor-pointer rounded-xl border {{ $border }} {{ $surface }} p-3 text-xs {{ $fg }}
                            transition"
                     id="modeDelLabel">
                <input type="radio" name="delivery_mode" value="delivered" class="sr-only js-delivery" {{ $oldMode === 'delivered' ? 'checked' : '' }}>
                <div class="font-semibold">Delivered</div>
                <div class="mt-1 text-[11px] {{ $muted }}">Capture transporter + truck + trailer.</div>
              </label>
            </div>

            @error('delivery_mode') <div class="{{ $errText }}">{{ $message }}</div> @enderror

            <div id="deliveryFields" class="mt-3 hidden grid gap-3 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label class="text-xs font-semibold {{ $muted }}">Transporter</label>
                <select name="transporter_id" class="{{ $fieldBase }} @error('transporter_id') {{ $fieldErr }} @enderror"
                        style="background: var(--tw-surface);">
                  <option value="">—</option>
                  @foreach($transporters as $t)
                    <option value="{{ $t->id }}" @selected(old('transporter_id') == $t->id)>{{ $t->name }}</option>
                  @endforeach
                </select>
                @error('transporter_id') <div class="{{ $errText }}">{{ $message }}</div> @enderror
              </div>

              <div>
                <label class="text-xs font-semibold {{ $muted }}">Truck no</label>
                <input name="truck_no" value="{{ old('truck_no') }}"
                       class="{{ $fieldBase }} @error('truck_no') {{ $fieldErr }} @enderror"
                       style="background: var(--tw-surface);" />
                @error('truck_no') <div class="{{ $errText }}">{{ $message }}</div> @enderror
              </div>

              <div>
                <label class="text-xs font-semibold {{ $muted }}">Trailer no</label>
                <input name="trailer_no" value="{{ old('trailer_no') }}"
                       class="{{ $fieldBase }} @error('trailer_no') {{ $fieldErr }} @enderror"
                       style="background: var(--tw-surface);" />
                @error('trailer_no') <div class="{{ $errText }}">{{ $message }}</div> @enderror
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold {{ $muted }}">Waybill</label>
                <input name="waybill_no" value="{{ old('waybill_no') }}"
                       class="{{ $fieldBase }} @error('waybill_no') {{ $fieldErr }} @enderror"
                       style="background: var(--tw-surface);" />
                @error('waybill_no') <div class="{{ $errText }}">{{ $message }}</div> @enderror
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold {{ $muted }}">Delivery notes</label>
                <textarea name="delivery_notes" rows="2"
                          class="{{ $fieldBase }} @error('delivery_notes') {{ $fieldErr }} @enderror"
                          style="background: var(--tw-surface);">{{ old('delivery_notes') }}</textarea>
                @error('delivery_notes') <div class="{{ $errText }}">{{ $message }}</div> @enderror
              </div>
            </div>
          </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <button type="button" id="cancelNewSale"
            class="h-10 px-4 rounded-xl border {{ $border }} {{ $surface2 }} text-sm font-semibold {{ $fg }}
                   hover:bg-[color:var(--tw-surface)] transition">
            Cancel
          </button>

          <button type="submit"
            class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-500/20 transition">
            Save draft
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  const modal = document.getElementById('newSaleModal');
  const openBtn = document.getElementById('btnNewSale');
  const closeBtn = document.getElementById('closeNewSale');
  const cancelBtn = document.getElementById('cancelNewSale');

  const modeExLabel = document.getElementById('modeExLabel');
  const modeDelLabel = document.getElementById('modeDelLabel');

  const open = () => modal && modal.classList.remove('hidden');
  const close = () => modal && modal.classList.add('hidden');

  on(openBtn, 'click', open);
  on(closeBtn, 'click', close);
  on(cancelBtn, 'click', close);
  on(modal, 'click', (e) => { if (e.target === modal) close(); });

  // delivery toggle + selected visuals
  const fields = document.getElementById('deliveryFields');

  const paintModes = () => {
    const v = document.querySelector('input[name="delivery_mode"]:checked')?.value || 'ex_depot';

    // reset
    [modeExLabel, modeDelLabel].forEach(l => {
      l?.classList.remove('border-emerald-500/50', 'bg-emerald-500/10', 'ring-2', 'ring-emerald-500/20');
    });

    if (v === 'delivered') {
      modeDelLabel?.classList.add('border-emerald-500/50','bg-emerald-500/10','ring-2','ring-emerald-500/20');
      fields?.classList.remove('hidden');
    } else {
      modeExLabel?.classList.add('border-emerald-500/50','bg-emerald-500/10','ring-2','ring-emerald-500/20');
      fields?.classList.add('hidden');
    }
  };

  document.querySelectorAll('.js-delivery').forEach(r => on(r, 'change', paintModes));
  paintModes();

  // Auto-open modal if validation errors exist (server-side)
  const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
  if (hasErrors) open();

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>

@endsection