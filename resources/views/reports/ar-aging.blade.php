@php
    $title    = 'Receivables';
    $subtitle = 'Clients who haven\'t paid yet — how much and how long they\'ve been overdue.';
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
<div class="no-print flex items-center gap-2 text-xs {{ $muted }} mb-4">
    <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    <span>Receivables</span>
</div>

{{-- Filters --}}
<div class="no-print rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">As of date</label>
            <input type="date" name="as_of" value="{{ $asOf }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Client</label>
            <select name="client_id" class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All clients</option>
                @foreach($clients as $cl)
                    <option value="{{ $cl->id }}" @selected(request('client_id') == $cl->id)>{{ $cl->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="{{ $btnPrimary }}">Run</button>
        @if(request()->hasAny(['client_id']) || request('as_of') !== today()->toDateString())
            <a href="{{ route('reports.ar-aging') }}" class="{{ $btnGhost }}">Reset</a>
        @endif
        <div class="ml-auto flex items-end">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="{{ $btnGhost }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </div>
    </form>
</div>

{{-- Grand totals summary bar --}}
@if($byClient->isNotEmpty())
<div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-4">
    @php
        $buckets = [
            ['key'=>'total',   'label'=>'Total Outstanding', 'color'=>'#6366f1'],
            ['key'=>'current', 'label'=>'Current',           'color'=>'#10b981'],
            ['key'=>'1_30',    'label'=>'1–30 days',         'color'=>'#f59e0b'],
            ['key'=>'31_60',   'label'=>'31–60 days',        'color'=>'#f97316'],
            ['key'=>'61_90',   'label'=>'61–90 days',        'color'=>'#ef4444'],
            ['key'=>'90_plus', 'label'=>'90+ days',          'color'=>'#991b1b'],
        ];
    @endphp
    @foreach($buckets as $b)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 text-center">
        <div class="text-[9px] uppercase tracking-wide {{ $muted }} mb-1">{{ $b['label'] }}</div>
        <div class="text-sm font-bold" style="color:{{ $b['color'] }}">
            {{ number_format($grandTotal[$b['key']], 0) }}
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Aging table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b {{ $border }} {{ $surface2 }}">
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Client</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24" style="color:#10b981">Current</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24" style="color:#f59e0b">1–30d</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24" style="color:#f97316">31–60d</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24" style="color:#ef4444">61–90d</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24" style="color:#991b1b">90+d</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Total</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-16">Inv.</th>
                <th class="w-10 px-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[color:var(--tw-border)]">
            @forelse($byClient as $row)
            <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                <td class="px-4 py-3">
                    <div class="font-semibold {{ $fg }}">{{ $row->client?->name ?? 'Unknown Client' }}</div>
                    @if($row->client?->email)
                    <div class="text-[10px] {{ $muted }}">{{ $row->client->email }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 text-right {{ $row->current > 0 ? 'font-semibold' : $muted }}" style="{{ $row->current > 0 ? 'color:#10b981' : '' }}">
                    {{ $row->current > 0 ? number_format($row->current, 2) : '—' }}
                </td>
                <td class="px-4 py-3 text-right {{ $row->{'1_30'} > 0 ? 'font-semibold' : $muted }}" style="{{ $row->{'1_30'} > 0 ? 'color:#f59e0b' : '' }}">
                    {{ $row->{'1_30'} > 0 ? number_format($row->{'1_30'}, 2) : '—' }}
                </td>
                <td class="px-4 py-3 text-right {{ $row->{'31_60'} > 0 ? 'font-semibold' : $muted }}" style="{{ $row->{'31_60'} > 0 ? 'color:#f97316' : '' }}">
                    {{ $row->{'31_60'} > 0 ? number_format($row->{'31_60'}, 2) : '—' }}
                </td>
                <td class="px-4 py-3 text-right {{ $row->{'61_90'} > 0 ? 'font-semibold' : $muted }}" style="{{ $row->{'61_90'} > 0 ? 'color:#ef4444' : '' }}">
                    {{ $row->{'61_90'} > 0 ? number_format($row->{'61_90'}, 2) : '—' }}
                </td>
                <td class="px-4 py-3 text-right {{ $row->{'90_plus'} > 0 ? 'font-bold' : $muted }}" style="{{ $row->{'90_plus'} > 0 ? 'color:#991b1b' : '' }}">
                    {{ $row->{'90_plus'} > 0 ? number_format($row->{'90_plus'}, 2) : '—' }}
                </td>
                <td class="px-4 py-3 text-right font-bold {{ $fg }}">
                    {{ number_format($row->total, 2) }}
                    <div class="text-[9px] {{ $muted }} font-normal">{{ $row->currency }}</div>
                </td>
                <td class="px-4 py-3 text-right {{ $muted }}">{{ $row->invoices->count() }}</td>
                <td class="px-3 py-3">
                    @if($row->client)
                    <a href="{{ route('clients.show', $row->client) }}" class="text-[color:var(--tw-muted)] hover:text-[color:var(--tw-accent)] transition" title="View client ledger">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-4 py-12 text-center {{ $muted }}">
                    <div class="text-sm font-semibold mb-1">No outstanding invoices</div>
                    <div class="text-xs">All client balances are settled as of {{ \Carbon\Carbon::parse($asOf)->format('d M Y') }}.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
        @if($byClient->isNotEmpty())
        <tfoot>
            <tr class="border-t {{ $border }} {{ $surface2 }}">
                <td class="px-4 py-3 text-xs font-bold {{ $fg }}">Total</td>
                <td class="px-4 py-3 text-right text-xs font-bold" style="color:#10b981">{{ number_format($grandTotal['current'], 2) }}</td>
                <td class="px-4 py-3 text-right text-xs font-bold" style="color:#f59e0b">{{ number_format($grandTotal['1_30'], 2) }}</td>
                <td class="px-4 py-3 text-right text-xs font-bold" style="color:#f97316">{{ number_format($grandTotal['31_60'], 2) }}</td>
                <td class="px-4 py-3 text-right text-xs font-bold" style="color:#ef4444">{{ number_format($grandTotal['61_90'], 2) }}</td>
                <td class="px-4 py-3 text-right text-xs font-bold" style="color:#991b1b">{{ number_format($grandTotal['90_plus'], 2) }}</td>
                <td class="px-4 py-3 text-right text-xs font-bold {{ $fg }}">{{ number_format($grandTotal['total'], 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

{{-- Print / actions bar --}}
<div class="no-print flex justify-end mt-4">
    <button onclick="window.print()"
            class="{{ $btnGhost }}">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a1 1 0 001-1v-4H8v4a1 1 0 001 1z"/></svg>
        Print
    </button>
</div>

@endsection
