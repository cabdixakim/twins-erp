@php
    $title    = 'P&L by Batch';
    $subtitle = 'Gross margin analysis — purchase cost + landed costs vs. sales revenue per shipment.';
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>P&amp;L by Batch</span>
</div>

{{-- Filters --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Batch code</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search batch…"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 w-40 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <button type="submit" class="{{ $btnPrimary }}">Filter</button>
        @if(request()->hasAny(['from','to','search']))
            <a href="{{ route('reports.pl') }}" class="{{ $btnGhost }}">Clear</a>
        @endif
    </form>
</div>

{{-- Summary totals --}}
@if($batches->total() > 0)
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Revenue</div>
        <div class="text-lg font-bold {{ $fg }}">{{ number_format($totals['revenue'], 0) }}</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">COGS + Landed</div>
        <div class="text-lg font-bold {{ $fg }}">{{ number_format($totals['cogs'] + $totals['landed'], 0) }}</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4" style="{{ $totals['gross_margin'] >= 0 ? 'border-color:rgba(16,185,129,.3)' : 'border-color:rgba(239,68,68,.3)' }}">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Gross Margin</div>
        <div class="text-lg font-bold {{ $totals['gross_margin'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
            {{ number_format($totals['gross_margin'], 0) }}
        </div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4" style="{{ ($totals['margin_pct'] ?? 0) >= 0 ? 'border-color:rgba(16,185,129,.3)' : 'border-color:rgba(239,68,68,.3)' }}">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Margin %</div>
        <div class="text-lg font-bold {{ ($totals['margin_pct'] ?? 0) >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
            {{ isset($totals['margin_pct']) ? $totals['margin_pct'] . '%' : '—' }}
        </div>
    </div>
</div>
@endif

{{-- Table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b {{ $border }} {{ $surface2 }}">
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Batch</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Product</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Date</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-20">Qty sold</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Revenue</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">COGS</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Landed</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Margin</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-16">%</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[color:var(--tw-border)]">
            @forelse($batches as $batch)
            @php
                $marginColor = $batch->_gross_margin >= 0 ? 'text-emerald-400' : 'text-rose-400';
                $pctColor    = ($batch->_margin_pct ?? 0) >= 0 ? 'text-emerald-400' : 'text-rose-400';
            @endphp
            <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                <td class="px-4 py-3">
                    <div class="font-semibold {{ $fg }}">{{ $batch->code }}</div>
                    @if($batch->supplier)
                    <div class="text-[10px] {{ $muted }}">{{ $batch->supplier->name }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 {{ $muted }}">{{ $batch->product?->name ?? '—' }}</td>
                <td class="px-4 py-3 {{ $muted }}">{{ $batch->purchased_at?->format('d M Y') ?? '—' }}</td>
                <td class="px-4 py-3 text-right {{ $fg }}">{{ number_format($batch->_qty_sold, 0) }} L</td>
                <td class="px-4 py-3 text-right {{ $fg }}">{{ $batch->_revenue > 0 ? number_format($batch->_revenue, 0) : '—' }}</td>
                <td class="px-4 py-3 text-right {{ $muted }}">{{ $batch->_cogs > 0 ? number_format($batch->_cogs, 0) : '—' }}</td>
                <td class="px-4 py-3 text-right {{ $muted }}">{{ $batch->_landed > 0 ? number_format($batch->_landed, 0) : '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold {{ $marginColor }}">
                    {{ $batch->_revenue > 0 ? number_format($batch->_gross_margin, 0) : '—' }}
                </td>
                <td class="px-4 py-3 text-right font-bold {{ $pctColor }}">
                    {{ $batch->_margin_pct !== null ? $batch->_margin_pct . '%' : '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-12 text-center {{ $muted }}">No batches found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($batches->hasPages())
    <div class="mt-4">{{ $batches->links() }}</div>
@endif

@endsection
