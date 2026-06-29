@extends('layouts.app')
@section('title', 'Trial Balance')
@section('subtitle', 'Debit and credit totals by account for the selected period.')

@section('content')

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
    <a href="{{ route('accounting.trial-balance.export', request()->query()) }}"
       class="tw-btn-ghost text-xs px-3 py-1.5 rounded-xl flex items-center gap-1.5 ml-auto">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export CSV
    </a>
</form>

@if($lines->isEmpty())
<div class="rounded-2xl border p-12 text-center" style="background:var(--tw-surface);border-color:var(--tw-border)">
    <p class="text-sm" style="color:var(--tw-muted)">No posted journal entries in this period. Post entries via Journal Entries first.</p>
</div>
@else

<div class="space-y-4">

    {{-- Balance indicator --}}
    <div class="rounded-xl border px-4 py-3 flex items-center gap-3" style="background:{{ $balanced ? 'rgba(16,185,129,.08)' : 'rgba(239,68,68,.08)' }};border-color:{{ $balanced ? 'rgba(16,185,129,.25)' : 'rgba(239,68,68,.25)' }}">
        <span class="text-lg">{{ $balanced ? '✓' : '⚠' }}</span>
        <span class="text-sm font-semibold {{ $balanced ? 'text-emerald-400' : 'text-rose-400' }}">
            {{ $balanced ? 'Books balance — total debits equal total credits.' : 'Warning: Debits do not equal credits. Review journal entries.' }}
        </span>
    </div>

    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-[11px] uppercase tracking-wider" style="background:var(--tw-surface-2);color:var(--tw-muted)">
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Account</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-right">Debit</th>
                    <th class="px-4 py-3 text-right">Credit</th>
                    <th class="px-4 py-3 text-right">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y" style="divide-color:var(--tw-border)">
                @php $typeColors = ['asset'=>'text-sky-400','liability'=>'text-rose-400','equity'=>'text-purple-400','revenue'=>'text-emerald-400','expense'=>'text-amber-400'] @endphp
                @foreach($lines as $line)
                @php $balance = (float)$line->total_debit - (float)$line->total_credit; @endphp
                <tr class="hover:bg-white/[.02] transition" style="background:var(--tw-surface)">
                    <td class="px-4 py-3 font-mono text-xs font-semibold" style="color:var(--tw-fg)">{{ $line->code }}</td>
                    <td class="px-4 py-3 text-xs" style="color:var(--tw-fg)">{{ $line->name }}</td>
                    <td class="px-4 py-3">
                        <span class="text-[10px] font-semibold {{ $typeColors[$line->type] ?? '' }}">{{ ucfirst($line->type) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($line->total_debit,2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums" style="color:var(--tw-fg)">{{ number_format($line->total_credit,2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $balance >= 0 ? '' : 'text-rose-400' }}" style="{{ $balance >= 0 ? 'color:var(--tw-fg)' : '' }}">{{ number_format(abs($balance),2) }} {{ $balance >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--tw-surface-2)">
                    <td colspan="3" class="px-4 py-3 font-bold text-right text-xs uppercase tracking-wider" style="color:var(--tw-muted)">Totals</td>
                    <td class="px-4 py-3 text-right font-bold tabular-nums {{ $balanced ? 'text-emerald-400' : 'text-rose-400' }}">{{ number_format($totals['debit'],2) }}</td>
                    <td class="px-4 py-3 text-right font-bold tabular-nums {{ $balanced ? 'text-emerald-400' : 'text-rose-400' }}">{{ number_format($totals['credit'],2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endif

@endsection
