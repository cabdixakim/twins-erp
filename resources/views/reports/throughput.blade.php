@php
    $title    = 'Volume Report';
    $subtitle = 'How many litres you bought and sold each month — with revenue totals.';
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $btnGhost = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";
    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";

    $labels   = json_encode(array_column($series, 'label'));
    $purchased = json_encode(array_column($series, 'purchased_qty'));
    $sold      = json_encode(array_column($series, 'sold_qty'));
    $revenues  = json_encode(array_column($series, 'revenue'));
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

{{-- Breadcrumb --}}
<div class="no-print flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Volume Report</span>
</div>

{{-- Period selector --}}
<div class="no-print flex items-center gap-2 mb-4 flex-wrap">
    <form method="GET" class="flex gap-2 items-center">
        <span class="text-xs {{ $muted }}">Show last</span>
        @foreach([3,6,12,24] as $m)
        <button type="submit" name="months" value="{{ $m }}"
            class="rounded-xl text-xs px-3 py-1.5 border transition
                   {{ $months == $m
                       ? 'border-emerald-500/50 bg-emerald-600 text-white font-semibold'
                       : "$border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)]" }}">
            {{ $m }}m
        </button>
        @endforeach
    </form>
    <a href="{{ route('reports.throughput.export', ['months' => $months]) }}"
       class="{{ $btnGhost }} ml-auto">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export CSV
    </a>
</div>

{{-- KPI bar --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 sm:col-span-1">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Purchased</div>
        <div class="text-lg font-bold {{ $fg }}">{{ number_format($totals['purchased_qty'], 0) }} L</div>
        <div class="text-[10px] {{ $muted }}">{{ $totals['purchased_count'] }} {{ $totals['purchased_count'] == 1 ? 'purchase' : 'purchases' }}</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Sold</div>
        <div class="text-lg font-bold" style="color:#10b981">{{ number_format($totals['sold_qty'], 0) }} L</div>
        <div class="text-[10px] {{ $muted }}">{{ $totals['sold_count'] }} sales</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Unsold</div>
        @php $remaining = $totals['purchased_qty'] - $totals['sold_qty']; @endphp
        <div class="text-lg font-bold" style="color:#0ea5e9">{{ number_format(max(0, $remaining), 0) }} L</div>
        <div class="text-[10px] {{ $muted }}">not yet sold</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Revenue</div>
        <div class="text-lg font-bold" style="color:#a855f7">{{ number_format($totals['revenue'], 0) }}</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Sell-through</div>
        @php $sellThru = $totals['purchased_qty'] > 0 ? round($totals['sold_qty'] / $totals['purchased_qty'] * 100, 1) : 0; @endphp
        <div class="text-lg font-bold {{ $fg }}">{{ $sellThru }}%</div>
        <div class="text-[10px] {{ $muted }}">of purchased vol.</div>
    </div>
</div>

{{-- Chart --}}
<div class="no-print rounded-2xl border {{ $border }} {{ $surface }} p-5 mb-4">
    <div class="text-xs font-semibold {{ $fg }} mb-4">Volume — Litres Purchased vs Sold</div>
    <div style="height:280px; position:relative">
        <canvas id="throughputChart"></canvas>
    </div>
</div>

{{-- Revenue chart --}}
<div class="no-print rounded-2xl border {{ $border }} {{ $surface }} p-5 mb-4">
    <div class="text-xs font-semibold {{ $fg }} mb-4">Revenue Trend</div>
    <div style="height:200px; position:relative">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

{{-- Monthly table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b {{ $border }} {{ $surface2 }}">
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Month</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Purchased (L)</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Orders</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Sold (L)</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Sales</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Revenue</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Sell-through</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[color:var(--tw-border)]">
            @foreach(array_reverse($series) as $row)
            @php
                $st = $row['purchased_qty'] > 0 ? round($row['sold_qty'] / $row['purchased_qty'] * 100, 1) : 0;
                $stColor = $st >= 80 ? '#10b981' : ($st >= 50 ? '#f59e0b' : '#94a3b8');
            @endphp
            <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                <td class="px-4 py-3 font-semibold {{ $fg }}">{{ $row['label'] }}</td>
                <td class="px-4 py-3 text-right {{ $fg }}">{{ number_format($row['purchased_qty'], 0) }}</td>
                <td class="px-4 py-3 text-right {{ $muted }}">{{ $row['purchased_count'] }}</td>
                <td class="px-4 py-3 text-right font-semibold" style="color:#10b981">{{ number_format($row['sold_qty'], 0) }}</td>
                <td class="px-4 py-3 text-right {{ $muted }}">{{ $row['sold_count'] }}</td>
                <td class="px-4 py-3 text-right {{ $fg }}">{{ $row['revenue'] > 0 ? number_format($row['revenue'], 0) : '—' }}</td>
                <td class="px-4 py-3 text-right font-semibold" style="color:{{ $stColor }}">
                    {{ $row['purchased_qty'] > 0 ? $st . '%' : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Print button --}}
<div class="no-print flex justify-end mt-4">
    <button onclick="window.print()"
            class="{{ $btnGhost }}">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4H8v4a1 1 0 001 1z"/></svg>
        Print
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isDark = document.documentElement.classList.contains('dark') || window.matchMedia('(prefers-color-scheme: dark)').matches;
const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
const textColor = isDark ? 'rgba(255,255,255,.4)' : 'rgba(0,0,0,.4)';

Chart.defaults.font.size = 11;
Chart.defaults.color     = textColor;

const labels    = {!! $labels !!};
const purchased = {!! $purchased !!};
const sold      = {!! $sold !!};
const revenues  = {!! $revenues !!};

// Volume chart
new Chart(document.getElementById('throughputChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            {
                label: 'Purchased (L)',
                data: purchased,
                backgroundColor: 'rgba(14,165,233,.25)',
                borderColor: 'rgba(14,165,233,.8)',
                borderWidth: 1.5,
                borderRadius: 4,
            },
            {
                label: 'Sold (L)',
                data: sold,
                backgroundColor: 'rgba(16,185,129,.25)',
                borderColor: 'rgba(16,185,129,.8)',
                borderWidth: 1.5,
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { boxWidth: 12, padding: 16 } } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: textColor } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, callback: v => (v/1000).toFixed(0)+'k L' } },
        }
    }
});

// Revenue chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Revenue',
            data: revenues,
            borderColor: 'rgba(168,85,247,.8)',
            backgroundColor: 'rgba(168,85,247,.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: 'rgba(168,85,247,.9)',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: textColor } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, callback: v => v.toLocaleString() } },
        }
    }
});
</script>

@endsection
