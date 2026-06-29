@extends('layouts.app')
@section('title', 'Bills You Haven\'t Paid')
@section('subtitle', 'Outstanding amounts owed to suppliers, depots, and transporters — grouped by how old they are.')

@section('content')

@php
    $buckets = ['current'=>'≤ 30 d','31_60'=>'31–60 d','61_90'=>'61–90 d','90_plus'=>'90+ d'];
    $colors  = ['current'=>'text-emerald-400','31_60'=>'text-amber-400','61_90'=>'text-orange-400','90_plus'=>'text-rose-400'];
@endphp

{{-- Filters --}}
<form method="GET" class="no-print flex flex-wrap items-end gap-3 mb-6">
    <div>
        <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">As of date</label>
        <input type="date" name="as_of" value="{{ $asOf }}"
               class="rounded-xl border px-3 py-1.5 text-sm"
               style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
    </div>
    <button type="submit" class="tw-btn-primary text-xs px-4 py-2 rounded-xl">Apply</button>
    <a href="{{ route('reports.index') }}" class="text-xs" style="color:var(--tw-muted)">← Reports</a>
    <button type="button" onclick="window.print()" class="tw-btn-ghost text-xs px-4 py-2 rounded-xl ml-auto flex items-center gap-2">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4H8v4a1 1 0 001 1z"/></svg>
        Print
    </button>
</form>

{{-- Grand totals strip --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @foreach([
        ['label'=>'Suppliers','value'=>$grandTotals['supplier'],'color'=>'#a855f7'],
        ['label'=>'Transporters','value'=>$grandTotals['transporter'],'color'=>'#0ea5e9'],
        ['label'=>'Depots','value'=>$grandTotals['depot'],'color'=>'#f59e0b'],
        ['label'=>'Total AP','value'=>$grandTotals['total'],'color'=>'#ef4444'],
    ] as $card)
    <div class="rounded-2xl border p-4" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <div class="text-[11px] font-semibold uppercase tracking-wider mb-1" style="color:var(--tw-muted)">{{ $card['label'] }}</div>
        <div class="text-lg font-bold" style="color:{{ $card['color'] }}">{{ number_format($card['value'],2) }}</div>
    </div>
    @endforeach
</div>

{{-- Suppliers table --}}
<div class="mb-8">
    <h2 class="text-sm font-bold mb-3" style="color:var(--tw-fg)">Supplier Payables</h2>
    @if($supplierRows->isEmpty())
    <p class="text-sm" style="color:var(--tw-muted)">No outstanding supplier payables as of {{ $asOf }}.</p>
    @else
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-[11px] uppercase tracking-wider" style="background:var(--tw-surface-2);color:var(--tw-muted)">
                    <th class="px-4 py-3 text-left">Supplier</th>
                    <th class="px-4 py-3 text-center">Age</th>
                    <th class="px-4 py-3 text-center">Bucket</th>
                    <th class="px-4 py-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($supplierRows as $row)
                <tr style="background:var(--tw-surface)">
                    <td class="px-4 py-3 font-medium" style="color:var(--tw-fg)">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-center text-xs" style="color:var(--tw-muted)">{{ $row->days }} days</td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-[11px] font-semibold {{ $colors[$row->bucket] }}">{{ $buckets[$row->bucket] }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($row->balance,2) }}</td>
                </tr>
                @endforeach
                <tr style="background:var(--tw-surface-2)">
                    <td colspan="3" class="px-4 py-3 font-bold text-right text-xs uppercase tracking-wider" style="color:var(--tw-muted)">Total Suppliers</td>
                    <td class="px-4 py-3 text-right font-bold tabular-nums" style="color:var(--tw-fg)">{{ number_format($grandTotals['supplier'],2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Transporters table --}}
<div class="mb-8">
    <h2 class="text-sm font-bold mb-3" style="color:var(--tw-fg)">Transporter Payables</h2>
    @if($transporterRows->isEmpty())
    <p class="text-sm" style="color:var(--tw-muted)">No outstanding transporter payables as of {{ $asOf }}.</p>
    @else
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-[11px] uppercase tracking-wider" style="background:var(--tw-surface-2);color:var(--tw-muted)">
                    <th class="px-4 py-3 text-left">Transporter</th>
                    <th class="px-4 py-3 text-center">Age</th>
                    <th class="px-4 py-3 text-center">Bucket</th>
                    <th class="px-4 py-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($transporterRows as $row)
                <tr style="background:var(--tw-surface)">
                    <td class="px-4 py-3 font-medium" style="color:var(--tw-fg)">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-center text-xs" style="color:var(--tw-muted)">{{ $row->days }} days</td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-[11px] font-semibold {{ $colors[$row->bucket] }}">{{ $buckets[$row->bucket] }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($row->balance,2) }}</td>
                </tr>
                @endforeach
                <tr style="background:var(--tw-surface-2)">
                    <td colspan="3" class="px-4 py-3 font-bold text-right text-xs uppercase tracking-wider" style="color:var(--tw-muted)">Total Transporters</td>
                    <td class="px-4 py-3 text-right font-bold tabular-nums" style="color:var(--tw-fg)">{{ number_format($grandTotals['transporter'],2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Depots table --}}
<div class="mb-8">
    <h2 class="text-sm font-bold mb-3" style="color:var(--tw-fg)">Depot Payables</h2>
    @if($depotRows->isEmpty())
    <p class="text-sm" style="color:var(--tw-muted)">No outstanding depot payables as of {{ $asOf }}.</p>
    @else
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-[11px] uppercase tracking-wider" style="background:var(--tw-surface-2);color:var(--tw-muted)">
                    <th class="px-4 py-3 text-left">Depot</th>
                    <th class="px-4 py-3 text-center">Age</th>
                    <th class="px-4 py-3 text-center">Bucket</th>
                    <th class="px-4 py-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($depotRows as $row)
                <tr style="background:var(--tw-surface)">
                    <td class="px-4 py-3 font-medium" style="color:var(--tw-fg)">{{ $row->name }}</td>
                    <td class="px-4 py-3 text-center text-xs" style="color:var(--tw-muted)">{{ $row->days }} days</td>
                    <td class="px-4 py-3 text-center">
                        <span class="text-[11px] font-semibold {{ $colors[$row->bucket] }}">{{ $buckets[$row->bucket] }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($row->balance,2) }}</td>
                </tr>
                @endforeach
                <tr style="background:var(--tw-surface-2)">
                    <td colspan="3" class="px-4 py-3 font-bold text-right text-xs uppercase tracking-wider" style="color:var(--tw-muted)">Total Depots</td>
                    <td class="px-4 py-3 text-right font-bold tabular-nums" style="color:var(--tw-fg)">{{ number_format($grandTotals['depot'],2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Grand total --}}
<div class="rounded-2xl border p-4 flex items-center justify-between" style="background:var(--tw-surface);border-color:var(--tw-border)">
    <span class="text-sm font-bold" style="color:var(--tw-fg)">Total Accounts Payable as of {{ $asOf }}</span>
    <span class="text-xl font-bold text-rose-400">{{ number_format($grandTotals['total'],2) }}</span>
</div>

@endsection
