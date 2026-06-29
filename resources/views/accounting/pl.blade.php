@extends('layouts.app')
@section('title', 'Profit & Loss')
@section('subtitle', $useGL ? 'General Ledger mode — journal_entry_lines.' : 'Operational mode — transactional tables.')

@section('content')

{{-- Date filter --}}
<form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
    <div>
        <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">From</label>
        <input type="date" name="from" value="{{ $from }}"
               class="rounded-xl border px-3 py-1.5 text-sm"
               style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
    </div>
    <div>
        <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">To</label>
        <input type="date" name="to" value="{{ $to }}"
               class="rounded-xl border px-3 py-1.5 text-sm"
               style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
    </div>
    <button type="submit" class="tw-btn-primary text-xs px-4 py-2 rounded-xl">Apply</button>
    <a href="{{ route('accounting.index') }}" class="text-xs" style="color:var(--tw-muted)">← Accounting</a>

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

<div class="space-y-6">

@if($useGL)
{{-- ══════════════════ GL MODE ══════════════════ --}}

    {{-- Revenue --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold" style="color:var(--tw-fg)">Revenue</h3>
            <span class="text-sm font-bold text-emerald-400">{{ number_format($totalRevenue, 2) }}</span>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @forelse($glRevenue as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-muted)">
                        <span class="font-mono text-[10px] mr-2">{{ $row->code }}</span>
                    </td>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">{{ $row->account_name }}</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums {{ $row->net >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                        {{ number_format($row->net, 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-5 py-4 text-sm" style="color:var(--tw-muted)">No revenue journal entries in this period. Historical data before accounting was enabled is not reflected here.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- COGS --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold" style="color:var(--tw-fg)">Cost of Goods Sold</h3>
            <span class="text-sm font-bold text-rose-400">({{ number_format($totalCogs, 2) }})</span>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @forelse($glCogs as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-muted)">
                        <span class="font-mono text-[10px] mr-2">{{ $row->code }}</span>
                    </td>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">{{ $row->account_name }}</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($row->net, 2) }})</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-5 py-4 text-sm" style="color:var(--tw-muted)">No COGS journal entries in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Gross Profit --}}
    <div class="rounded-2xl border px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <span class="text-sm font-bold" style="color:var(--tw-fg)">Gross Profit</span>
        <div class="text-right">
            <span class="text-lg font-bold {{ $grossProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ number_format($grossProfit, 2) }}</span>
            @if($grossMargin !== null)
            <div class="text-[11px]" style="color:var(--tw-muted)">{{ $grossMargin }}% margin</div>
            @endif
        </div>
    </div>

    {{-- Operating Expenses --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold" style="color:var(--tw-fg)">Operating Expenses</h3>
            <span class="text-sm font-bold text-rose-400">({{ number_format($totalOpex, 2) }})</span>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @forelse($glOpex as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-muted)">
                        <span class="font-mono text-[10px] mr-2">{{ $row->code }}</span>
                    </td>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">{{ $row->account_name }}</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($row->net, 2) }})</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-5 py-4 text-sm" style="color:var(--tw-muted)">No operating expense journal entries in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

@else
{{-- ══════════════════ OPERATIONAL MODE ══════════════════ --}}

    {{-- Revenue --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold" style="color:var(--tw-fg)">Revenue</h3>
            <span class="text-sm font-bold text-emerald-400">{{ number_format($totalRevenue + $totalJournalRevenue, 2) }}</span>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($revenueRows as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">{{ $row->product_name }}</td>
                    <td class="px-5 py-2.5 text-right text-xs" style="color:var(--tw-muted)">{{ number_format($row->qty,0) }} L</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($row->revenue,2) }}</td>
                </tr>
                @endforeach
                @foreach($journalRevenue as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">
                        {{ $row->account_name }}
                        <span class="ml-1 text-[10px] px-1 rounded" style="background:rgba(99,102,241,.12);color:#6366f1">journal adj.</span>
                    </td>
                    <td class="px-5 py-2.5"></td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums {{ $row->net >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ number_format($row->net,2) }}</td>
                </tr>
                @endforeach
                @if($revenueRows->isEmpty() && $journalRevenue->isEmpty())
                <tr><td colspan="3" class="px-5 py-4 text-sm" style="color:var(--tw-muted)">No posted sales or revenue journal entries in this period.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- COGS --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold" style="color:var(--tw-fg)">Cost of Goods Sold</h3>
            <span class="text-sm font-bold text-rose-400">({{ number_format($totalCogs + $totalLanded,2) }})</span>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($cogsRows as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">{{ $row->product_name }} — purchase cost</td>
                    <td class="px-5 py-2.5 text-right text-xs" style="color:var(--tw-muted)">{{ number_format($row->qty,0) }} L</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($row->cogs,2) }})</td>
                </tr>
                @endforeach
                @foreach($landedCosts as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">Landed cost — {{ ucfirst(str_replace('_',' ',$row->category)) }}</td>
                    <td class="px-5 py-2.5"></td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($row->total,2) }})</td>
                </tr>
                @endforeach
                @if($cogsRows->isEmpty() && $landedCosts->isEmpty())
                <tr><td colspan="3" class="px-5 py-4 text-sm" style="color:var(--tw-muted)">No COGS in this period.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Gross Profit --}}
    <div class="rounded-2xl border px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <span class="text-sm font-bold" style="color:var(--tw-fg)">Gross Profit</span>
        <div class="text-right">
            <span class="text-lg font-bold {{ $grossProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ number_format($grossProfit,2) }}</span>
            @if($grossMargin !== null)
            <div class="text-[11px]" style="color:var(--tw-muted)">{{ $grossMargin }}% margin</div>
            @endif
        </div>
    </div>

    {{-- Operating Expenses --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold" style="color:var(--tw-fg)">Operating Expenses</h3>
            <span class="text-sm font-bold text-rose-400">({{ number_format($totalOpex,2) }})</span>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @if($transporterCharges > 0)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">Transport & Freight charges</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($transporterCharges,2) }})</td>
                </tr>
                @endif
                @if($depotCharges > 0)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">Depot storage & handling</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($depotCharges,2) }})</td>
                </tr>
                @endif
                @foreach($pettyCashExpenses as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">{{ ucfirst(str_replace('_',' ',$row->category)) }} (petty cash)</td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($row->total,2) }})</td>
                </tr>
                @endforeach
                @if($totalOpex == 0)
                <tr><td colspan="2" class="px-5 py-4 text-sm" style="color:var(--tw-muted)">No operating expenses in this period.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Journal Adjustments (manual entries only) --}}
    @if($journalExpenses->isNotEmpty() || $totalJournalRevenue != 0)
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <div>
                <h3 class="text-sm font-bold" style="color:var(--tw-fg)">Journal Adjustments</h3>
                <div class="text-[11px] mt-0.5" style="color:var(--tw-muted)">Manually-posted journal entries (depreciation, accruals, salaries, etc.)</div>
            </div>
            <span class="text-sm font-bold {{ ($totalJournalRevenue - $totalJournalExpenses) >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                {{ number_format($totalJournalRevenue - $totalJournalExpenses, 2) }}
            </span>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @foreach($journalExpenses as $row)
                <tr>
                    <td class="px-5 py-2.5" style="color:var(--tw-fg)">
                        <span class="text-[10px] font-mono mr-2" style="color:var(--tw-muted)">{{ $row->code }}</span>{{ $row->account_name }}
                    </td>
                    <td class="px-5 py-2.5 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">({{ number_format($row->net,2) }})</td>
                </tr>
                @endforeach
                @if($journalExpenses->isEmpty())
                <tr><td colspan="2" class="px-5 py-4 text-sm" style="color:var(--tw-muted)">No manual expense journal entries in this period.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    @endif

@endif
{{-- ══════════════════ SHARED NET PROFIT ══════════════════ --}}

    {{-- Net Profit --}}
    <div class="rounded-2xl border px-5 py-5 flex items-center justify-between" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <div>
            <span class="text-base font-bold" style="color:var(--tw-fg)">Net Profit</span>
            @if($useGL)
            <div class="text-[11px] mt-0.5" style="color:var(--tw-muted)">GL entries posted after accounting was enabled only</div>
            @endif
        </div>
        <div class="text-right">
            <span class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ number_format($netProfit,2) }}</span>
            @if($netMargin !== null)
            <div class="text-[11px] mt-0.5" style="color:var(--tw-muted)">{{ $netMargin }}% net margin</div>
            @endif
        </div>
    </div>

</div>

@endsection
