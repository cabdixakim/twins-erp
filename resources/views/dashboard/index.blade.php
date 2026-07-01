@extends('layouts.app')
@section('title','Dashboard')
@section('content')


@php
  $volUnit  = $volumeUnit ?? 'L';
  $volLabel = $volUnit === 'M3' ? 'm³' : $volUnit;
  $fmtVol   = fn($v) => number_format((float)$v, $volUnit === 'M3' ? 3 : 0);

  $greeting = match(true) {
    now()->hour < 12 => 'Good morning',
    now()->hour < 17 => 'Good afternoon',
    default          => 'Good evening',
  };
  $firstName = explode(' ', auth()->user()->name)[0];

  $_u = auth()->user();
  $_u->loadMissing('role.permissions');
  $dCan = [
    'purchases.create'  => $_u->hasPermission('purchases.create'),
    'purchases.view'    => $_u->hasPermission('purchases.view'),
    'sales.view'        => $_u->hasPermission('sales.view'),
    'petty-cash.view'   => $_u->hasPermission('petty-cash.view'),
    'reports.export'    => $_u->hasPermission('reports.export'),
    'suppliers.view'    => $_u->hasPermission('suppliers.view'),
    'depots.view'       => $_u->hasPermission('depots.view'),
    'clients.view'      => $_u->hasPermission('clients.view'),
    'inventory.view'    => $_u->hasPermission('inventory.view'),
    'transporters.view' => $_u->hasPermission('transporters.view'),
  ];

  $totalAP = $supplierPayableTotal + $depotPayableTotal + $byCurrency->sum();
@endphp

<div class="space-y-5">

  {{-- ══ Welcome bar ══ --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold tw-fg">{{ $greeting }}, {{ $firstName }}</h1>
      <p class="text-xs tw-muted mt-0.5">
        {{ auth()->user()?->activeCompany?->name ?? config('app.name') }}
        &nbsp;·&nbsp;{{ now()->format('l, d F Y') }}
      </p>
    </div>

    {{-- Quick Actions --}}
    <div class="flex items-center gap-2">
      @if($dCan['purchases.create'])
      <a href="{{ route('purchases.create') }}"
         class="h-9 w-9 sm:w-auto sm:px-3.5 rounded-xl border text-xs font-semibold tw-fg transition inline-flex items-center justify-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)" title="New Purchase">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        <span class="hidden sm:inline">New Purchase</span>
      </a>
      @endif
      @if($dCan['sales.view'])
      <a href="{{ route('sales.index') }}"
         class="h-9 w-9 sm:w-auto sm:px-3.5 rounded-xl border text-xs font-semibold tw-fg transition inline-flex items-center justify-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)" title="Sales">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span class="hidden sm:inline">Sales</span>
      </a>
      @endif
      @if($dCan['petty-cash.view'])
      <a href="{{ route('petty-cash.index') }}"
         class="h-9 w-9 sm:w-auto sm:px-3.5 rounded-xl border text-xs font-semibold tw-fg transition inline-flex items-center justify-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)" title="Petty Cash">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
        </svg>
        <span class="hidden sm:inline">Petty Cash</span>
      </a>
      @endif
      @if($dCan['reports.export'])
      <a href="{{ route('reports.index') }}"
         class="h-9 w-9 sm:w-auto sm:px-3.5 rounded-xl border text-xs font-semibold tw-fg transition inline-flex items-center justify-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)" title="Reports">
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
        </svg>
        <span class="hidden sm:inline">Reports</span>
      </a>
      @endif
    </div>
  </div>

  {{-- ══ 4 Big KPI cards ══ --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

    {{-- Fuel in stock --}}
    <div class="tw-card rounded-2xl p-5">
      <p class="text-xs font-semibold tw-muted mb-3">⛽ Fuel in Stock</p>
      <p class="text-3xl font-black leading-none" style="color:#0ea5e9">
        {{ $fmtVol($totalStockOnHand) }}
        <span class="text-sm font-semibold" style="color:#0ea5e9;opacity:.7">{{ $volLabel }}</span>
      </p>
      <p class="text-xs tw-muted mt-2">
        across {{ $depotStockRows->count() }} depot{{ $depotStockRows->count() === 1 ? '' : 's' }}
      </p>
    </div>

    {{-- Sales this month --}}
    <div class="tw-card rounded-2xl p-5">
      <p class="text-xs font-semibold tw-muted mb-3">📦 Sales This Month</p>
      <p class="text-3xl font-black leading-none" style="color:#10b981">
        {{ number_format($revenueMtd, 0) }}
        <span class="text-sm font-semibold" style="color:#10b981;opacity:.7">{{ $baseCurrency }}</span>
      </p>
      <p class="text-xs tw-muted mt-2">
        {{ $salesCountMtd }} sale{{ $salesCountMtd === 1 ? '' : 's' }}
        @if($grossMarginPct > 0)
          &nbsp;·&nbsp;<span style="color:#10b981">{{ $grossMarginPct }}% margin</span>
        @endif
      </p>
    </div>

    {{-- Open orders --}}
    <div class="tw-card rounded-2xl p-5">
      <p class="text-xs font-semibold tw-muted mb-3">🚚 Orders In Progress</p>
      <p class="text-3xl font-black leading-none" style="color:#f59e0b">
        {{ number_format($openPurchasesCount) }}
      </p>
      <p class="text-xs tw-muted mt-2">
        @if($openByStatus->isNotEmpty())
          @foreach(['draft'=>'draft','confirmed'=>'confirmed','nominated'=>'nominated'] as $st => $label)
            @if($openByStatus->has($st))
              {{ $openByStatus[$st] }} {{ $label }}{{ !$loop->last && $openByStatus->has(array_keys(array_diff_key(['draft'=>1,'confirmed'=>1,'nominated'=>1],[$st=>1]))[0] ?? '') ? ' · ' : '' }}
            @endif
          @endforeach
        @else
          purchases being processed
        @endif
      </p>
    </div>

    {{-- Petty cash --}}
    <div class="tw-card rounded-2xl p-5">
      <p class="text-xs font-semibold tw-muted mb-3">💵 Cash Float</p>
      <p class="text-3xl font-black leading-none" style="color:#14b8a6">
        {{ number_format($pettyCashTotal, 0) }}
        <span class="text-sm font-semibold" style="color:#14b8a6;opacity:.7">{{ $baseCurrency }}</span>
      </p>
      <p class="text-xs tw-muted mt-2">petty cash on hand</p>
    </div>

  </div>

  {{-- ══ Money summary: owed to you vs. what you owe ══ --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

    {{-- What clients owe you --}}
    @if($dCan['clients.view'])
    <a href="{{ route('clients.index') }}"
       class="tw-card group rounded-2xl p-5 block hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
             style="background:rgba(16,185,129,.10); border:1px solid rgba(16,185,129,.20)">
          <svg style="color:#10b981;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold tw-fg">Receivables</p>
          <p class="text-[10px] tw-muted">money to collect from clients</p>
        </div>
        <svg class="w-4 h-4 tw-muted opacity-40 group-hover:opacity-80 transition shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      @if($clientARByCurrency->isNotEmpty())
        @foreach($clientARByCurrency as $cur => $amt)
        <p class="text-2xl font-black leading-none" style="color:#10b981">
          {{ number_format($amt, 2) }}
          <span class="text-sm font-semibold" style="color:#10b981;opacity:.7">{{ $cur }}</span>
        </p>
        @endforeach
        @if($topARClients->isNotEmpty())
        <div class="mt-3 pt-3 space-y-1.5" style="border-top:1px solid var(--tw-border)">
          @foreach($topARClients as $c)
          <div class="flex justify-between text-xs">
            <span class="tw-fg truncate max-w-[60%]">{{ $c->name }}</span>
            <span class="font-semibold shrink-0" style="color:#10b981">{{ number_format($c->balance, 2) }} {{ $c->currency }}</span>
          </div>
          @endforeach
        </div>
        @endif
      @else
        <p class="text-lg font-black tw-fg">All collected ✓</p>
        <p class="text-xs tw-muted mt-1">No outstanding client balances</p>
      @endif
    </a>
    @endif

    {{-- What you owe (combined AP) --}}
    <div class="tw-card rounded-2xl p-5">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
             style="background:rgba(239,68,68,.10); border:1px solid rgba(239,68,68,.20)">
          <svg style="color:#ef4444;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold tw-fg">Payables</p>
          <p class="text-[10px] tw-muted">bills due to suppliers & depots</p>
        </div>
      </div>
      <p class="text-2xl font-black leading-none" style="color:#ef4444">
        {{ number_format($totalAP, 2) }}
        <span class="text-sm font-semibold" style="color:#ef4444;opacity:.7">{{ $baseCurrency }}</span>
      </p>
      <div class="mt-3 pt-3 space-y-2" style="border-top:1px solid var(--tw-border)">
        @if($dCan['suppliers.view'])
        <div class="flex justify-between text-xs">
          <span class="tw-muted">Suppliers</span>
          <span class="font-semibold tw-fg">{{ number_format($supplierPayableTotal, 2) }}</span>
        </div>
        @endif
        @if($dCan['depots.view'])
        <div class="flex justify-between text-xs">
          <span class="tw-muted">Depots</span>
          <span class="font-semibold tw-fg">{{ number_format($depotPayableTotal, 2) }}</span>
        </div>
        @endif
        @if($dCan['transporters.view'])
        <div class="flex justify-between text-xs">
          <span class="tw-muted">Transporters</span>
          <span class="font-semibold tw-fg">{{ number_format($byCurrency->sum(), 2) }}</span>
        </div>
        @endif
      </div>
    </div>

    {{-- Bank balances --}}
    @if($dCan['petty-cash.view'])
    <a href="{{ route('banks.index') }}"
       class="tw-card group rounded-2xl p-5 block hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
             style="background:rgba(20,184,166,.10); border:1px solid rgba(20,184,166,.20)">
          <svg style="color:#14b8a6;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5v2H3v-2z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12v7M9 12v7M15 12v7M19 12v7"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 19h18"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold tw-fg">Bank Balances</p>
          <p class="text-[10px] tw-muted">money in your bank accounts</p>
        </div>
        <svg class="w-4 h-4 tw-muted opacity-40 group-hover:opacity-80 transition shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      @if($bankByCurrency->isNotEmpty())
        @foreach($bankByCurrency as $cur => $amt)
        <p class="text-2xl font-black leading-none" style="color:{{ $amt >= 0 ? '#14b8a6' : '#ef4444' }}">
          {{ $amt < 0 ? '(' : '' }}{{ number_format(abs($amt), 2) }}{{ $amt < 0 ? ')' : '' }}
          <span class="text-sm font-semibold" style="opacity:.7">{{ $cur }}</span>
        </p>
        @endforeach
        @if($topBankAccounts->isNotEmpty())
        <div class="mt-3 pt-3 space-y-1.5" style="border-top:1px solid var(--tw-border)">
          @foreach($topBankAccounts as $ba)
          <div class="flex justify-between text-xs">
            <span class="tw-fg truncate max-w-[55%]">{{ $ba->name }}</span>
            <span class="font-semibold shrink-0" style="color:{{ $ba->balance >= 0 ? '#14b8a6' : '#ef4444' }}">
              {{ $ba->balance < 0 ? '(' : '' }}{{ number_format(abs($ba->balance), 2) }}{{ $ba->balance < 0 ? ')' : '' }}
              <span style="opacity:.7">{{ $ba->currency }}</span>
            </span>
          </div>
          @endforeach
        </div>
        @endif
      @else
        <p class="text-sm tw-muted">No bank accounts added yet.</p>
      @endif
    </a>
    @endif

  </div>

  {{-- ══ Stock by Depot ══ --}}
  @if($dCan['inventory.view'] && $depotStockRows->isNotEmpty())
  <a href="{{ route('depot-stock.index') }}"
     class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
             style="background:rgba(14,165,233,.10); border:1px solid rgba(14,165,233,.20)">
          <svg style="color:#0ea5e9;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold tw-fg">Fuel by Depot</p>
          <p class="text-[10px] tw-muted">{{ $fmtVol($totalStockOnHand) }} {{ $volLabel }} total across all depots — click to see full breakdown</p>
        </div>
      </div>
      <svg class="w-4 h-4 tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
      </svg>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      @foreach($depotStockRows as $row)
      @php $pct = $totalStockOnHand > 0 ? ($row->total_qty / $totalStockOnHand * 100) : 0; @endphp
      <div class="rounded-xl p-3" style="background:var(--tw-surface-2)">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold tw-fg truncate max-w-[60%]">{{ $row->depot_name }}</span>
          <span class="text-xs font-bold shrink-0" style="color:#0ea5e9">{{ $fmtVol($row->total_qty) }} {{ $volLabel }}</span>
        </div>
        <div class="h-1.5 rounded-full overflow-hidden" style="background:var(--tw-border)">
          <div class="h-full rounded-full" style="width:{{ round($pct) }}%; background:#0ea5e9"></div>
        </div>
        <p class="text-[10px] tw-muted mt-1">{{ round($pct) }}% of total</p>
      </div>
      @endforeach
    </div>
  </a>
  @endif

  {{-- ══ Who you owe — detail cards ══ --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

    @if($dCan['suppliers.view'])
    <a href="{{ route('suppliers.index') }}"
       class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
             style="background:rgba(239,68,68,.10); border:1px solid rgba(239,68,68,.20)">
          <svg style="color:#ef4444;width:1rem;height:1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-xs font-bold tw-fg">Owed to Suppliers</p>
          <p class="text-[10px] tw-muted">fuel purchase invoices</p>
        </div>
        <svg class="w-3.5 h-3.5 tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      @if($supplierByCurrency->isNotEmpty())
        @foreach($supplierByCurrency as $cur => $total)
        <p class="text-xl font-black leading-none" style="color:#ef4444">
          {{ number_format($total, 2) }} <span class="text-xs" style="opacity:.7">{{ $cur }}</span>
        </p>
        @endforeach
      @else
        <p class="text-base font-bold tw-fg">All settled ✓</p>
      @endif
      @if($topSuppliers->isNotEmpty())
      <div class="mt-2 pt-2 space-y-1" style="border-top:1px solid var(--tw-border)">
        @foreach($topSuppliers as $s)
        <div class="flex justify-between text-xs">
          <span class="tw-fg truncate max-w-[60%]">{{ $s->name }}</span>
          <span class="font-semibold shrink-0" style="color:#ef4444">{{ number_format($s->balance, 2) }}</span>
        </div>
        @endforeach
      </div>
      @endif
    </a>
    @endif

    @if($dCan['depots.view'])
    <a href="{{ route('depots.index') }}"
       class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
             style="background:rgba(168,85,247,.10); border:1px solid rgba(168,85,247,.20)">
          <svg style="color:#a855f7;width:1rem;height:1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-xs font-bold tw-fg">Owed to Depots</p>
          <p class="text-[10px] tw-muted">storage & handling fees</p>
        </div>
        <svg class="w-3.5 h-3.5 tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      @if($depotByCurrency->isNotEmpty())
        @foreach($depotByCurrency as $cur => $total)
        <p class="text-xl font-black leading-none" style="color:#a855f7">
          {{ number_format($total, 2) }} <span class="text-xs" style="opacity:.7">{{ $cur }}</span>
        </p>
        @endforeach
      @else
        <p class="text-base font-bold tw-fg">All settled ✓</p>
      @endif
      @if($topDepots->isNotEmpty())
      <div class="mt-2 pt-2 space-y-1" style="border-top:1px solid var(--tw-border)">
        @foreach($topDepots as $d)
        <div class="flex justify-between text-xs">
          <span class="tw-fg truncate max-w-[60%]">{{ $d->name }}</span>
          <span class="font-semibold shrink-0" style="color:#a855f7">{{ number_format($d->balance, 2) }}</span>
        </div>
        @endforeach
      </div>
      @endif
    </a>
    @endif

    @if($dCan['transporters.view'])
    <a href="{{ route('transporters.index') }}"
       class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
             style="background:rgba(251,146,60,.10); border:1px solid rgba(251,146,60,.20)">
          <svg style="color:#fb923c;width:1rem;height:1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0121 11.414V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0M15 17a2 2 0 104 0"/>
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-xs font-bold tw-fg">Owed to Transporters</p>
          <p class="text-[10px] tw-muted">freight & delivery charges</p>
        </div>
        <svg class="w-3.5 h-3.5 tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      @if($byCurrency->isNotEmpty())
        @foreach($byCurrency as $cur => $amt)
        <p class="text-xl font-black leading-none" style="color:#fb923c">
          {{ number_format($amt, 2) }} <span class="text-xs" style="opacity:.7">{{ $cur }}</span>
        </p>
        @endforeach
      @else
        <p class="text-base font-bold tw-fg">All settled ✓</p>
      @endif
      @if($topTransporters->isNotEmpty())
      <div class="mt-2 pt-2 space-y-1" style="border-top:1px solid var(--tw-border)">
        @foreach($topTransporters as $t)
        <div class="flex justify-between text-xs">
          <span class="tw-fg truncate max-w-[60%]">{{ $t->name }}</span>
          <span class="font-semibold shrink-0" style="color:#fb923c">{{ number_format($t->balance, 2) }}</span>
        </div>
        @endforeach
      </div>
      @endif
    </a>
    @endif

  </div>

</div>
@endsection
