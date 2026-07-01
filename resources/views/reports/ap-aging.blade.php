@php
    $bucketKeys   = ['current', '31_60', '61_90', '90_plus'];
    $bucketLabels = ['current' => 'Current', '31_60' => '31–60d', '61_90' => '61–90d', '90_plus' => '90+d'];
    $bucketColors = ['current' => '#10b981', '31_60' => '#f59e0b', '61_90' => '#f97316', '90_plus' => '#ef4444'];
    $bv = fn($row, $b) => $row->bucket === $b ? $row->balance : 0;

    $border     = 'border-[color:var(--tw-border)]';
    $surface    = 'bg-[color:var(--tw-surface)]';
    $surface2   = 'bg-[color:var(--tw-surface-2)]';
    $bg         = 'bg-[color:var(--tw-bg)]';
    $fg         = 'text-[color:var(--tw-fg)]';
    $muted      = 'text-[color:var(--tw-muted)]';
    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border border-[color:var(--tw-border)] bg-[color:var(--tw-btn)] text-[color:var(--tw-fg)] hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";
@endphp

@extends('layouts.app')
@section('title', 'Payables Aging')
@section('subtitle', 'Outstanding amounts owed to suppliers, depots, and transporters — grouped by how old they are.')

@section('content')

{{-- Breadcrumb --}}
<div class="no-print flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Payables Aging</span>
</div>

{{-- Filters card --}}
<div class="no-print rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">As of date</label>
            <input type="date" name="as_of" value="{{ $asOf }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <button type="submit" class="{{ $btnPrimary }}">Run</button>
        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('reports.ap-aging.export', request()->query()) }}" class="{{ $btnGhost }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </div>
    </form>
</div>

{{-- Summary strip --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    @foreach([
        ['label' => 'Suppliers',    'value' => $grandTotals['supplier'],    'color' => '#a855f7'],
        ['label' => 'Transporters', 'value' => $grandTotals['transporter'], 'color' => '#0ea5e9'],
        ['label' => 'Depots',       'value' => $grandTotals['depot'],       'color' => '#f59e0b'],
        ['label' => 'Total AP',     'value' => $grandTotals['total'],       'color' => '#ef4444'],
    ] as $card)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 text-center">
        <div class="text-[9px] uppercase tracking-wide {{ $muted }} mb-1">{{ $card['label'] }}</div>
        <div class="text-base font-bold tabular-nums" style="color:{{ $card['color'] }}">
            {{ number_format($card['value'], 0) }}
        </div>
    </div>
    @endforeach
</div>

{{-- ── Suppliers ─────────────────────────────────────────────────── --}}
<div class="mb-6">
    <h2 class="text-xs font-bold uppercase tracking-wider {{ $muted }} mb-2 px-1">Supplier Payables</h2>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b {{ $border }} {{ $surface2 }}">
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Supplier</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#10b981">Current</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#f59e0b">31–60d</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#f97316">61–90d</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#ef4444">90+d</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} text-[10px] uppercase tracking-wide w-28">Total</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} text-[10px] uppercase tracking-wide w-16">Age</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[color:var(--tw-border)]">
                @forelse($supplierRows as $row)
                <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                    <td class="px-4 py-3 font-semibold {{ $fg }}">{{ $row->name }}</td>
                    @foreach($bucketKeys as $b)
                    @php $v = $bv($row, $b); @endphp
                    <td class="px-4 py-3 text-right tabular-nums {{ $v > 0 ? 'font-semibold' : $muted }}"
                        style="{{ $v > 0 ? 'color:'.$bucketColors[$b] : '' }}">
                        {{ $v > 0 ? number_format($v, 2) : '—' }}
                    </td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-bold tabular-nums {{ $fg }}">{{ number_format($row->balance, 2) }}</td>
                    <td class="px-4 py-3 text-right {{ $muted }}">{{ $row->days }}d</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center {{ $muted }}">No outstanding supplier payables as of {{ $asOf }}.</td>
                </tr>
                @endforelse
            </tbody>
            @if($supplierRows->isNotEmpty())
            <tfoot>
                <tr class="border-t {{ $border }} {{ $surface2 }}">
                    <td class="px-4 py-3 text-xs font-bold {{ $fg }}">Total</td>
                    @foreach($bucketKeys as $b)
                    @php $bTotal = $supplierRows->filter(fn($r) => $r->bucket === $b)->sum('balance'); @endphp
                    <td class="px-4 py-3 text-right text-xs font-bold tabular-nums" style="{{ $bTotal > 0 ? 'color:'.$bucketColors[$b] : '' }}">
                        {{ $bTotal > 0 ? number_format($bTotal, 2) : '—' }}
                    </td>
                    @endforeach
                    <td class="px-4 py-3 text-right text-xs font-bold tabular-nums {{ $fg }}">{{ number_format($grandTotals['supplier'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Transporters ─────────────────────────────────────────────── --}}
<div class="mb-6">
    <h2 class="text-xs font-bold uppercase tracking-wider {{ $muted }} mb-2 px-1">Transporter Payables</h2>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b {{ $border }} {{ $surface2 }}">
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Transporter</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#10b981">Current</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#f59e0b">31–60d</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#f97316">61–90d</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#ef4444">90+d</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} text-[10px] uppercase tracking-wide w-28">Total</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} text-[10px] uppercase tracking-wide w-16">Age</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[color:var(--tw-border)]">
                @forelse($transporterRows as $row)
                <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                    <td class="px-4 py-3 font-semibold {{ $fg }}">{{ $row->name }}</td>
                    @foreach($bucketKeys as $b)
                    @php $v = $bv($row, $b); @endphp
                    <td class="px-4 py-3 text-right tabular-nums {{ $v > 0 ? 'font-semibold' : $muted }}"
                        style="{{ $v > 0 ? 'color:'.$bucketColors[$b] : '' }}">
                        {{ $v > 0 ? number_format($v, 2) : '—' }}
                    </td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-bold tabular-nums {{ $fg }}">{{ number_format($row->balance, 2) }}</td>
                    <td class="px-4 py-3 text-right {{ $muted }}">{{ $row->days }}d</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center {{ $muted }}">No outstanding transporter payables as of {{ $asOf }}.</td>
                </tr>
                @endforelse
            </tbody>
            @if($transporterRows->isNotEmpty())
            <tfoot>
                <tr class="border-t {{ $border }} {{ $surface2 }}">
                    <td class="px-4 py-3 text-xs font-bold {{ $fg }}">Total</td>
                    @foreach($bucketKeys as $b)
                    @php $bTotal = $transporterRows->filter(fn($r) => $r->bucket === $b)->sum('balance'); @endphp
                    <td class="px-4 py-3 text-right text-xs font-bold tabular-nums" style="{{ $bTotal > 0 ? 'color:'.$bucketColors[$b] : '' }}">
                        {{ $bTotal > 0 ? number_format($bTotal, 2) : '—' }}
                    </td>
                    @endforeach
                    <td class="px-4 py-3 text-right text-xs font-bold tabular-nums {{ $fg }}">{{ number_format($grandTotals['transporter'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Depots ───────────────────────────────────────────────────── --}}
<div class="mb-6">
    <h2 class="text-xs font-bold uppercase tracking-wider {{ $muted }} mb-2 px-1">Depot Payables</h2>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-xs">
            <thead>
                <tr class="border-b {{ $border }} {{ $surface2 }}">
                    <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Depot</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#10b981">Current</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#f59e0b">31–60d</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#f97316">61–90d</th>
                    <th class="text-right px-4 py-3 font-semibold text-[10px] uppercase tracking-wide w-24" style="color:#ef4444">90+d</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} text-[10px] uppercase tracking-wide w-28">Total</th>
                    <th class="text-right px-4 py-3 font-semibold {{ $muted }} text-[10px] uppercase tracking-wide w-16">Age</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[color:var(--tw-border)]">
                @forelse($depotRows as $row)
                <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                    <td class="px-4 py-3 font-semibold {{ $fg }}">{{ $row->name }}</td>
                    @foreach($bucketKeys as $b)
                    @php $v = $bv($row, $b); @endphp
                    <td class="px-4 py-3 text-right tabular-nums {{ $v > 0 ? 'font-semibold' : $muted }}"
                        style="{{ $v > 0 ? 'color:'.$bucketColors[$b] : '' }}">
                        {{ $v > 0 ? number_format($v, 2) : '—' }}
                    </td>
                    @endforeach
                    <td class="px-4 py-3 text-right font-bold tabular-nums {{ $fg }}">{{ number_format($row->balance, 2) }}</td>
                    <td class="px-4 py-3 text-right {{ $muted }}">{{ $row->days }}d</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center {{ $muted }}">No outstanding depot payables as of {{ $asOf }}.</td>
                </tr>
                @endforelse
            </tbody>
            @if($depotRows->isNotEmpty())
            <tfoot>
                <tr class="border-t {{ $border }} {{ $surface2 }}">
                    <td class="px-4 py-3 text-xs font-bold {{ $fg }}">Total</td>
                    @foreach($bucketKeys as $b)
                    @php $bTotal = $depotRows->filter(fn($r) => $r->bucket === $b)->sum('balance'); @endphp
                    <td class="px-4 py-3 text-right text-xs font-bold tabular-nums" style="{{ $bTotal > 0 ? 'color:'.$bucketColors[$b] : '' }}">
                        {{ $bTotal > 0 ? number_format($bTotal, 2) : '—' }}
                    </td>
                    @endforeach
                    <td class="px-4 py-3 text-right text-xs font-bold tabular-nums {{ $fg }}">{{ number_format($grandTotals['depot'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Grand total row --}}
<div class="rounded-2xl border {{ $border }} p-4 flex items-center justify-between" style="background:var(--tw-surface)">
    <span class="text-sm font-bold {{ $fg }}">Total Accounts Payable — as of {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}</span>
    <span class="text-xl font-bold" style="color:#ef4444">{{ number_format($grandTotals['total'], 2) }}</span>
</div>

{{-- Print --}}
<div class="no-print flex justify-end mt-4">
    <button onclick="window.print()" class="{{ $btnGhost }}">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4H8v4a1 1 0 001 1z"/></svg>
        Print
    </button>
</div>

@endsection
