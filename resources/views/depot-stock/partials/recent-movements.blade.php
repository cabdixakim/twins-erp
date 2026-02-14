{{-- resources/views/depot-stock/partials/recent-movements.blade.php --}}
@php
  // Expect: $recentMovements, $border, $surface, $surface2, $fg, $muted, $pillGreen
@endphp

<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden mt-4">
  <div class="p-4 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between gap-3">
    <div>
      <div class="text-sm font-semibold {{ $fg }}">Recent movements</div>
      <div class="mt-0.5 text-xs {{ $muted }}">Last 12 receipts into this depot</div>
    </div>
  </div>

  @if($recentMovements->isEmpty())
    <div class="p-6 text-sm {{ $muted }}">No movements yet.</div>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="{{ $surface2 }} border-b {{ $border }}">
          <tr class="text-left">
            <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">When</th>
            <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Type</th>
            <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Batch</th>
            <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Product</th>
            <th class="px-4 py-3 text-[11px] font-semibold {{ $muted }}">Qty</th>
          </tr>
        </thead>
        <tbody class="divide-y {{ $border }}">
          @foreach($recentMovements as $m)
            <tr class="hover:bg-[color:var(--tw-surface-2)]/60">
              <td class="px-4 py-3 {{ $muted }}">{{ $m->created_at?->format('Y-m-d H:i') }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $pillGreen }}">
                  {{ strtoupper($m->type) }}
                </span>
              </td>
              <td class="px-4 py-3 {{ $fg }}">{{ $m->batch?->code ?? ('#' . ($m->batch_id ?? '—')) }}</td>
              <td class="px-4 py-3 {{ $fg }}">{{ $m->product?->name ?? ('#' . ($m->product_id ?? '—')) }}</td>
              <td class="px-4 py-3 font-semibold {{ $fg }}">{{ number_format((float)$m->qty, 3) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>