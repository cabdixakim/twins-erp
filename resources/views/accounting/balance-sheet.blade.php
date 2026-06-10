@extends('layouts.app')
@section('title', 'Balance Sheet')
@section('subtitle', 'Assets, liabilities and equity snapshot derived from operational data.')

@section('content')

<form method="GET" class="flex flex-wrap items-end gap-3 mb-6">
    <div>
        <label class="block text-[11px] font-semibold mb-1" style="color:var(--tw-muted)">As of date</label>
        <input type="date" name="as_of" value="{{ $asOf }}"
               class="rounded-xl border px-3 py-1.5 text-sm"
               style="background:var(--tw-surface-2);border-color:var(--tw-border);color:var(--tw-fg)">
    </div>
    <button type="submit" class="tw-btn-primary text-xs px-4 py-2 rounded-xl">Apply</button>
    <a href="{{ route('accounting.index') }}" class="text-xs" style="color:var(--tw-muted)">← Accounting</a>
</form>

<div class="max-w-3xl space-y-6">

    {{-- ASSETS --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold text-sky-400">ASSETS</h3>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">

                {{-- Bank --}}
                <tr><td colspan="2" class="px-5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider" style="color:var(--tw-muted)">Cash & Bank</td></tr>
                @foreach($bankRows as $bank)
                <tr>
                    <td class="px-5 py-2 pl-8" style="color:var(--tw-fg)">{{ $bank->name }} <span class="text-[10px]" style="color:var(--tw-muted)">({{ $bank->currency }})</span></td>
                    <td class="px-5 py-2 text-right font-semibold tabular-nums {{ $bank->balance >= 0 ? '' : 'text-rose-400' }}" style="{{ $bank->balance >= 0 ? 'color:var(--tw-fg)' : '' }}">{{ number_format($bank->balance,2) }}</td>
                </tr>
                @endforeach
                @if(empty($bankRows))
                <tr><td colspan="2" class="px-5 py-2 pl-8 text-xs" style="color:var(--tw-muted)">No bank accounts</td></tr>
                @endif

                {{-- Petty Cash --}}
                <tr>
                    <td class="px-5 py-2 pl-8" style="color:var(--tw-fg)">Petty Cash Floats</td>
                    <td class="px-5 py-2 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($pettyCashTotal,2) }}</td>
                </tr>

                {{-- AR --}}
                <tr><td colspan="2" class="px-5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider" style="color:var(--tw-muted)">Accounts Receivable</td></tr>
                <tr>
                    <td class="px-5 py-2 pl-8" style="color:var(--tw-fg)">Trade Receivables – Clients</td>
                    <td class="px-5 py-2 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($arTotal,2) }}</td>
                </tr>

                {{-- Inventory --}}
                <tr><td colspan="2" class="px-5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider" style="color:var(--tw-muted)">Inventory</td></tr>
                <tr>
                    <td class="px-5 py-2 pl-8" style="color:var(--tw-fg)">Fuel Stock (at weighted avg cost)</td>
                    <td class="px-5 py-2 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($inventoryValue,2) }}</td>
                </tr>

                {{-- Total --}}
                <tr style="background:var(--tw-surface-2)">
                    <td class="px-5 py-3 font-bold text-sky-400">Total Assets</td>
                    <td class="px-5 py-3 text-right font-bold text-sky-400 tabular-nums">{{ number_format($totalAssets,2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- LIABILITIES --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold text-rose-400">LIABILITIES</h3>
        </div>
        <table class="w-full text-sm" style="background:var(--tw-surface)">
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                <tr><td colspan="2" class="px-5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider" style="color:var(--tw-muted)">Accounts Payable</td></tr>
                <tr>
                    <td class="px-5 py-2 pl-8" style="color:var(--tw-fg)">Payables – Suppliers</td>
                    <td class="px-5 py-2 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format(max(0,$supplierPayables),2) }}</td>
                </tr>
                <tr>
                    <td class="px-5 py-2 pl-8" style="color:var(--tw-fg)">Payables – Transporters</td>
                    <td class="px-5 py-2 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format(max(0,$transporterPayables),2) }}</td>
                </tr>
                <tr>
                    <td class="px-5 py-2 pl-8" style="color:var(--tw-fg)">Payables – Depots</td>
                    <td class="px-5 py-2 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format(max(0,$depotPayables),2) }}</td>
                </tr>
                <tr style="background:var(--tw-surface-2)">
                    <td class="px-5 py-3 font-bold text-rose-400">Total Liabilities</td>
                    <td class="px-5 py-3 text-right font-bold text-rose-400 tabular-nums">{{ number_format($totalLiabilities,2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- EQUITY --}}
    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <div class="px-5 py-4 flex items-center justify-between" style="background:var(--tw-surface-2)">
            <h3 class="text-sm font-bold text-purple-400">EQUITY (Net Position)</h3>
            <span class="text-lg font-bold {{ $equity >= 0 ? 'text-purple-400' : 'text-rose-400' }}">{{ number_format($equity,2) }}</span>
        </div>
        <div class="px-5 py-4" style="background:var(--tw-surface)">
            <p class="text-xs" style="color:var(--tw-muted)">Equity = Total Assets − Total Liabilities. This is a simplified operational balance sheet derived from live data, not from posted journal entries.</p>
        </div>
    </div>

    {{-- Check --}}
    <div class="rounded-xl border px-5 py-3 flex items-center justify-between" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
        <span class="text-xs font-semibold" style="color:var(--tw-muted)">Assets = Liabilities + Equity</span>
        <span class="text-xs font-bold {{ abs($totalAssets - $totalLiabilities - $equity) < 0.01 ? 'text-emerald-400' : 'text-rose-400' }}">
            {{ number_format($totalAssets,2) }} = {{ number_format($totalLiabilities,2) }} + {{ number_format($equity,2) }} ✓
        </span>
    </div>

</div>

@endsection
