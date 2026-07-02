@php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $reasonColour = [
      'depot_shrinkage'        => 'border-amber-500/50 bg-amber-500/10 text-amber-400',
      'write_off'              => 'border-rose-500/50 bg-rose-500/10 text-rose-400',
      'meter_variance'         => 'border-blue-500/50 bg-blue-500/10 text-blue-400',
      'stock_count_correction' => 'border-purple-500/50 bg-purple-500/10 text-purple-400',
      'transit_loss'           => 'border-orange-500/50 bg-orange-500/10 text-orange-400',
  ];
@endphp

@extends('layouts.app')

@section('title', 'Write Offs')
@section('subtitle', 'Shrinkage, write-offs, meter variances and stock count corrections')

@section('content')

{{-- Header bar --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <div class="flex flex-wrap gap-2">
    <form method="GET" action="{{ route('inventory-adjustments.index') }}" class="flex flex-wrap gap-2">
      <select name="depot" onchange="this.form.submit()"
              class="h-9 rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} text-sm px-3 focus:outline-none">
        <option value="">All depots</option>
        @foreach($depots as $d)
          <option value="{{ $d->id }}" {{ request('depot') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
        @endforeach
      </select>
      <select name="reason" onchange="this.form.submit()"
              class="h-9 rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} text-sm px-3 focus:outline-none">
        <option value="">All reasons</option>
        <option value="depot_shrinkage"        {{ request('reason') === 'depot_shrinkage' ? 'selected' : '' }}>Depot Shrinkage</option>
        <option value="write_off"              {{ request('reason') === 'write_off' ? 'selected' : '' }}>Write-off</option>
        <option value="meter_variance"         {{ request('reason') === 'meter_variance' ? 'selected' : '' }}>Meter Variance</option>
        <option value="stock_count_correction" {{ request('reason') === 'stock_count_correction' ? 'selected' : '' }}>Stock Count Correction</option>
        <option value="transit_loss"           {{ request('reason') === 'transit_loss' ? 'selected' : '' }}>Transit Loss</option>
      </select>
      <input type="date" name="from" value="{{ request('from') }}"
             class="h-9 rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} text-sm px-3 focus:outline-none">
      <input type="date" name="to" value="{{ request('to') }}"
             class="h-9 rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} text-sm px-3 focus:outline-none">
      @if(request()->anyFilled(['depot','reason','from','to']))
        <a href="{{ route('inventory-adjustments.index') }}"
           class="h-9 px-3 rounded-xl border {{ $border }} {{ $fg }} text-sm flex items-center hover:opacity-70 transition">Clear</a>
      @endif
    </form>
  </div>
  <div class="flex items-center gap-2">
    <a href="{{ route('inventory-adjustments.export', request()->query()) }}"
       class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} {{ $fg }} text-sm font-semibold flex items-center gap-2 hover:opacity-70 transition">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
      </svg>
      Export CSV
    </a>
    <a href="{{ route('inventory-adjustments.create') }}"
       class="h-9 px-4 rounded-xl border border-rose-600 bg-rose-500 text-white text-sm font-semibold flex items-center gap-2 hover:bg-rose-600 transition">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      Record write-off
    </a>
  </div>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
  <div class="relative overflow-hidden rounded-2xl border {{ $border }} {{ $surface }} p-4">
    <div class="absolute inset-0 opacity-[0.06]" style="background:linear-gradient(135deg,#f43f5e,transparent 60%)"></div>
    <div class="relative flex items-start justify-between">
      <div>
        <div class="text-[10px] font-bold {{ $muted }} uppercase tracking-widest mb-2">Total loss (all time)</div>
        <div class="text-3xl font-extrabold {{ $fg }} tabular-nums leading-none">{{ number_format($totalQty, 3) }}<span class="text-sm font-semibold {{ $muted }} ml-1">L</span></div>
        <div class="text-sm font-semibold s-rose mt-2">{{ $currency }} {{ number_format($totalValue, 2) }}</div>
        <div class="text-xs {{ $muted }} mt-2">Across {{ $adjustments->total() }} adjustment{{ $adjustments->total() !== 1 ? 's' : '' }}</div>
      </div>
      <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(244,63,94,.12)">
        <svg class="w-5 h-5" style="color:#f43f5e" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="relative overflow-hidden rounded-2xl border {{ $border }} {{ $surface }} p-4">
    <div class="absolute inset-0 opacity-[0.06]" style="background:linear-gradient(135deg,#f43f5e,transparent 60%)"></div>
    <div class="relative flex items-start justify-between">
      <div>
        <div class="text-[10px] font-bold {{ $muted }} uppercase tracking-widest mb-2">Non-recoverable</div>
        <div class="text-3xl font-extrabold tabular-nums leading-none" style="color:#f43f5e">{{ number_format($nonRecoverableQty, 3) }}<span class="text-sm font-semibold {{ $muted }} ml-1">L</span></div>
        <div class="text-sm font-semibold mt-2" style="color:#f43f5e">{{ $currency }} {{ number_format($nonRecoverableValue, 2) }}</div>
        <div class="text-xs {{ $muted }} mt-2">Absorbed as a straight loss</div>
      </div>
      <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(244,63,94,.12)">
        <svg class="w-5 h-5" style="color:#f43f5e" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </div>
    </div>
  </div>
  <div class="relative overflow-hidden rounded-2xl border {{ $border }} {{ $surface }} p-4">
    <div class="absolute inset-0 opacity-[0.06]" style="background:linear-gradient(135deg,#10b981,transparent 60%)"></div>
    <div class="relative flex items-start justify-between">
      <div>
        <div class="text-[10px] font-bold {{ $muted }} uppercase tracking-widest mb-2">Recoverable</div>
        <div class="text-3xl font-extrabold tabular-nums leading-none" style="color:#10b981">{{ number_format($recoverableQty, 3) }}<span class="text-sm font-semibold {{ $muted }} ml-1">L</span></div>
        <div class="text-sm font-semibold mt-2" style="color:#10b981">{{ $currency }} {{ number_format($recoverableValue, 2) }}</div>
        <div class="text-xs {{ $muted }} mt-2">Claimable / chargeable to a third party</div>
      </div>
      <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(16,185,129,.12)">
        <svg class="w-5 h-5" style="color:#10b981" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
    </div>
  </div>
</div>

{{-- Table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
  @if($adjustments->isEmpty())
    <div class="px-6 py-14 text-center">
      <div class="text-3xl mb-3">📋</div>
      <div class="text-sm font-semibold {{ $fg }}">No stock adjustments yet</div>
      <div class="text-xs {{ $muted }} mt-1">Depot shrinkage is auto-posted on every receipt. Manual write-offs will appear here.</div>
    </div>
  @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b {{ $border }} {{ $surface2 }}">
            <th class="px-4 py-3 text-left text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Date</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Reason</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Product</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Depot</th>
            <th class="px-4 py-3 text-right text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Qty lost</th>
            <th class="px-4 py-3 text-right text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Unit cost</th>
            <th class="px-4 py-3 text-right text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Loss value</th>
            <th class="px-4 py-3 text-center text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Recoverable</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold {{ $muted }} uppercase tracking-wider">Notes</th>
          </tr>
        </thead>
        <tbody class="divide-y {{ $border }}">
          @foreach($adjustments as $adj)
            @php $rc = $reasonColour[$adj->reason_type] ?? 'border-gray-500/40 bg-gray-500/10 text-gray-400'; @endphp
            <tr class="hover:{{ $surface2 }} transition">
              <td class="px-4 py-3 {{ $muted }} text-xs whitespace-nowrap">
                {{ $adj->created_at->format('d M Y') }}<br>
                <span class="text-[10px]">{{ $adj->created_at->format('H:i') }}</span>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold {{ $rc }}">
                  {{ \App\Models\InventoryAdjustment::reasonLabel($adj->reason_type) }}
                </span>
              </td>
              <td class="px-4 py-3 {{ $fg }} font-medium text-xs">{{ $adj->product?->name ?? '—' }}</td>
              <td class="px-4 py-3 {{ $muted }} text-xs">{{ $adj->depot?->name ?? '—' }}</td>
              <td class="px-4 py-3 text-right font-mono text-xs {{ $fg }}">{{ number_format($adj->qty, 3) }}</td>
              <td class="px-4 py-3 text-right font-mono text-xs {{ $muted }}">{{ $currency }} {{ number_format($adj->unit_cost, 4) }}</td>
              <td class="px-4 py-3 text-right font-mono text-sm font-semibold s-rose">{{ $currency }} {{ number_format($adj->total_value, 2) }}</td>
              <td class="px-4 py-3 text-center">
                @if($adj->recoverable)
                  <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" style="border-color:rgba(16,185,129,.4);background:rgba(16,185,129,.1);color:#10b981">Recoverable</span>
                @else
                  <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold" style="border-color:rgba(244,63,94,.4);background:rgba(244,63,94,.1);color:#f43f5e">Non-recoverable</span>
                @endif
              </td>
              <td class="px-4 py-3 {{ $muted }} text-xs max-w-xs truncate">{{ $adj->notes ?: '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if($adjustments->hasPages())
      <div class="px-4 py-3 border-t {{ $border }}">
        {{ $adjustments->links() }}
      </div>
    @endif
  @endif
</div>

@endsection
