@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-btn)] text-[color:var(--tw-fg)] hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";

    $fmt = fn($n) => number_format(abs($n), 2);
    $fmtSigned = function($n) {
        if ($n >= 0) return '<span class="text-emerald-400 font-bold">'.number_format($n, 2).'</span>';
        return '<span class="text-rose-400 font-bold">('.number_format(abs($n), 2).')</span>';
    };

    $volUnit = $volumeUnit ?? 'L';

    // Build by-product summary for sidebar (operational mode only)
    $byProductMap = [];
    if (!$useGL) {
        foreach ($revenueRows ?? [] as $row) {
            $byProductMap[$row->product_name] = ['revenue' => (float)$row->revenue, 'qty' => (float)$row->qty, 'cogs' => 0];
        }
        foreach ($cogsRows ?? [] as $row) {
            if (!isset($byProductMap[$row->product_name])) {
                $byProductMap[$row->product_name] = ['revenue' => 0, 'qty' => (float)$row->qty, 'cogs' => 0];
            }
            $byProductMap[$row->product_name]['cogs'] = (float)$row->cogs;
        }
    }
@endphp

@extends('layouts.app')
@section('title', 'Profit & Loss')
@section('subtitle', $useGL ? 'General Ledger mode — journal_entry_lines.' : 'Operational mode — transactional data.')

@section('content')

{{-- Breadcrumb --}}
<div class="no-print flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('accounting.index') }}" class="hover:underline">Accounting</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Profit &amp; Loss</span>
</div>

{{-- Date filter + controls --}}
<div class="no-print rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">From</label>
            <input type="date" name="from" value="{{ $from }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">To</label>
            <input type="date" name="to" value="{{ $to }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <button type="submit" class="{{ $btnPrimary }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            Filter
        </button>
        <a href="{{ route('accounting.pl.export', request()->query()) }}" class="{{ $btnGhost }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <span class="{{ $muted }} text-xs self-center">
            {{ \Carbon\Carbon::parse($from)->format('d M Y') }} → {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </span>
        {{-- Mode badge --}}
        <span class="ml-auto inline-flex items-center gap-1.5 text-[11px] font-semibold px-3 py-1.5 rounded-full
            {{ $useGL ? 'text-emerald-400' : 'text-amber-400' }}"
            style="background:{{ $useGL ? 'rgba(16,185,129,.12)' : 'rgba(251,191,36,.12)' }}">
            @if($useGL)
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
            GL Mode
            @else
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01"/><circle cx="12" cy="12" r="10"/></svg>
            Operational Mode
            @endif
        </span>
    </form>
</div>

{{-- 3-col layout: 2/3 statement + 1/3 sidebar --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 print-two-col">

    {{-- ── STATEMENT COLUMN ── --}}
    <div class="lg:col-span-2 space-y-3">

@if($useGL)
{{-- ══ GL MODE ══ --}}

        {{-- Revenue --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Revenue</span>
                <span class="text-sm font-bold text-emerald-400">{{ $fmt($totalRevenue) }}</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @forelse($glRevenue as $row)
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="font-mono text-[10px] {{ $muted }} w-16 shrink-0">{{ $row->code }}</span>
                    <span class="text-sm {{ $fg }} flex-1">{{ $row->account_name }}</span>
                    <span class="text-sm font-semibold tabular-nums {{ $row->net >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $fmt($row->net) }}</span>
                </div>
                @empty
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No revenue journal entries in this period.</div>
                @endforelse
            </div>
        </div>

        {{-- COGS --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Cost of Goods Sold</span>
                <span class="text-sm font-bold text-rose-400">({{ $fmt($totalCogs) }})</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @forelse($glCogs as $row)
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="font-mono text-[10px] {{ $muted }} w-16 shrink-0">{{ $row->code }}</span>
                    <span class="text-sm {{ $fg }} flex-1">{{ $row->account_name }}</span>
                    <span class="text-sm font-semibold tabular-nums {{ $fg }}">({{ $fmt($row->net) }})</span>
                </div>
                @empty
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No COGS journal entries in this period.</div>
                @endforelse
            </div>
        </div>

        {{-- Gross Profit --}}
        <div class="rounded-2xl border overflow-hidden {{ $grossProfit >= 0 ? 'border-emerald-500/30' : 'border-rose-500/30' }}">
            <div class="flex items-center justify-between px-5 py-4 {{ $grossProfit >= 0 ? 'bg-emerald-500/5' : 'bg-rose-500/5' }}">
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Gross Profit</div>
                    @if($grossMargin !== null)<div class="text-[10px] {{ $muted }} mt-0.5">{{ $grossMargin }}% margin</div>@endif
                </div>
                <div class="text-xl font-bold">{!! $fmtSigned($grossProfit) !!}</div>
            </div>
        </div>

        {{-- Operating Expenses --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Operating Expenses</span>
                <span class="text-sm font-bold text-rose-400">({{ $fmt($totalOpex) }})</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @forelse($glOpex as $row)
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="font-mono text-[10px] {{ $muted }} w-16 shrink-0">{{ $row->code }}</span>
                    <span class="text-sm {{ $fg }} flex-1">{{ $row->account_name }}</span>
                    <span class="text-sm font-semibold tabular-nums {{ $fg }}">({{ $fmt($row->net) }})</span>
                </div>
                @empty
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No operating expense journal entries in this period.</div>
                @endforelse
            </div>
        </div>

@else
{{-- ══ OPERATIONAL MODE ══ --}}

        {{-- Revenue --}}
        @php $totalQtySold = ($revenueRows ?? collect())->sum('qty'); @endphp
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Income</span>
                <span class="text-[10px] {{ $muted }}">{{ number_format($totalQtySold, 0) }} {{ $volUnit }} sold</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @foreach($revenueRows ?? [] as $row)
                <div class="flex items-center justify-between px-5 py-2.5">
                    <span class="text-sm {{ $fg }}">{{ $row->product_name }}</span>
                    <div class="text-right">
                        <div class="text-sm font-semibold tabular-nums {{ $fg }}">{{ $fmt($row->revenue) }}</div>
                        <div class="text-[10px] {{ $muted }}">{{ number_format($row->qty, 0) }} {{ $volUnit }}</div>
                    </div>
                </div>
                @endforeach
                @foreach($journalRevenue ?? [] as $row)
                <div class="flex items-center justify-between px-5 py-2.5">
                    <span class="text-sm {{ $fg }}">
                        {{ $row->account_name }}
                        <span class="ml-1 text-[10px] px-1 rounded" style="background:rgba(99,102,241,.12);color:#6366f1">journal adj.</span>
                    </span>
                    <span class="text-sm font-semibold tabular-nums {{ $row->net >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $fmt($row->net) }}</span>
                </div>
                @endforeach
                @if(($revenueRows ?? collect())->isEmpty() && ($journalRevenue ?? collect())->isEmpty())
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No posted sales in this period.</div>
                @endif
                <div class="flex items-center justify-between px-5 py-2.5 {{ $surface2 }}">
                    <span class="text-xs font-bold uppercase tracking-wide {{ $muted }}">Total Revenue</span>
                    <span class="text-sm font-bold {{ $fg }}">{{ $fmt($totalRevenue + ($totalJournalRevenue ?? 0)) }}</span>
                </div>
            </div>
        </div>

        {{-- Cost of Sales --}}
        @php $totalCostOfSales = $totalCogs + $totalLanded; @endphp
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Cost of Sales</span>
                <span class="text-sm font-bold text-rose-400">({{ $fmt($totalCostOfSales) }})</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @if(($cogsRows ?? collect())->isNotEmpty())
                <div class="px-5 py-1.5" style="background:var(--tw-surface-2)">
                    <span class="text-[10px] font-semibold uppercase tracking-wider {{ $muted }}">Purchase cost by product</span>
                </div>
                @foreach($cogsRows ?? [] as $row)
                <div class="flex items-center justify-between px-5 py-2.5">
                    <span class="text-sm {{ $fg }}">{{ $row->product_name }}</span>
                    <div class="text-right">
                        <div class="text-sm font-semibold tabular-nums {{ $fg }}">({{ $fmt($row->cogs) }})</div>
                        <div class="text-[10px] {{ $muted }}">{{ number_format($row->qty, 0) }} {{ $volUnit }}</div>
                    </div>
                </div>
                @endforeach
                @endif
                @if(($landedCosts ?? collect())->isNotEmpty())
                <div class="px-5 py-1.5" style="background:var(--tw-surface-2)">
                    <span class="text-[10px] font-semibold uppercase tracking-wider {{ $muted }}">Landed costs (proportional to qty sold)</span>
                </div>
                @foreach($landedCosts ?? [] as $row)
                <div class="flex items-center justify-between px-5 py-2.5">
                    <span class="text-sm {{ $fg }}">{{ ucfirst(str_replace('_', ' ', $row->category)) }}</span>
                    <span class="text-sm font-semibold tabular-nums {{ $fg }}">({{ $fmt($row->total) }})</span>
                </div>
                @endforeach
                @endif
                @if(($cogsRows ?? collect())->isEmpty() && ($landedCosts ?? collect())->isEmpty())
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No COGS in this period.</div>
                @endif
                <div class="flex items-center justify-between px-5 py-2.5 {{ $surface2 }}">
                    <span class="text-xs font-bold uppercase tracking-wide {{ $muted }}">Total Cost of Sales</span>
                    <span class="text-sm font-bold {{ $fg }}">{{ $fmt($totalCostOfSales) }}</span>
                </div>
            </div>
        </div>

        {{-- Gross Profit --}}
        <div class="rounded-2xl border overflow-hidden {{ $grossProfit >= 0 ? 'border-emerald-500/30' : 'border-rose-500/30' }}">
            <div class="flex items-center justify-between px-5 py-4 {{ $grossProfit >= 0 ? 'bg-emerald-500/5' : 'bg-rose-500/5' }}">
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Gross Profit</div>
                    @if($grossMargin !== null)<div class="text-[10px] {{ $muted }} mt-0.5">{{ $grossMargin }}% margin</div>@endif
                </div>
                <div class="text-xl font-bold">{!! $fmtSigned($grossProfit) !!}</div>
            </div>
        </div>

        {{-- Operating Expenses --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Operating Expenses</span>
                <span class="text-sm font-bold text-rose-400">({{ $fmt($totalOpex ?? 0) }})</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @if(($transporterCharges ?? 0) > 0)
                <div class="flex items-center justify-between px-5 py-2.5">
                    <span class="text-sm {{ $fg }}">Transport &amp; Freight</span>
                    <span class="text-sm tabular-nums {{ $fg }}">({{ $fmt($transporterCharges) }})</span>
                </div>
                @endif
                @if(($depotCharges ?? 0) > 0)
                <div class="flex items-center justify-between px-5 py-2.5">
                    <span class="text-sm {{ $fg }}">Depot Storage &amp; Handling</span>
                    <span class="text-sm tabular-nums {{ $fg }}">({{ $fmt($depotCharges) }})</span>
                </div>
                @endif
                @foreach($pettyCashExpenses ?? [] as $row)
                <div class="flex items-center justify-between px-5 py-2.5">
                    <span class="text-sm {{ $fg }}">{{ ucfirst(str_replace('_', ' ', $row->category)) }} <span class="text-[10px] {{ $muted }}">(petty cash)</span></span>
                    <span class="text-sm tabular-nums {{ $fg }}">({{ $fmt($row->total) }})</span>
                </div>
                @endforeach
                @if(($totalOpex ?? 0) == 0)
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No operating expenses in this period.</div>
                @endif
                <div class="flex items-center justify-between px-5 py-2.5 {{ $surface2 }}">
                    <span class="text-xs font-bold uppercase tracking-wide {{ $muted }}">Total Operating Expenses</span>
                    <span class="text-sm font-bold {{ $fg }}">{{ $fmt($totalOpex ?? 0) }}</span>
                </div>
            </div>
        </div>

        {{-- Journal Adjustments --}}
        @if(($journalExpenses ?? collect())->isNotEmpty() || ($totalJournalRevenue ?? 0) != 0)
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Journal Adjustments</span>
                    <div class="text-[10px] {{ $muted }} mt-0.5">Manual entries — depreciation, accruals, salaries, etc.</div>
                </div>
                <span class="text-sm font-bold {{ (($totalJournalRevenue ?? 0) - ($totalJournalExpenses ?? 0)) >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                    {!! $fmtSigned(($totalJournalRevenue ?? 0) - ($totalJournalExpenses ?? 0)) !!}
                </span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @foreach($journalExpenses ?? [] as $row)
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="font-mono text-[10px] {{ $muted }} w-16 shrink-0">{{ $row->code }}</span>
                    <span class="text-sm {{ $fg }} flex-1">{{ $row->account_name }}</span>
                    <span class="text-sm font-semibold tabular-nums {{ $fg }}">({{ $fmt($row->net) }})</span>
                </div>
                @endforeach
                @if(($journalExpenses ?? collect())->isEmpty())
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No manual expense entries in this period.</div>
                @endif
            </div>
        </div>
        @endif

@endif

        {{-- Net Profit -- shared --}}
        <div class="rounded-2xl border overflow-hidden {{ $netProfit >= 0 ? 'border-emerald-500/40' : 'border-rose-500/40' }}">
            <div class="flex items-center justify-between px-5 py-5 {{ $netProfit >= 0 ? 'bg-emerald-500/8' : 'bg-rose-500/8' }}">
                <div>
                    <div class="text-sm font-bold uppercase tracking-widest {{ $muted }}">Net Profit</div>
                    @if($netMargin !== null)<div class="text-[10px] {{ $muted }} mt-0.5">{{ $netMargin }}% of revenue</div>@endif
                    @if($useGL)<div class="text-[10px] {{ $muted }} mt-0.5">GL entries only — historical data before accounting enabled not included</div>@endif
                </div>
                <div class="text-2xl font-bold">{!! $fmtSigned($netProfit) !!}</div>
            </div>
        </div>

    </div>{{-- end statement column --}}

    {{-- ── SIDEBAR ── --}}
    <div class="space-y-4">

        {{-- Summary KPIs --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 space-y-4">
            <div class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Summary</div>
            <div class="space-y-2.5">
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Revenue</span>
                    <span class="text-sm font-semibold {{ $fg }}">{{ $fmt($totalRevenue + ($totalJournalRevenue ?? 0)) }}</span>
                </div>
                @if(!$useGL)
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Cost of Sales</span>
                    <span class="text-sm {{ $fg }}">{{ $fmt($totalCostOfSales ?? ($totalCogs + ($totalLanded ?? 0))) }}</span>
                </div>
                @else
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Cost of Sales</span>
                    <span class="text-sm {{ $fg }}">{{ $fmt($totalCogs) }}</span>
                </div>
                @endif
                <div class="border-t {{ $border }} pt-2.5 flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Gross Profit</span>
                    <span class="text-sm font-semibold">
                        {!! $fmtSigned($grossProfit) !!}
                        @if($grossMargin !== null)<span class="text-[10px] font-normal {{ $muted }} ml-1">{{ $grossMargin }}%</span>@endif
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Operating Expenses</span>
                    <span class="text-sm {{ $fg }}">{{ $fmt($totalOpex ?? 0) }}</span>
                </div>
                @if(($totalJournalRevenue ?? 0) != 0 || ($totalJournalExpenses ?? 0) != 0)
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Journal Adjustments</span>
                    <span class="text-sm {{ $fg }}">{!! $fmtSigned(($totalJournalRevenue ?? 0) - ($totalJournalExpenses ?? 0)) !!}</span>
                </div>
                @endif
                <div class="border-t {{ $border }} pt-2.5 flex justify-between items-center">
                    <span class="text-xs font-bold {{ $muted }}">Net Profit</span>
                    <span class="text-base font-bold">{!! $fmtSigned($netProfit) !!}</span>
                </div>
            </div>
        </div>

        {{-- By Product (operational only) --}}
        @if(!$useGL && count($byProductMap) > 0)
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-4 py-3 border-b {{ $border }} {{ $surface2 }}">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">By Product</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @foreach($byProductMap as $productName => $pRow)
                @php $margin = $pRow['revenue'] - $pRow['cogs']; $marginPct = $pRow['revenue'] > 0 ? round($margin / $pRow['revenue'] * 100, 1) : null; @endphp
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-xs font-semibold {{ $fg }}">{{ $productName }}</span>
                        <span class="text-xs font-semibold {{ $margin >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{!! $fmtSigned($margin) !!}</span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] {{ $muted }}">
                        <span>{{ number_format($pRow['qty'], 0) }} {{ $volUnit }} &middot; Rev {{ $fmt($pRow['revenue']) }}</span>
                        @if($marginPct !== null)<span>{{ $marginPct }}%</span>@endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Print --}}
        <button onclick="window.print()" class="w-full {{ $btnGhost }} justify-center">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4H8v4a1 1 0 001 1z"/></svg>
            Print Statement
        </button>

    </div>

</div>

@endsection
