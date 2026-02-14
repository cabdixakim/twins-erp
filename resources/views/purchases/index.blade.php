@extends('layouts.app')

@section('title', 'Purchases')
@section('subtitle', 'Draft → Confirmed creates a Batch')

@section('content')

@php
  $totalCount = method_exists($purchases, 'total') ? $purchases->total() : (is_countable($purchases) ? count($purchases) : null);

  // If controller hasn't provided options yet, fallback.
  $supplierOptions = $supplierOptions ?? [];
  $typeOptions     = $typeOptions ?? ['import','local_depot','cross_dock'];
  $statusOptions   = $statusOptions ?? ['draft','confirmed'];

  // Theme tokens
  $fg      = 'text-[color:var(--tw-fg)]';
  $muted   = 'text-[color:var(--tw-muted)]';
  $bg      = 'bg-[color:var(--tw-bg)]';
  $surface = 'bg-[color:var(--tw-surface)]';
  $surface2= 'bg-[color:var(--tw-surface-2)]';
  $border  = 'border-[color:var(--tw-border)]';
  $ring    = 'focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]';

  // Reusable button styles
  $btnBase   = 'inline-flex items-center justify-center gap-2 rounded-xl border font-semibold transition select-none';
  $btnGhost  = $btnBase.' border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] '.$fg.' hover:bg-[color:var(--tw-surface)]';
  $btnPrime  = $btnBase.' border-[color:var(--tw-accent)] bg-[color:var(--tw-accent-soft)] '.$fg.' hover:brightness-110';

  $pillBase  = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
@endphp

<div class="flex flex-col gap-4">

  {{-- Header (mobile-first, compact) --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 sm:p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-3">
          <span class="h-9 w-9 rounded-2xl grid place-items-center {{ $surface2 }} border {{ $border }}">
            <span class="text-base" aria-hidden="true">🧾</span>
          </span>

          <div class="min-w-0">
            <div class="flex items-center gap-2 min-w-0">
              <h1 class="text-[15px] sm:text-base font-semibold {{ $fg }} leading-tight truncate">Purchases</h1>

              @if(!is_null($totalCount))
                <span class="shrink-0 {{ $pillBase }} {{ $border }} {{ $surface2 }} {{ $fg }}">
                  {{ number_format($totalCount) }}
                </span>
              @endif
            </div>

            <p class="mt-0.5 text-[11px] sm:text-[12px] {{ $muted }} leading-snug">
              Draft → Confirmed creates a Batch.
            </p>
          </div>
        </div>
      </div>

      <div class="shrink-0 flex items-center gap-2">
        {{-- Export (icon-only on mobile, label on sm+) --}}
        <button
          type="button"
          id="btnExportPurchasesCsvTop"
          class="{{ $btnGhost }} h-9 w-9 sm:w-auto sm:px-3 sm:gap-2 text-[12px]"
          aria-label="Export CSV"
        >
          <span class="text-base leading-none" aria-hidden="true">⤓</span>
          <span class="hidden sm:inline">Export</span>
        </button>

        {{-- New purchase (compact on mobile) --}}
        <a
          href="{{ route('purchases.create') }}"
          class="{{ $btnPrime }} h-9 px-3 sm:h-10 sm:px-4 text-[12px] sm:text-[13px]"
        >
          <span class="text-base leading-none" aria-hidden="true">＋</span>
          <span class="hidden xs:inline">New</span>
          <span class="xs:hidden">Add</span>
        </a>
      </div>
    </div>

    <p class="mt-3 text-[12px] sm:text-sm {{ $muted }} leading-snug">
      Manage procurement records. Confirming a draft creates a batch and routes it into the correct workflow.
    </p>
  </div>

  {{-- helper breakpoint (only if you don’t already have xs) --}}
  <style>
    @media (min-width: 420px){ .xs\:inline{display:inline} .xs\:hidden{display:none} }
    @media (max-width: 419px){ .xs\:inline{display:none} .xs\:hidden{display:inline} }
  </style>

  @if (session('status'))
    <div class="rounded-xl border border-emerald-500/30 bg-[color:var(--tw-accent-soft)] p-3 text-sm text-emerald-100">
      {!! nl2br(e(session('status'))) !!}
    </div>
  @endif

  {{-- Filters (mobile-friendly) --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 sm:p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="text-[13px] sm:text-sm font-semibold {{ $fg }}">Filters</div>
        <div class="mt-1 text-[11px] sm:text-xs {{ $muted }} leading-snug">
          Search + dropdowns. Export downloads only the visible rows on this page.
        </div>
      </div>

      <!-- {{-- Export (secondary, hide on mobile since header already has it) --}}
      <button
        type="button"
        id="btnExportPurchasesCsvInline"
        class="hidden sm:inline-flex {{ $btnGhost }} h-10 px-3 text-[12px]"
      >
        Export CSV
      </button> -->
    </div>

    <form method="GET" action="{{ url()->current() }}"
          class="mt-4 grid grid-cols-1 gap-3
                 lg:grid-cols-[minmax(0,1fr)_220px_170px_170px_auto] lg:items-end">

      {{-- Search (grows) --}}
      <div class="min-w-0">
        <label class="block text-[11px] {{ $muted }} mb-1">Search</label>
        <div class="relative">
          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 {{ $muted }}">⌕</span>
          <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Purchase #, batch #, supplier..."
            class="h-10 w-full rounded-xl border {{ $border }} {{ $surface2 }} pl-9 pr-3 text-sm {{ $fg }}
                   placeholder:text-[color:var(--tw-muted)] focus:outline-none {{ $ring }}"
          />
        </div>
      </div>

      {{-- Supplier --}}
      <div class="min-w-0">
        <label class="block text-[11px] {{ $muted }} mb-1">Supplier</label>
        <select
          name="supplier"
          class="h-10 w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }}
                 focus:outline-none {{ $ring }}"
        >
          <option value="">All</option>
          @foreach($supplierOptions as $s)
            <option value="{{ $s }}" @selected(request('supplier') === $s)>{{ $s }}</option>
          @endforeach
        </select>
      </div>

      {{-- Type --}}
      <div class="min-w-0">
        <label class="block text-[11px] {{ $muted }} mb-1">Type</label>
        <select
          name="type"
          class="h-10 w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }}
                 focus:outline-none {{ $ring }}"
        >
          <option value="">All</option>
          @foreach($typeOptions as $t)
            <option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst(str_replace('_',' ', $t)) }}</option>
          @endforeach
        </select>
      </div>

      {{-- Status --}}
      <div class="min-w-0">
        <label class="block text-[11px] {{ $muted }} mb-1">Status</label>
        <select
          name="status"
          class="h-10 w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }}
                 focus:outline-none {{ $ring }}"
        >
          <option value="">All</option>
          @foreach($statusOptions as $st)
            <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst($st) }}</option>
          @endforeach
        </select>
      </div>

      {{-- Buttons --}}
      <div class="flex items-center gap-2 justify-end">
        <button
          type="submit"
          class="h-10 {{ $btnGhost }} px-4 text-[13px]"
        >
          Filter
        </button>

        <a
          href="{{ url()->current() }}"
          class="h-10 {{ $btnGhost }} px-4 text-[13px]"
        >
          Reset
        </a>
      </div>
    </form>

    {{-- Mobile-only export button (since we hide inline export on small screens) --}}
    <button
      type="button"
      id="btnExportPurchasesCsvMobile"
      class="mt-3 sm:hidden w-full {{ $btnGhost }} h-10 px-4 text-[13px]"
    >
      Export CSV
    </button>
  </div>

  {{-- Table --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <div class="px-3 sm:px-5 py-3 sm:py-4 border-b {{ $border }} flex items-start sm:items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="text-[13px] sm:text-sm font-semibold {{ $fg }}">Recent purchases</div>
        <div class="mt-0.5 text-[11px] sm:text-xs {{ $muted }}">Tap a row to open the purchase.</div>
      </div>

      <div class="hidden sm:flex items-center gap-2">
        <button
          type="button"
          id="btnExportPurchasesCsvTable"
          class="{{ $btnGhost }} h-10 px-3 text-[12px]"
        >
          Export CSV
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm" id="purchasesTable">
        <thead class="{{ $surface2 }}">
          <tr class="text-left text-xs {{ $muted }}">
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Purchase</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Supplier</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Type</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Qty</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Unit price</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Est. total</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Status</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Date</th>
            <th class="px-3 sm:px-5 py-3 text-right whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-[color:var(--tw-border)]">
          @forelse($purchases as $p)
            @php
              $typeLabel = match($p->type) {
                'import' => 'Import',
                'local_depot' => 'Local depot',
                'cross_dock' => 'Cross dock',
                default => ucfirst($p->type),
              };

              $statusClasses = match($p->status) {
                'draft' =>
                    'border-slate-400/40 bg-slate-200 text-slate-900
                    dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',

                'confirmed' =>
                    'border-emerald-600 bg-emerald-500 text-white
                    dark:border-emerald-400 dark:bg-emerald-400 dark:text-emerald-950',

                'received' =>
                    'border-blue-700 bg-blue-500 text-white
                    dark:border-blue-400 dark:bg-blue-400 dark:text-blue-950',

                default =>
                    'border-slate-400/40 bg-slate-200 text-slate-900
                    dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
              };

              $qty = (float) ($p->qty ?? 0);
              $unit = (float) ($p->unit_price ?? 0);
              $total = $qty * $unit;

              // Adjust to your actual supplier storage
              $supplierName = $p->supplier_name ?? ($p->supplier?->name ?? ($p->supplier ?? '—'));

              $showUrl = route('purchases.show', $p);
            @endphp

            <tr class="group hover:bg-[color:var(--tw-surface-2)] cursor-pointer"
                data-href="{{ $showUrl }}"
                data-export-row="1">
              <td class="px-3 sm:px-5 py-4">
                <div class="font-semibold {{ $fg }}">
                  Purchase #{{ $p->display_ref ?? $p->id }}
                </div>
                <div class="mt-1 text-xs {{ $muted }}">
                  @if($p->batch_id)
                    Batch: <span class="{{ $fg }}">#{{ $p->batch_id }}</span>
                  @else
                    <span>No batch yet</span>
                  @endif
                </div>
              </td>

              <td class="px-3 sm:px-5 py-4 {{ $fg }}">
                {{ $supplierName }}
              </td>

              <td class="px-3 sm:px-5 py-4">
                <span class="inline-flex items-center rounded-full border {{ $border }} {{ $surface2 }} px-2.5 py-1 text-xs {{ $fg }} whitespace-nowrap">
                  {{ $typeLabel }}
                </span>
              </td>

              <td class="px-3 sm:px-5 py-4 {{ $fg }}">
                {{ number_format($qty, 3) }}
                <span class="text-xs {{ $muted }}">L</span>
              </td>

              <td class="px-3 sm:px-5 py-4 {{ $fg }}">
                <span class="{{ $muted }}">{{ strtoupper($p->currency ?? 'USD') }}</span>
                {{ number_format($unit, 6) }}
              </td>

              <td class="px-3 sm:px-5 py-4 {{ $fg }}">
                <span class="{{ $muted }}">{{ strtoupper($p->currency ?? 'USD') }}</span>
                {{ number_format($total, 2) }}
              </td>

              <td class="px-3 sm:px-5 py-4">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs {{ $statusClasses }}">
                  {{ ucfirst($p->status) }}
                </span>
              </td>

              <td class="px-3 sm:px-5 py-4 {{ $muted }}">
                {{ $p->purchase_date?->format('Y-m-d') ?? '—' }}
              </td>

              <td class="px-3 sm:px-5 py-4 text-right">
                <a href="{{ $showUrl }}"
                   class="inline-flex items-center rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-1.5 text-xs font-semibold {{ $fg }}
                          hover:bg-[color:var(--tw-surface)]"
                   onclick="event.stopPropagation();">
                  Open
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-5 py-10 text-center text-sm {{ $muted }}">
                No purchases yet.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-3 sm:px-5 py-4 border-t {{ $border }}">
      {{ $purchases->links() }}
    </div>
  </div>

</div>

@endsection

@push('scripts')
<script>
(() => {
  // Row click → open show page
  document.addEventListener('click', (e) => {
    const row = e.target.closest('tr[data-href]');
    if (!row) return;
    if (e.target.closest('a,button,input,select,textarea,label')) return;
    window.location.href = row.getAttribute('data-href');
  });

  function exportCsv() {
    const table = document.getElementById('purchasesTable');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr[data-export-row="1"]'));
    if (!rows.length) return;

    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
    headers.pop(); // remove Action

    const csvEscape = (v) => {
      const s = String(v ?? '').replace(/\r?\n|\r/g, ' ').trim();
      if (/[",]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
      return s;
    };

    const data = rows.map(tr => {
      const tds = Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim());
      tds.pop(); // remove Action
      return tds.map(csvEscape).join(',');
    });

    const csv = [headers.map(csvEscape).join(','), ...data].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });

    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `purchases_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 500);
  }

  [
    'btnExportPurchasesCsvTop',
    'btnExportPurchasesCsvInline',
    'btnExportPurchasesCsvMobile',
    'btnExportPurchasesCsvTable'
  ].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('click', exportCsv);
  });
})();
</script>
@endpush