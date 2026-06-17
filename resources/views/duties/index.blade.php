@extends('layouts.app')

@section('title', 'Duties Register')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';

    $vendorTypeLabels = [
        'customs_authority' => 'Customs Authority',
        'supplier'          => 'Supplier',
        'depot'             => 'Depot',
        'transporter'       => 'Transporter',
        'self'              => 'Self',
    ];
    $statusColors = [
        'posted'  => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30',
        'pending' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30',
        'waived'  => 'bg-slate-500/10 text-slate-500 border-slate-500/20',
    ];
@endphp

<div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Payables</div>
            <h1 class="text-xl font-bold {{ $fg }}">Duties Register</h1>
            <p class="text-sm {{ $muted }} mt-0.5">All import duty records across purchase shipments.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('duty-vendors.index') }}"
               class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
                By authority
            </a>
            <a href="{{ route('duties.export', request()->query()) }}"
               class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
                CSV Export
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    @if($totals)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Total Trucks</div>
            <div class="text-xl font-bold {{ $fg }}">{{ $totals->total_count }}</div>
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Posted</div>
            <div class="text-xl font-bold text-emerald-500">{{ $totals->posted_count }}</div>
            @if($totals->posted_amount > 0)
                <div class="text-xs {{ $muted }} mt-0.5">{{ number_format($totals->posted_amount, 2) }}</div>
            @endif
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Pending</div>
            <div class="text-xl font-bold text-amber-500">{{ $totals->pending_count }}</div>
            @if($totals->pending_amount > 0)
                <div class="text-xs {{ $muted }} mt-0.5">{{ number_format($totals->pending_amount, 2) }}</div>
            @endif
        </div>
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Waived</div>
            <div class="text-xl font-bold {{ $muted }}">{{ $totals->waived_count }}</div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-semibold {{ $muted }} mb-1">From</label>
            <input type="date" name="from" value="{{ $dateFrom }}"
                class="rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
        </div>
        <div>
            <label class="block text-xs font-semibold {{ $muted }} mb-1">To</label>
            <input type="date" name="to" value="{{ $dateTo }}"
                class="rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
        </div>
        <div>
            <label class="block text-xs font-semibold {{ $muted }} mb-1">Vendor type</label>
            <select name="vendor_type"
                class="rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
                <option value="">All types</option>
                @foreach($vendorTypeLabels as $val => $lbl)
                    <option value="{{ $val }}" {{ $vendorType === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold {{ $muted }} mb-1">Product</label>
            <select name="product_id"
                class="rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
                <option value="">All products</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold {{ $muted }} mb-1">Status</label>
            <select name="status"
                class="rounded-xl border {{ $border }} {{ $surface }} px-3 py-2 text-sm {{ $fg }} focus:outline-none focus:border-[color:var(--tw-accent)]">
                <option value="">All statuses</option>
                <option value="posted"  {{ $status === 'posted'  ? 'selected' : '' }}>Posted</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="waived"  {{ $status === 'waived'  ? 'selected' : '' }}>Waived</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                class="h-9 px-4 rounded-xl bg-[color:var(--tw-accent)] text-white text-sm font-semibold hover:opacity-90 transition">
                Filter
            </button>
            @if($dateFrom || $dateTo || $vendorType || $productId || $status)
                <a href="{{ route('duties.index') }}"
                   class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-sm {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition flex items-center">
                    Clear
                </a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        @if($entries->isEmpty())
            <div class="p-12 text-center text-sm {{ $muted }}">No duty records match the selected filters.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="{{ $muted }} border-b {{ $border }} {{ $surface2 }}">
                    <tr>
                        <th class="text-left px-5 py-3 font-semibold whitespace-nowrap">Border date</th>
                        <th class="text-left px-3 py-3 font-semibold">Purchase</th>
                        <th class="text-left px-3 py-3 font-semibold">Truck</th>
                        <th class="text-left px-3 py-3 font-semibold">Vendor type</th>
                        <th class="text-left px-3 py-3 font-semibold">Vendor</th>
                        <th class="text-right px-3 py-3 font-semibold">Qty</th>
                        <th class="text-right px-3 py-3 font-semibold">Rate/1000L</th>
                        <th class="text-right px-3 py-3 font-semibold">Amount</th>
                        <th class="text-center px-3 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y {{ $border }}">
                    @foreach($entries as $row)
                    <tr class="hover:bg-[color:var(--tw-surface-2)] transition cursor-pointer"
                        onclick="window.location='{{ route('purchases.show', $row->purchase_id) }}'">
                        <td class="px-5 py-3 whitespace-nowrap {{ $muted }}">
                            {{ $row->border_date ? \Carbon\Carbon::parse($row->border_date)->format('d M Y') : '—' }}
                        </td>
                        <td class="px-3 py-3 font-semibold {{ $fg }}">{{ $row->purchase_ref }}</td>
                        <td class="px-3 py-3 {{ $muted }}">{{ $row->truck_reg }}</td>
                        <td class="px-3 py-3 {{ $muted }}">{{ $vendorTypeLabels[$row->duty_vendor_type] ?? $row->duty_vendor_type }}</td>
                        <td class="px-3 py-3 {{ $muted }}">{{ $row->vendor_name }}</td>
                        <td class="px-3 py-3 text-right {{ $muted }} font-mono">
                            {{ $row->duty_qty ? number_format($row->duty_qty, 0) : '—' }}
                        </td>
                        <td class="px-3 py-3 text-right {{ $muted }} font-mono">
                            {{ $row->duty_rate_per_1000l ? number_format($row->duty_rate_per_1000l, 4) : '—' }}
                        </td>
                        <td class="px-3 py-3 text-right font-semibold {{ $fg }}">
                            @if($row->duty_amount)
                                {{ $row->duty_currency }} {{ number_format($row->duty_amount, 2) }}
                            @else
                                <span class="{{ $muted }}">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                            @if($row->duty_status)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border
                                {{ $statusColors[$row->duty_status] ?? 'bg-slate-500/10 text-slate-500 border-slate-500/20' }}">
                                {{ ucfirst($row->duty_status) }}
                            </span>
                            @else
                                <span class="{{ $muted }}">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t {{ $border }} {{ $surface2 }}">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
