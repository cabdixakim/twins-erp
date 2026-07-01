@php
    $title    = 'Profit & Loss';
    $subtitle = 'Revenue, cost of sales, and expenses for the selected period.';
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";

    $fmt = fn($n) => number_format(abs($n), 0);
    $fmtSigned = function($n) use ($fg) {
        if ($n >= 0) return '<span class="text-emerald-400 font-bold">'.number_format($n, 0).'</span>';
        return '<span class="text-rose-400 font-bold">('.number_format(abs($n), 0).')</span>';
    };
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')
<div class="max-w-7xl mx-auto">

{{-- Breadcrumb --}}
<div class="no-print flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Profit &amp; Loss</span>
</div>

{{-- Date filter --}}
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
        <a href="{{ route('reports.pl.export', ['from' => $from, 'to' => $to]) }}"
           class="{{ $btnGhost }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
        <span class="{{ $muted }} text-xs self-center">
            {{ \Carbon\Carbon::parse($from)->format('d M Y') }} → {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </span>
    </form>
</div>

{{-- P&L Statement --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 print-two-col">

    {{-- Statement column --}}
    <div class="lg:col-span-2 space-y-3">

        {{-- INCOME --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Income</span>
                <span class="text-[10px] {{ $muted }}">{{ number_format($qtySold, 0) }} L sold</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm {{ $fg }}">Fuel Sales</span>
                    <span class="text-sm font-semibold {{ $fg }}">{{ $fmt($revenue) }}</span>
                </div>
                <div class="flex items-center justify-between px-5 py-3 {{ $surface2 }}">
                    <span class="text-xs font-bold uppercase tracking-wide {{ $muted }}">Total Revenue</span>
                    <span class="text-sm font-bold {{ $fg }}">{{ $fmt($revenue) }}</span>
                </div>
            </div>
        </div>

        {{-- COST OF SALES --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }} flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Cost of Sales</span>
                <span class="text-sm font-bold text-rose-400">({{ $fmt($cogs) }})</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @forelse($cogsBreakdown as $line)
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm {{ $fg }}">{{ $line['label'] }}</span>
                    <span class="text-sm {{ $muted }}">{{ $fmt($line['amount']) }}</span>
                </div>
                @empty
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No COGS in this period.</div>
                @endforelse
                <div class="flex items-center justify-between px-5 py-3 {{ $surface2 }}">
                    <span class="text-xs font-bold uppercase tracking-wide {{ $muted }}">Total Cost of Sales</span>
                    <span class="text-sm font-bold {{ $fg }}">{{ $fmt($cogs) }}</span>
                </div>
            </div>
        </div>

        {{-- GROSS PROFIT --}}
        <div class="rounded-2xl border overflow-hidden {{ $grossProfit >= 0 ? 'border-emerald-500/30' : 'border-rose-500/30' }}">
            <div class="flex items-center justify-between px-5 py-4 {{ $grossProfit >= 0 ? 'bg-emerald-500/5' : 'bg-rose-500/5' }}">
                <div>
                    <div class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Gross Profit</div>
                    @if($grossMarginPct !== null)
                    <div class="text-[10px] {{ $muted }} mt-0.5">{{ $grossMarginPct }}% margin</div>
                    @endif
                </div>
                <div class="text-xl font-bold">
                    {!! $fmtSigned($grossProfit) !!}
                </div>
            </div>
        </div>

        {{-- OPERATING EXPENSES --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-3 border-b {{ $border }} {{ $surface2 }}">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Operating Expenses</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                {{-- Transporter freight --}}
                @if($transporterCharges > 0)
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm {{ $fg }}">Transport & Freight</span>
                    <span class="text-sm {{ $muted }}">{{ $fmt($transporterCharges) }}</span>
                </div>
                @endif

                {{-- Depot charges --}}
                @if($depotCharges > 0)
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm {{ $fg }}">Depot Charges</span>
                    <span class="text-sm {{ $muted }}">{{ $fmt($depotCharges) }}</span>
                </div>
                @endif

                {{-- Petty cash --}}
                @if($pettyCash > 0)
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-sm {{ $fg }}">Petty Cash Expenses</span>
                    <span class="text-sm {{ $muted }}">{{ $fmt(abs($pettyCash)) }}</span>
                </div>
                @endif

                {{-- Empty state --}}
                @if($transporterCharges == 0 && $depotCharges == 0 && $pettyCash == 0)
                <div class="px-5 py-4 text-sm {{ $muted }} italic">No operating expenses recorded in this period.</div>
                @endif

                <div class="flex items-center justify-between px-5 py-3 {{ $surface2 }}">
                    <span class="text-xs font-bold uppercase tracking-wide {{ $muted }}">Total Operating Expenses</span>
                    <span class="text-sm font-bold {{ $fg }}">{{ $fmt($totalExpenses) }}</span>
                </div>
            </div>
        </div>

        {{-- NET PROFIT --}}
        @php $netProfitView = $netProfit; @endphp
        <div class="rounded-2xl border overflow-hidden {{ $netProfitView >= 0 ? 'border-emerald-500/40' : 'border-rose-500/40' }}">
            <div class="flex items-center justify-between px-5 py-5 {{ $netProfitView >= 0 ? 'bg-emerald-500/8' : 'bg-rose-500/8' }}">
                <div>
                    <div class="text-sm font-bold uppercase tracking-widest {{ $muted }}">Net Profit</div>
                    @if($netMarginPct !== null)
                    <div class="text-[10px] {{ $muted }} mt-0.5">{{ $netMarginPct }}% of revenue</div>
                    @endif
                </div>
                <div class="text-2xl font-bold">
                    {!! $fmtSigned($netProfitView) !!}
                </div>
            </div>
        </div>

    </div>{{-- end statement column --}}

    {{-- Right sidebar: summary KPIs + by-product --}}
    <div class="space-y-4">

        {{-- Quick numbers --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 space-y-4">
            <div class="text-xs font-bold uppercase tracking-widest {{ $muted }}">Summary</div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Revenue</span>
                    <span class="text-sm font-semibold {{ $fg }}">{{ $fmt($revenue) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Cost of Sales</span>
                    <span class="text-sm {{ $fg }}">{{ $fmt($cogs) }}</span>
                </div>
                <div class="border-t {{ $border }} pt-3 flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Gross Profit</span>
                    <span class="text-sm font-semibold">
                        {!! $fmtSigned($grossProfit) !!}
                        @if($grossMarginPct !== null)
                        <span class="text-[10px] font-normal {{ $muted }} ml-1">{{ $grossMarginPct }}%</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs {{ $muted }}">Operating Expenses</span>
                    <span class="text-sm {{ $fg }}">{{ $fmt($transporterCharges + $depotCharges + $pettyCash) }}</span>
                </div>
                <div class="border-t {{ $border }} pt-3 flex justify-between items-center">
                    <span class="text-xs font-bold {{ $muted }}">Net Profit</span>
                    <span class="text-base font-bold">
                        {!! $fmtSigned($netProfitView) !!}
                    </span>
                </div>
            </div>
        </div>

        {{-- By product --}}
        @if($byProduct->isNotEmpty())
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-4 py-3 border-b {{ $border }} {{ $surface2 }}">
                <span class="text-xs font-bold uppercase tracking-widest {{ $muted }}">By Product</span>
            </div>
            <div class="divide-y divide-[color:var(--tw-border)]">
                @foreach($byProduct as $row)
                @php
                    $rowMargin = (float)$row->margin;
                    $rowMarginPct = (float)$row->revenue > 0
                        ? round($rowMargin / (float)$row->revenue * 100, 1)
                        : null;
                @endphp
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold {{ $fg }}">{{ $row->product_name }}</span>
                        <span class="text-xs {{ $rowMargin >= 0 ? 'text-emerald-400' : 'text-rose-400' }} font-semibold">
                            {!! $fmtSigned($rowMargin) !!}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] {{ $muted }}">
                        <span>{{ number_format((float)$row->qty, 0) }} L &middot; Rev {{ $fmt((float)$row->revenue) }}</span>
                        @if($rowMarginPct !== null)
                        <span>{{ $rowMarginPct }}%</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Print button --}}
        <button onclick="window.print()"
                class="w-full {{ $btnGhost }} justify-center">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4H8v4a1 1 0 001 1z"/></svg>
            Print Statement
        </button>

    </div>
</div>

</div>{{-- max-w-7xl --}}

@endsection
