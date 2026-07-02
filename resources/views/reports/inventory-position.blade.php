@php
    $title    = 'Inventory Position';
    $subtitle = 'Full purchase-to-sale reconciliation — where every litre is, its value, and every loss (recoverable & non-recoverable) — for any period.';
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";
    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";

    $fmt  = fn($n) => number_format(abs((float)$n), 0);
    $cfmt = fn($n) => $currency . ' ' . number_format((float)$n, 2);
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

{{-- Breadcrumb --}}
<div class="no-print flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Inventory Position</span>
</div>

{{-- Date filter --}}
<div class="no-print rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Period from</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <button type="submit" class="{{ $btnPrimary }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 10.607z"/></svg>
            Apply
        </button>
        <a href="{{ route('reports.inventory-position') }}" class="{{ $btnGhost }}">Reset</a>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 1: LIVE PIPELINE                                          --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h2 class="text-xs font-semibold uppercase tracking-widest {{ $muted }} mb-3">Live Pipeline — Where Every Litre Is Right Now</h2>

    {{-- Pipeline KPI cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 mb-4">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
                <span class="text-[10px] uppercase tracking-wide {{ $muted }}">At Shipper</span>
            </div>
            <div class="text-xl font-bold" style="color:#f59e0b">{{ number_format($pipelineTotals['at_shipper'], 0) }} L</div>
            <div class="text-[10px] {{ $muted }} mt-0.5">loaded / waiting dispatch</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-sky-400 flex-shrink-0"></span>
                <span class="text-[10px] uppercase tracking-wide {{ $muted }}">In Transit</span>
            </div>
            <div class="text-xl font-bold" style="color:#0ea5e9">{{ number_format($pipelineTotals['in_transit'], 0) }} L</div>
            <div class="text-[10px] {{ $muted }} mt-0.5">trucks on the road</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 flex-shrink-0"></span>
                <span class="text-[10px] uppercase tracking-wide {{ $muted }}">In Depots</span>
            </div>
            <div class="text-xl font-bold" style="color:#10b981">{{ number_format($pipelineTotals['in_depots'], 0) }} L</div>
            <div class="text-[10px] {{ $muted }} mt-0.5">physically in storage</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-purple-400 flex-shrink-0"></span>
                <span class="text-[10px] uppercase tracking-wide {{ $muted }}">Sold to Clients</span>
            </div>
            <div class="text-xl font-bold" style="color:#a855f7">{{ number_format($pipelineTotals['sold'], 0) }} L</div>
            <div class="text-[10px] {{ $muted }} mt-0.5">all time dispatched</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-rose-500 flex-shrink-0"></span>
                <span class="text-[10px] uppercase tracking-wide {{ $muted }}">Non-Recoverable</span>
            </div>
            <div class="text-xl font-bold" style="color:#f43f5e">{{ number_format($pipelineTotals['losses_non_recoverable'], 0) }} L</div>
            <div class="text-[10px] {{ $muted }} mt-0.5">{{ $cfmt($pipelineTotals['losses_non_recoverable_value']) }} absorbed</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-orange-400 flex-shrink-0"></span>
                <span class="text-[10px] uppercase tracking-wide {{ $muted }}">Recoverable</span>
            </div>
            <div class="text-xl font-bold" style="color:#fb923c">{{ number_format($pipelineTotals['losses_recoverable'], 0) }} L</div>
            <div class="text-[10px] {{ $muted }} mt-0.5">{{ $cfmt($pipelineTotals['losses_recoverable_value']) }} claimable</div>
        </div>
    </div>

    {{-- Pipeline by product table --}}
    @if(count($pipelineRows) > 0)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="{{ $surface2 }} border-b {{ $border }}">
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Product</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#f59e0b">At Shipper</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#0ea5e9">In Transit</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#10b981">In Depots</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#a855f7">Sold</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#f43f5e">Non-Recov. Loss</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#fb923c">Recov. Loss</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pipelineRows as $row)
                <tr class="border-b {{ $border }} last:border-0 hover:{{ $surface2 }} transition">
                    <td class="px-4 py-3 font-semibold {{ $fg }}">{{ $row['product'] }}</td>
                    <td class="px-4 py-3 text-right">
                        @if($row['at_shipper'] > 0)<span style="color:#f59e0b">{{ number_format($row['at_shipper'], 0) }} L</span>@else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['in_transit'] > 0)<span style="color:#0ea5e9">{{ number_format($row['in_transit'], 0) }} L</span>@else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['in_depots'] > 0)<span style="color:#10b981">{{ number_format($row['in_depots'], 0) }} L</span>@else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['sold'] > 0)<span style="color:#a855f7">{{ number_format($row['sold'], 0) }} L</span>@else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['losses_non_recoverable'] > 0)
                            <span style="color:#f43f5e">{{ number_format($row['losses_non_recoverable'], 0) }} L</span>
                            <div class="text-[10px] {{ $muted }}">{{ $cfmt($row['losses_non_recoverable_value']) }}</div>
                        @else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['losses_recoverable'] > 0)
                            <span style="color:#fb923c">{{ number_format($row['losses_recoverable'], 0) }} L</span>
                            <div class="text-[10px] {{ $muted }}">{{ $cfmt($row['losses_recoverable_value']) }}</div>
                        @else<span class="{{ $muted }}">—</span>@endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="{{ $surface2 }} border-t {{ $border }}">
                    <td class="px-4 py-3 font-bold text-[10px] uppercase tracking-wide {{ $muted }}">Total</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#f59e0b">{{ number_format($pipelineTotals['at_shipper'], 0) }} L</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#0ea5e9">{{ number_format($pipelineTotals['in_transit'], 0) }} L</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#10b981">{{ number_format($pipelineTotals['in_depots'], 0) }} L</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#a855f7">{{ number_format($pipelineTotals['sold'], 0) }} L</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#f43f5e">{{ number_format($pipelineTotals['losses_non_recoverable'], 0) }} L</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#fb923c">{{ number_format($pipelineTotals['losses_recoverable'], 0) }} L</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-8 text-center {{ $muted }} text-sm">
        No stock data found. Confirm and receive purchases to see the pipeline.
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 2: DEPOT BREAKDOWN (current)                              --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
@if($depotBreakdown->count() > 0)
<div class="mb-6">
    <h2 class="text-xs font-semibold uppercase tracking-widest {{ $muted }} mb-3">Current Depot Stock</h2>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="{{ $surface2 }} border-b {{ $border }}">
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Depot</th>
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Product</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Qty (L)</th>
                </tr>
            </thead>
            <tbody>
                @php $depotGroups = $depotBreakdown->groupBy('depot_name'); @endphp
                @foreach($depotGroups as $depotName => $rows)
                    @foreach($rows as $i => $row)
                    <tr class="border-b {{ $border }} last:border-0 hover:{{ $surface2 }} transition">
                        @if($i === 0)
                        <td class="px-4 py-3 font-semibold {{ $fg }}" rowspan="{{ count($rows) }}">{{ $depotName }}</td>
                        @endif
                        <td class="px-4 py-3 {{ $muted }}">{{ $products[$row->product_id] ?? "Product #$row->product_id" }}</td>
                        <td class="px-4 py-3 text-right font-semibold" style="color:#10b981">{{ number_format($row->qty, 0) }} L</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr class="{{ $surface2 }} border-t {{ $border }}">
                    <td colspan="2" class="px-4 py-3 font-bold text-[10px] uppercase tracking-wide {{ $muted }}">Total in Depots</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#10b981">{{ number_format($pipelineTotals['in_depots'], 0) }} L</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 3: PERIOD STOCK MOVEMENT (with $ values)                  --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h2 class="text-xs font-semibold uppercase tracking-widest {{ $muted }} mb-1">Period Inventory Movement</h2>
    <p class="text-xs {{ $muted }} mb-3">
        Opening &amp; closing inventory (qty and value) based on actual depot receipts and issues
        from <strong class="{{ $fg }}">{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</strong>
        to <strong class="{{ $fg }}">{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong>.
    </p>

    @if(count($movementRows) > 0)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="{{ $surface2 }} border-b {{ $border }}">
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Product</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Opening</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#10b981">+ Receipts</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#a855f7">− Dispatched</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-[10px]" style="color:#f43f5e">− Losses</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Closing</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movementRows as $row)
                <tr class="border-b {{ $border }} last:border-0 hover:{{ $surface2 }} transition">
                    <td class="px-4 py-3 font-semibold {{ $fg }}">{{ $row['product'] }}</td>
                    <td class="px-4 py-3 text-right {{ $muted }}">
                        {{ number_format($row['opening'], 0) }} L
                        <div class="text-[10px]">{{ $cfmt($row['opening_value']) }}</div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['receipts'] > 0)
                            <span style="color:#10b981">+{{ number_format($row['receipts'], 0) }} L</span>
                            <div class="text-[10px] {{ $muted }}">{{ $cfmt($row['receipts_value']) }}</div>
                        @else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['dispatched'] > 0)
                            <span style="color:#a855f7">−{{ number_format($row['dispatched'], 0) }} L</span>
                            <div class="text-[10px] {{ $muted }}">{{ $cfmt($row['dispatched_value']) }}</div>
                        @else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($row['losses'] > 0)
                            <span style="color:#f43f5e">−{{ number_format($row['losses'], 0) }} L</span>
                            <div class="text-[10px] {{ $muted }}">{{ $cfmt($row['losses_value']) }}</div>
                            <div class="text-[9px] {{ $muted }} mt-0.5">
                                Non-recov: {{ number_format($row['non_recoverable_qty'], 0) }} L ({{ $cfmt($row['non_recoverable_value']) }})<br>
                                Recov: {{ number_format($row['recoverable_qty'], 0) }} L ({{ $cfmt($row['recoverable_value']) }})
                            </div>
                        @else<span class="{{ $muted }}">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @php $closingColor = $row['closing'] > 0 ? '#10b981' : ($row['closing'] < 0 ? '#f43f5e' : null); @endphp
                        <span class="font-bold {{ $closingColor ? '' : $muted }}" @if($closingColor) style="color:{{ $closingColor }}" @endif>
                            {{ number_format($row['closing'], 0) }} L
                        </span>
                        <div class="text-[10px] {{ $muted }}">{{ $cfmt($row['closing_value']) }}</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $totOpening      = collect($movementRows)->sum('opening');
                    $totOpeningValue = collect($movementRows)->sum('opening_value');
                    $totReceipts      = collect($movementRows)->sum('receipts');
                    $totReceiptsValue = collect($movementRows)->sum('receipts_value');
                    $totDispatched      = collect($movementRows)->sum('dispatched');
                    $totDispatchedValue = collect($movementRows)->sum('dispatched_value');
                    $totLosses      = collect($movementRows)->sum('losses');
                    $totLossesValue = collect($movementRows)->sum('losses_value');
                    $totClosing      = collect($movementRows)->sum('closing');
                    $totClosingValue = collect($movementRows)->sum('closing_value');
                @endphp
                <tr class="{{ $surface2 }} border-t {{ $border }}">
                    <td class="px-4 py-3 font-bold text-[10px] uppercase tracking-wide {{ $muted }}">Total</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px] {{ $muted }}">
                        {{ number_format($totOpening, 0) }} L
                        <div class="font-normal">{{ $cfmt($totOpeningValue) }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#10b981">
                        +{{ number_format($totReceipts, 0) }} L
                        <div class="font-normal {{ $muted }}">{{ $cfmt($totReceiptsValue) }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#a855f7">
                        −{{ number_format($totDispatched, 0) }} L
                        <div class="font-normal {{ $muted }}">{{ $cfmt($totDispatchedValue) }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#f43f5e">
                        −{{ number_format($totLosses, 0) }} L
                        <div class="font-normal {{ $muted }}">{{ $cfmt($totLossesValue) }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" @if($totClosing > 0) style="color:#10b981" @endif>
                        {{ number_format($totClosing, 0) }} L
                        <div class="font-normal {{ $muted }}">{{ $cfmt($totClosingValue) }}</div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <p class="text-[10px] {{ $muted }} mt-2">
        Note: Opening &amp; closing stock reflect depot receipts and issues only. Fuel still in transit or at shipper does not affect these figures until it is physically received into a depot.
        Values use each movement's recorded weighted-average unit cost.
    </p>
    @else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-8 text-center {{ $muted }} text-sm">
        No inventory movements found for this period.
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- SECTION 4: PURCHASES RECEIVED WITHIN THE PERIOD                   --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}
<div class="mb-6">
    <h2 class="text-xs font-semibold uppercase tracking-widest {{ $muted }} mb-1">Purchases Received This Period</h2>
    <p class="text-xs {{ $muted }} mb-3">Individual purchase receipts that make up the "+ Receipts" figure above.</p>

    @if($purchaseBreakdown->count() > 0)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="{{ $surface2 }} border-b {{ $border }}">
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Date</th>
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Reference</th>
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Type</th>
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Supplier</th>
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Product</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Qty</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Unit Cost</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseBreakdown as $row)
                <tr class="border-b {{ $border }} last:border-0 hover:{{ $surface2 }} transition">
                    <td class="px-4 py-3 {{ $muted }} whitespace-nowrap">{{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}</td>
                    <td class="px-4 py-3 {{ $fg }} font-medium">
                        @if($row->purchase_id)
                            <a href="{{ route('purchases.show', $row->purchase_id) }}" class="hover:underline">{{ $row->reference ?? "PO #$row->purchase_id" }}</a>
                        @else
                            {{ $row->reference ?? '—' }}
                        @endif
                    </td>
                    <td class="px-4 py-3 {{ $muted }} capitalize">{{ str_replace('_', ' ', $row->purchase_type ?? '—') }}</td>
                    <td class="px-4 py-3 {{ $muted }}">{{ $row->supplier_name ?? '—' }}</td>
                    <td class="px-4 py-3 {{ $fg }}">{{ $row->product_name }}</td>
                    <td class="px-4 py-3 text-right {{ $fg }}">{{ number_format($row->qty, 0) }} L</td>
                    <td class="px-4 py-3 text-right {{ $muted }}">{{ $cfmt($row->avg_unit_cost) }}</td>
                    <td class="px-4 py-3 text-right font-semibold" style="color:#10b981">{{ $cfmt($row->value) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="{{ $surface2 }} border-t {{ $border }}">
                    <td colspan="5" class="px-4 py-3 font-bold text-[10px] uppercase tracking-wide {{ $muted }}">Total</td>
                    <td class="px-4 py-3 text-right font-bold text-[10px] {{ $fg }}">{{ number_format($purchaseBreakdown->sum('qty'), 0) }} L</td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 text-right font-bold text-[10px]" style="color:#10b981">{{ $cfmt($purchaseBreakdown->sum('value')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-8 text-center {{ $muted }} text-sm">
        No purchases were received in this period.
    </div>
    @endif
</div>

@endsection
