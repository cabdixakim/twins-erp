@extends('layouts.app')
@section('title', $account->code . ' — ' . $account->name)
@section('subtitle', ucfirst($account->type) . ' account · General Ledger')

@section('content')

<div class="space-y-5">

    {{-- Filters + nav --}}
    <form method="GET" class="flex flex-wrap items-end gap-3">
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
        <a href="{{ route('accounting.ledger', ['from' => $from, 'to' => $to]) }}"
           class="text-xs self-center" style="color:var(--tw-muted)">← General Ledger</a>
    </form>

    {{-- Account summary card --}}
    @php
        $typeColors = [
            'asset'     => 'text-sky-400',
            'liability' => 'text-rose-400',
            'equity'    => 'text-purple-400',
            'revenue'   => 'text-emerald-400',
            'expense'   => 'text-amber-400',
        ];
        $balColor = $typeColors[$account->type] ?? 'text-[color:var(--tw-fg)]';
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-2xl border p-4" style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color:var(--tw-muted)">Opening balance</div>
            <div class="text-base font-bold tabular-nums {{ $openingBalance >= 0 ? '' : 'text-rose-400' }}" style="{{ $openingBalance >= 0 ? 'color:var(--tw-fg)' : '' }}">
                {{ number_format(abs($openingBalance), 2) }}
                @if($openingBalance != 0)<span class="text-[10px] font-normal opacity-60">{{ $openingBalance > 0 ? 'Dr' : 'Cr' }}</span>@endif
            </div>
        </div>
        <div class="rounded-2xl border p-4" style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color:var(--tw-muted)">Total debits</div>
            <div class="text-base font-bold tabular-nums" style="color:var(--tw-fg)">
                {{ number_format($lines->sum('debit'), 2) }}
            </div>
        </div>
        <div class="rounded-2xl border p-4" style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color:var(--tw-muted)">Total credits</div>
            <div class="text-base font-bold tabular-nums" style="color:var(--tw-fg)">
                {{ number_format($lines->sum('credit'), 2) }}
            </div>
        </div>
        <div class="rounded-2xl border p-4" style="background:var(--tw-surface);border-color:var(--tw-border)">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color:var(--tw-muted)">Closing balance</div>
            <div class="text-base font-bold tabular-nums {{ $balColor }}">
                {{ number_format(abs($closingBalance), 2) }}
                @if($closingBalance != 0)<span class="text-[10px] font-normal opacity-60">{{ $closingBalance > 0 ? 'Dr' : 'Cr' }}</span>@endif
            </div>
        </div>
    </div>

    {{-- Transactions table --}}
    @if($lines->isEmpty())
    <div class="rounded-2xl border p-12 text-center" style="background:var(--tw-surface);border-color:var(--tw-border)">
        <p class="text-sm" style="color:var(--tw-muted)">No transactions posted to this account in this period.</p>
    </div>
    @else

    <div class="rounded-2xl border overflow-hidden" style="border-color:var(--tw-border)">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-[10px] uppercase tracking-wider border-b"
                    style="background:var(--tw-surface-2);color:var(--tw-muted);border-color:var(--tw-border)">
                    <th class="px-4 py-3 text-left w-28">Date</th>
                    <th class="px-4 py-3 text-left w-32">Reference</th>
                    <th class="px-4 py-3 text-left">Description</th>
                    <th class="px-4 py-3 text-left w-28">Journal</th>
                    <th class="px-4 py-3 text-right w-28">Debit</th>
                    <th class="px-4 py-3 text-right w-28">Credit</th>
                    <th class="px-4 py-3 text-right w-32">Balance</th>
                </tr>
            </thead>
            <tbody>
                {{-- Opening balance row --}}
                <tr style="background:var(--tw-surface-2)">
                    <td class="px-4 py-2.5 text-xs font-semibold" style="color:var(--tw-muted)">{{ \Carbon\Carbon::parse($from)->format('d M Y') }}</td>
                    <td colspan="3" class="px-4 py-2.5 text-xs font-semibold italic" style="color:var(--tw-muted)">Opening balance</td>
                    <td class="px-4 py-2.5"></td>
                    <td class="px-4 py-2.5"></td>
                    <td class="px-4 py-2.5 text-right text-xs font-bold tabular-nums {{ $openingBalance >= 0 ? '' : 'text-rose-400' }}" style="{{ $openingBalance >= 0 ? 'color:var(--tw-fg)' : '' }}">
                        {{ number_format(abs($openingBalance), 2) }}
                        @if($openingBalance != 0)<span class="text-[9px] opacity-60">{{ $openingBalance > 0 ? 'Dr' : 'Cr' }}</span>@endif
                    </td>
                </tr>

                @foreach($lines as $line)
                @php $bal = $line->running_balance; @endphp
                <tr class="border-t transition hover:bg-white/[.02]"
                    style="background:var(--tw-surface);border-color:var(--tw-border)">
                    <td class="px-4 py-3 text-xs tabular-nums" style="color:var(--tw-muted)">
                        {{ \Carbon\Carbon::parse($line->entry_date)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 font-mono text-xs font-semibold" style="color:var(--tw-fg)">
                        {{ $line->reference }}
                    </td>
                    <td class="px-4 py-3 text-xs" style="color:var(--tw-fg)">
                        {{ $line->line_desc ?: $line->entry_desc }}
                    </td>
                    <td class="px-4 py-3 text-xs" style="color:var(--tw-muted)">
                        {{ $line->journal_name }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-xs font-semibold" style="color:var(--tw-fg)">
                        {{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-xs font-semibold" style="color:var(--tw-fg)">
                        {{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-right tabular-nums text-xs font-bold {{ $bal >= 0 ? '' : 'text-rose-400' }}" style="{{ $bal >= 0 ? 'color:var(--tw-fg)' : '' }}">
                        {{ number_format(abs($bal), 2) }}
                        <span class="text-[9px] font-normal opacity-60">{{ $bal > 0 ? 'Dr' : 'Cr' }}</span>
                    </td>
                </tr>
                @endforeach

                {{-- Closing balance row --}}
                <tr class="border-t" style="background:var(--tw-surface-2);border-color:var(--tw-border)">
                    <td class="px-4 py-2.5 text-xs font-semibold" style="color:var(--tw-muted)">{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</td>
                    <td colspan="3" class="px-4 py-2.5 text-xs font-bold" style="color:var(--tw-fg)">Closing balance</td>
                    <td class="px-4 py-2.5 text-right text-xs font-bold tabular-nums text-emerald-400">
                        {{ number_format($lines->sum('debit'), 2) }}
                    </td>
                    <td class="px-4 py-2.5 text-right text-xs font-bold tabular-nums text-rose-400">
                        {{ number_format($lines->sum('credit'), 2) }}
                    </td>
                    <td class="px-4 py-2.5 text-right text-xs font-bold tabular-nums {{ $closingBalance >= 0 ? $balColor : 'text-rose-400' }}">
                        {{ number_format(abs($closingBalance), 2) }}
                        @if($closingBalance != 0)<span class="text-[9px] font-normal opacity-60">{{ $closingBalance > 0 ? 'Dr' : 'Cr' }}</span>@endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

</div>
@endsection
