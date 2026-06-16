@php
    $title    = 'Reports';
    $subtitle = 'Financial intelligence — P&L, AR aging, throughput, and more.';
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    {{-- P&L by Batch --}}
    <a href="{{ route('reports.pl') }}"
       class="group rounded-2xl border {{ $border }} {{ $surface }} p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
             style="background:rgba(16,185,129,.10); border:1px solid rgba(16,185,129,.20)">
            <svg class="w-6 h-6" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold {{ $fg }} mb-1 group-hover:text-emerald-400 transition">P&amp;L by Batch</h3>
        <p class="text-xs {{ $muted }} leading-relaxed">Purchase cost + landed costs vs. sales revenue. Gross margin and margin % per shipment.</p>
        @if($summary['active_batches'] > 0)
        <div class="mt-4 text-[10px] font-semibold text-emerald-400">{{ $summary['active_batches'] }} active batches</div>
        @endif
    </a>

    {{-- AR Aging --}}
    <a href="{{ route('reports.ar-aging') }}"
       class="group rounded-2xl border {{ $border }} {{ $surface }} p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
             style="background:rgba(239,68,68,.10); border:1px solid rgba(239,68,68,.20)">
            <svg class="w-6 h-6" style="color:#ef4444" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold {{ $fg }} mb-1 group-hover:text-rose-400 transition">AR Aging</h3>
        <p class="text-xs {{ $muted }} leading-relaxed">Outstanding client invoices bucketed by overdue days — Current, 1–30, 31–60, 61–90, 90+.</p>
        @if($summary['overdue_invoices'] > 0)
        <div class="mt-4 text-[10px] font-semibold text-rose-400">{{ $summary['overdue_invoices'] }} overdue invoices</div>
        @elseif($summary['open_invoices'] > 0)
        <div class="mt-4 text-[10px] font-semibold text-amber-400">{{ $summary['open_invoices'] }} open invoices</div>
        @endif
    </a>

    {{-- Throughput --}}
    <a href="{{ route('reports.throughput') }}"
       class="group rounded-2xl border {{ $border }} {{ $surface }} p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
             style="background:rgba(14,165,233,.10); border:1px solid rgba(14,165,233,.20)">
            <svg class="w-6 h-6" style="color:#0ea5e9" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold {{ $fg }} mb-1 group-hover:text-sky-400 transition">Purchased vs Sold</h3>
        <p class="text-xs {{ $muted }} leading-relaxed">Monthly volume — litres purchased vs. sold, transaction counts, and revenue trend.</p>
    </a>

    {{-- Depot Stock (link to existing) --}}
    <a href="{{ route('depot-stock.index') }}"
       class="group rounded-2xl border {{ $border }} {{ $surface }} p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
             style="background:rgba(168,85,247,.10); border:1px solid rgba(168,85,247,.20)">
            <svg class="w-6 h-6" style="color:#a855f7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold {{ $fg }} mb-1 group-hover:text-purple-400 transition">Stock Position</h3>
        <p class="text-xs {{ $muted }} leading-relaxed">Live inventory position by depot — on-hand qty, batch breakdown, recent movements, and CSV export.</p>
    </a>

    {{-- Client Statements (link to clients) --}}
    <a href="{{ route('clients.index') }}"
       class="group rounded-2xl border {{ $border }} {{ $surface }} p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
             style="background:rgba(245,158,11,.10); border:1px solid rgba(245,158,11,.20)">
            <svg class="w-6 h-6" style="color:#f59e0b" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold {{ $fg }} mb-1 group-hover:text-amber-400 transition">Client Statements</h3>
        <p class="text-xs {{ $muted }} leading-relaxed">Per-client AR ledger, statement of account, and invoice history with printable statements.</p>
    </a>

    {{-- Supplier Payables --}}
    <a href="{{ route('suppliers.index') }}"
       class="group rounded-2xl border {{ $border }} {{ $surface }} p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
             style="background:rgba(100,116,139,.10); border:1px solid rgba(100,116,139,.20)">
            <svg class="w-6 h-6" style="color:#64748b" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold {{ $fg }} mb-1 group-hover:text-slate-400 transition">Supplier Payables</h3>
        <p class="text-xs {{ $muted }} leading-relaxed">Outstanding balances, payment history, and printable statements for each supplier.</p>
    </a>

    {{-- AP Aging --}}
    <a href="{{ route('reports.ap-aging') }}"
       class="group rounded-2xl border {{ $border }} {{ $surface }} p-6 hover:-translate-y-0.5 hover:shadow-lg transition-all duration-150 block">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
             style="background:rgba(239,68,68,.10); border:1px solid rgba(239,68,68,.20)">
            <svg class="w-6 h-6" style="color:#ef4444" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold {{ $fg }} mb-1 group-hover:text-rose-400 transition">AP Aging</h3>
        <p class="text-xs {{ $muted }} leading-relaxed">Outstanding payables to suppliers, transporters and depots bucketed by age — current, 31–60, 61–90, 90+.</p>
    </a>

</div>

@endsection
