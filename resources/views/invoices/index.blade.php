@php
    $title    = 'Invoices';
    $subtitle = 'All client invoices and outstanding AR.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";

    $statusClasses = [
        'draft'   => 'bg-slate-500/10 text-slate-400 border border-slate-500/20',
        'sent'    => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
        'overdue' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
        'paid'    => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
        'void'    => 'bg-slate-500/10 text-slate-500 border border-slate-500/20',
    ];
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

{{-- KPI Cards --}}
<div class="grid gap-4 sm:grid-cols-3 mb-6">
    @php
        $kpis = [
            ['label' => 'Outstanding',      'value' => $company->base_currency . ' ' . number_format($totals['outstanding'], 2), 'color' => 'text-blue-400'],
            ['label' => 'Overdue',          'value' => $company->base_currency . ' ' . number_format($totals['overdue'], 2),    'color' => 'text-rose-400'],
            ['label' => 'Paid This Month',  'value' => $company->base_currency . ' ' . number_format($totals['paid_this_month'], 2), 'color' => 'text-emerald-400'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[11px] uppercase tracking-wider {{ $muted }} mb-1">{{ $kpi['label'] }}</div>
        <div class="text-xl font-black {{ $kpi['color'] }}">{{ $kpi['value'] }}</div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Status</label>
            <select name="status" class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All statuses</option>
                @foreach(['draft','sent','overdue','paid','void'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Client</label>
            <select name="client_id" class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All clients</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected(request('client_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
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
        <button type="submit" class="{{ $btnPrimary }}">Filter</button>
        @if(request()->hasAny(['status','client_id','from','to']))
            <a href="{{ route('invoices.index') }}" class="{{ $btnGhost }}">Clear</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b {{ $border }} {{ $surface2 }}">
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Invoice #</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Client</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Issued</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Due</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Status</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Total</th>
                <th class="text-right px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Balance Due</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[color:var(--tw-border)]">
            @forelse($invoices as $inv)
            @php
                $balanceDue = max(0, (float)$inv->total - (float)$inv->paid_amount);
                $stClass = $statusClasses[$inv->status] ?? $statusClasses['sent'];
            @endphp
            <tr class="hover:bg-[color:var(--tw-surface-2)] transition cursor-pointer"
                onclick="window.open('{{ route('invoices.show', $inv) }}', '_blank')">
                <td class="px-4 py-3">
                    <span class="font-mono font-bold {{ $fg }}">{{ $inv->invoice_number }}</span>
                    @if($inv->sale)
                        <div class="{{ $muted }} text-[10px]">{{ $inv->sale->reference }}</div>
                    @endif
                </td>
                <td class="px-4 py-3 {{ $fg }} font-medium">{{ $inv->client?->name ?? '—' }}</td>
                <td class="px-4 py-3 {{ $muted }}">{{ $inv->issued_date->format('d M Y') }}</td>
                <td class="px-4 py-3">
                    <span class="{{ $inv->status === 'overdue' ? 'text-rose-400 font-semibold' : $muted }}">
                        {{ $inv->due_date->format('d M Y') }}
                    </span>
                    @if($inv->status === 'overdue')
                        @php $days = max(0,(int)now()->diffInDays($inv->due_date)); @endphp
                        <div class="text-rose-400 text-[10px]">{{ $days }}d overdue</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $stClass }}">
                        {{ ucfirst($inv->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right font-mono {{ $fg }}">
                    {{ $inv->currency }} {{ number_format($inv->total, 2) }}
                </td>
                <td class="px-4 py-3 text-right font-mono">
                    @if($inv->status === 'paid')
                        <span class="text-emerald-400 font-bold">—</span>
                    @elseif($inv->status === 'void')
                        <span class="{{ $muted }}">—</span>
                    @else
                        <span class="{{ $balanceDue > 0 ? 'text-rose-400 font-bold' : 'text-emerald-400' }}">
                            {{ $inv->currency }} {{ number_format($balanceDue, 2) }}
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                    <a href="{{ route('invoices.pdf', $inv) }}"
                       title="Download PDF"
                       class="{{ $btnGhost }} !px-2.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                        PDF
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-12 text-center {{ $muted }}">No invoices found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($invoices->hasPages())
    <div class="mt-4">{{ $invoices->links() }}</div>
@endif

@endsection
