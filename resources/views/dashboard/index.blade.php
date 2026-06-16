@extends('layouts.app')
@section('title','Dashboard')
@section('content')

@php
  $greeting = match(true) {
    now()->hour < 12 => 'Good morning',
    now()->hour < 17 => 'Good afternoon',
    default          => 'Good evening',
  };
  $firstName = explode(' ', auth()->user()->name)[0];
  $netPositive = $netPosition >= 0;
@endphp

<div class="space-y-5">

  {{-- ══════════════════════════════════════════════════════════════
       ROW 1 — Welcome + Quick Actions
  ══════════════════════════════════════════════════════════════ --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h1 class="text-xl font-bold tw-fg">{{ $greeting }}, {{ $firstName }}</h1>
      <p class="text-xs tw-muted mt-0.5">
        {{ auth()->user()?->activeCompany?->name ?? config('app.name') }}
        &nbsp;·&nbsp;
        {{ now()->format('l, d F Y') }}
      </p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="{{ route('purchases.create') }}"
         class="h-9 px-3.5 rounded-xl border text-xs font-semibold tw-fg hover:tw-surface transition inline-flex items-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        New Purchase
      </a>
      <a href="{{ route('sales.index') }}"
         class="h-9 px-3.5 rounded-xl border text-xs font-semibold tw-fg hover:tw-surface transition inline-flex items-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        New Sale
      </a>
      <a href="{{ route('petty-cash.index') }}"
         class="h-9 px-3.5 rounded-xl border text-xs font-semibold tw-fg hover:tw-surface transition inline-flex items-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
        </svg>
        Petty Cash
      </a>
      <a href="{{ route('reports.index') }}"
         class="h-9 px-3.5 rounded-xl border text-xs font-semibold tw-fg hover:tw-surface transition inline-flex items-center gap-1.5"
         style="border-color:var(--tw-border); background:var(--tw-surface)">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
        </svg>
        Reports
      </a>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════════════
       ROW 2 — KPI Strip (6 cards)
  ══════════════════════════════════════════════════════════════ --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">

    {{-- Revenue MTD --}}
    <div class="tw-card rounded-2xl p-4 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-semibold uppercase tracking-widest tw-muted">Revenue MTD</span>
        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.2)">
          <svg class="w-3.5 h-3.5" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
          </svg>
        </div>
      </div>
      <div>
        <p class="text-xl font-bold tw-fg leading-none">
          {{ number_format($revenueMtd, 0) }}
          <span class="text-[11px] font-semibold tw-muted ml-0.5">{{ $baseCurrency }}</span>
        </p>
        <p class="text-[10px] tw-muted mt-1">{{ $salesCountMtd }} sale{{ $salesCountMtd === 1 ? '' : 's' }} this month</p>
      </div>
    </div>

    {{-- Gross Margin --}}
    <div class="tw-card rounded-2xl p-4 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-semibold uppercase tracking-widest tw-muted">Gross Margin</span>
        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(168,85,247,.12); border:1px solid rgba(168,85,247,.2)">
          <svg class="w-3.5 h-3.5" style="color:#a855f7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>
          </svg>
        </div>
      </div>
      <div>
        <p class="text-xl font-bold leading-none" style="color:#a855f7">{{ $grossMarginPct }}%</p>
        <p class="text-[10px] tw-muted mt-1">GP: {{ number_format($grossProfitMtd, 0) }} {{ $baseCurrency }}</p>
      </div>
    </div>

    {{-- Stock on Hand --}}
    <div class="tw-card rounded-2xl p-4 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-semibold uppercase tracking-widest tw-muted">Stock on Hand</span>
        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(14,165,233,.12); border:1px solid rgba(14,165,233,.2)">
          <svg class="w-3.5 h-3.5" style="color:#0ea5e9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
          </svg>
        </div>
      </div>
      <div>
        <p class="text-xl font-bold leading-none" style="color:#0ea5e9">{{ number_format($totalStockOnHand, 0) }}
          <span class="text-xs font-medium" style="color:#0ea5e9;opacity:.7">L</span>
        </p>
        <p class="text-[10px] tw-muted mt-1">{{ $depotStockRows->count() }} depot{{ $depotStockRows->count() === 1 ? '' : 's' }} with stock</p>
      </div>
    </div>

    {{-- Open Purchases --}}
    <div class="tw-card rounded-2xl p-4 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-semibold uppercase tracking-widest tw-muted">Open Purchases</span>
        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(245,158,11,.12); border:1px solid rgba(245,158,11,.2)">
          <svg class="w-3.5 h-3.5" style="color:#f59e0b" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
          </svg>
        </div>
      </div>
      <div>
        <p class="text-xl font-bold leading-none" style="color:#f59e0b">{{ number_format($openPurchasesCount) }}</p>
        <p class="text-[10px] tw-muted mt-1">in progress</p>
      </div>
    </div>

    {{-- Net Position (AR − AP) --}}
    <div class="tw-card rounded-2xl p-4 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-semibold uppercase tracking-widest tw-muted">Net Position</span>
        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:{{ $netPositive ? 'rgba(16,185,129,.12)' : 'rgba(239,68,68,.12)' }};
                    border:1px solid {{ $netPositive ? 'rgba(16,185,129,.2)' : 'rgba(239,68,68,.2)' }}">
          <svg class="w-3.5 h-3.5" style="color:{{ $netPositive ? '#10b981' : '#ef4444' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            @if($netPositive)
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m0 0l6.75-6.75M12 19.5l-6.75-6.75"/>
            @else
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75"/>
            @endif
          </svg>
        </div>
      </div>
      <div>
        <p class="text-xl font-bold leading-none" style="color:{{ $netPositive ? '#10b981' : '#ef4444' }}">
          {{ $netPositive ? '' : '–' }}{{ number_format(abs($netPosition), 0) }}
          <span class="text-[11px] font-semibold" style="color:{{ $netPositive ? '#10b981' : '#ef4444' }};opacity:.7">{{ $baseCurrency }}</span>
        </p>
        <p class="text-[10px] tw-muted mt-1">AR − AP</p>
      </div>
    </div>

    {{-- Petty Cash --}}
    <div class="tw-card rounded-2xl p-4 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-[10px] font-semibold uppercase tracking-widest tw-muted">Petty Cash</span>
        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(20,184,166,.12); border:1px solid rgba(20,184,166,.2)">
          <svg class="w-3.5 h-3.5" style="color:#14b8a6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
          </svg>
        </div>
      </div>
      <div>
        <p class="text-xl font-bold leading-none" style="color:#14b8a6">
          {{ number_format($pettyCashTotal, 0) }}
          <span class="text-[11px] font-semibold" style="color:#14b8a6;opacity:.7">{{ $baseCurrency }}</span>
        </p>
        <p class="text-[10px] tw-muted mt-1">current float</p>
      </div>
    </div>

  </div>

  {{-- ══════════════════════════════════════════════════════════════
       ROW 3 — Throughput Chart + Open Purchases
  ══════════════════════════════════════════════════════════════ --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Throughput Chart (2/3) --}}
    <div class="lg:col-span-2 tw-card rounded-2xl p-5">
      <div class="flex items-center justify-between mb-5">
        <div>
          <div class="text-sm font-semibold tw-fg">Purchased vs Sold</div>
          <div class="text-[10px] tw-muted mt-0.5">Last 6 months — litres</div>
        </div>
        <div class="flex items-center gap-4 text-[10px] tw-muted">
          <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-sm" style="background:#10b981"></span>
            Purchased
          </span>
          <span class="flex items-center gap-1.5">
            <span class="inline-block w-3 h-3 rounded-sm" style="background:#0ea5e9"></span>
            Sold
          </span>
        </div>
      </div>
      <div class="relative" style="height:200px">
        <canvas id="throughputChart"></canvas>
      </div>
    </div>

    {{-- Open Purchases Breakdown (1/3) --}}
    <div class="tw-card rounded-2xl p-5 flex flex-col">
      <div class="flex items-center justify-between mb-4">
        <div class="text-sm font-semibold tw-fg">Open Purchases</div>
        <a href="{{ route('purchases.index') }}"
           class="text-[10px] font-semibold underline underline-offset-2 tw-muted hover:tw-fg transition">
          View all →
        </a>
      </div>

      {{-- Big number --}}
      <div class="flex-1 flex flex-col items-center justify-center py-4">
        <div class="text-5xl font-black tw-fg">{{ $openPurchasesCount }}</div>
        <div class="text-xs tw-muted mt-1">orders in flight</div>
      </div>

      {{-- Status breakdown --}}
      @if($openByStatus->isNotEmpty())
      <div class="space-y-2 pt-4" style="border-top:1px solid var(--tw-border)">
        @foreach(['draft' => ['#94a3b8','Draft'], 'confirmed' => ['#10b981','Confirmed'], 'nominated' => ['#f59e0b','Nominated']] as $st => [$hex, $label])
          @if($openByStatus->has($st))
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $hex }}"></span>
              <span class="text-xs tw-muted">{{ $label }}</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-1.5 rounded-full overflow-hidden w-16" style="background:var(--tw-border)">
                <div class="h-full rounded-full" style="background:{{ $hex }};width:{{ $openPurchasesCount > 0 ? round($openByStatus[$st]/$openPurchasesCount*100) : 0 }}%"></div>
              </div>
              <span class="text-xs font-bold tw-fg w-4 text-right">{{ $openByStatus[$st] }}</span>
            </div>
          </div>
          @endif
        @endforeach
      </div>
      @else
      <p class="text-xs tw-muted text-center py-4">All orders are finalised.</p>
      @endif
    </div>

  </div>

  {{-- ══════════════════════════════════════════════════════════════
       ROW 4 — Financial Ledgers (3 equal columns)
  ══════════════════════════════════════════════════════════════ --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- Supplier AP --}}
    <a href="{{ route('suppliers.index') }}"
       class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background:rgba(239,68,68,.10); border:1px solid rgba(239,68,68,.20)">
          <svg class="w-4.5 h-4.5" style="color:#ef4444;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
        </div>
        <div>
          <div class="text-xs font-semibold tw-fg">Supplier Payables</div>
          <div class="text-[10px] tw-muted">Outstanding AP</div>
        </div>
        <svg class="w-4 h-4 ml-auto tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      <div class="mb-4">
        @if($supplierByCurrency->count() >= 1)
          @foreach($supplierByCurrency as $currency => $total)
            <div class="text-2xl font-black leading-none" style="color:#ef4444">
              {{ number_format($total, 2) }}
              <span class="text-sm font-semibold" style="color:#ef4444;opacity:.7">{{ $currency }}</span>
            </div>
          @endforeach
        @else
          <div class="text-xl font-black tw-fg">Settled</div>
        @endif
      </div>
      @if($topSuppliers->isNotEmpty())
        <div class="space-y-2 pt-3" style="border-top:1px solid var(--tw-border)">
          @foreach($topSuppliers as $s)
            <div class="flex items-center justify-between text-xs">
              <span class="tw-fg truncate max-w-[60%]">{{ $s->name }}</span>
              <span class="font-semibold shrink-0" style="color:#ef4444">
                {{ number_format($s->balance, 2) }}
                <span class="text-[10px] font-medium ml-0.5" style="color:#ef4444;opacity:.7">{{ $s->currency }}</span>
              </span>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-xs tw-muted pt-3" style="border-top:1px solid var(--tw-border)">All suppliers settled.</p>
      @endif
    </a>

    {{-- Depot Charges AP --}}
    <a href="{{ route('depots.index') }}"
       class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background:rgba(168,85,247,.10); border:1px solid rgba(168,85,247,.20)">
          <svg style="color:#a855f7;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
          </svg>
        </div>
        <div>
          <div class="text-xs font-semibold tw-fg">Depot Charges</div>
          <div class="text-[10px] tw-muted">Storage & throughput</div>
        </div>
        <svg class="w-4 h-4 ml-auto tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      <div class="mb-4">
        @if($depotByCurrency->count() >= 1)
          @foreach($depotByCurrency as $currency => $total)
            <div class="text-2xl font-black leading-none" style="color:#a855f7">
              {{ number_format($total, 2) }}
              <span class="text-sm font-semibold" style="color:#a855f7;opacity:.7">{{ $currency }}</span>
            </div>
          @endforeach
        @else
          <div class="text-xl font-black tw-fg">Settled</div>
        @endif
      </div>
      @if($topDepots->isNotEmpty())
        <div class="space-y-2 pt-3" style="border-top:1px solid var(--tw-border)">
          @foreach($topDepots as $d)
            <div class="flex items-center justify-between text-xs">
              <span class="tw-fg truncate max-w-[60%]">{{ $d->name }}</span>
              <span class="font-semibold shrink-0" style="color:#a855f7">
                {{ number_format($d->balance, 2) }}
                <span class="text-[10px] font-medium ml-0.5" style="color:#a855f7;opacity:.7">{{ $d->currency }}</span>
              </span>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-xs tw-muted pt-3" style="border-top:1px solid var(--tw-border)">No outstanding charges.</p>
      @endif
    </a>

    {{-- Client AR --}}
    <a href="{{ route('clients.index') }}"
       class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background:rgba(16,185,129,.10); border:1px solid rgba(16,185,129,.20)">
          <svg style="color:#10b981;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <div>
          <div class="text-xs font-semibold tw-fg">Client Receivables</div>
          <div class="text-[10px] tw-muted">Outstanding AR</div>
        </div>
        <svg class="w-4 h-4 ml-auto tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      <div class="mb-4">
        @if($clientARByCurrency->count() >= 1)
          @foreach($clientARByCurrency as $currency => $total)
            <div class="text-2xl font-black leading-none" style="color:#10b981">
              {{ number_format($total, 2) }}
              <span class="text-sm font-semibold" style="color:#10b981;opacity:.7">{{ $currency }}</span>
            </div>
          @endforeach
        @else
          <div class="text-xl font-black tw-fg">Settled</div>
        @endif
      </div>
      @if($topARClients->isNotEmpty())
        <div class="space-y-2 pt-3" style="border-top:1px solid var(--tw-border)">
          @foreach($topARClients as $c)
            <div class="flex items-center justify-between text-xs">
              <span class="tw-fg truncate max-w-[60%]">{{ $c->name }}</span>
              <span class="font-semibold shrink-0" style="color:#10b981">
                {{ number_format($c->balance, 2) }}
                <span class="text-[10px] font-medium ml-0.5" style="color:#10b981;opacity:.7">{{ $c->currency }}</span>
              </span>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-xs tw-muted pt-3" style="border-top:1px solid var(--tw-border)">All clients settled.</p>
      @endif
    </a>

  </div>

  {{-- ══════════════════════════════════════════════════════════════
       ROW 5 — Stock by Depot + Bank & Transporter
  ══════════════════════════════════════════════════════════════ --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    {{-- Stock by Depot --}}
    <a href="{{ route('depot-stock.index') }}"
       class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
               style="background:rgba(14,165,233,.10); border:1px solid rgba(14,165,233,.20)">
            <svg style="color:#0ea5e9;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
            </svg>
          </div>
          <div>
            <div class="text-sm font-semibold tw-fg">Stock by Depot</div>
            <div class="text-[10px] tw-muted">{{ number_format($totalStockOnHand, 0) }} L total on hand</div>
          </div>
        </div>
        <svg class="w-4 h-4 tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
      @if($depotStockRows->isNotEmpty())
        <div class="space-y-2.5">
          @foreach($depotStockRows as $row)
            @php $pct = $totalStockOnHand > 0 ? ($row->total_qty / $totalStockOnHand * 100) : 0; @endphp
            <div>
              <div class="flex items-center justify-between text-xs mb-1">
                <span class="tw-fg font-medium truncate max-w-[60%]">{{ $row->depot_name }}</span>
                <span class="font-bold shrink-0" style="color:#0ea5e9">
                  {{ number_format($row->total_qty, 0) }}
                  <span class="text-[10px] font-medium" style="color:#0ea5e9;opacity:.7">L</span>
                </span>
              </div>
              <div class="h-1.5 rounded-full overflow-hidden" style="background:var(--tw-border)">
                <div class="h-full rounded-full transition-all" style="width:{{ round($pct) }}%; background:#0ea5e9"></div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <p class="text-xs tw-muted">No stock on hand — all depots are empty.</p>
      @endif
    </a>

    {{-- Bank Balances + Transporter Payables --}}
    <div class="space-y-4">

      {{-- Bank Balances --}}
      <a href="{{ route('banks.index') }}"
         class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:rgba(20,184,166,.10); border:1px solid rgba(20,184,166,.20)">
              <svg style="color:#14b8a6;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5v2H3v-2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12v7M9 12v7M15 12v7M19 12v7"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 19h18"/>
              </svg>
            </div>
            <div>
              <div class="text-sm font-semibold tw-fg">Bank Balances</div>
              @if($bankByCurrency->isNotEmpty())
                @foreach($bankByCurrency as $cur => $amt)
                  <div class="text-[10px] tw-muted">{{ number_format(abs($amt), 2) }} {{ $cur }}{{ $amt < 0 ? ' DR' : '' }}</div>
                @endforeach
              @else
                <div class="text-[10px] tw-muted">No accounts added</div>
              @endif
            </div>
          </div>
          <svg class="w-4 h-4 tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
          </svg>
        </div>
        @if($topBankAccounts->isNotEmpty())
          <div class="space-y-2">
            @foreach($topBankAccounts as $ba)
              <div class="flex items-center justify-between text-xs">
                <span class="tw-fg truncate max-w-[55%]">{{ $ba->name }}</span>
                <span class="font-semibold shrink-0" style="color:{{ $ba->balance >= 0 ? '#14b8a6' : '#ef4444' }}">
                  {{ $ba->balance < 0 ? '(' : '' }}{{ number_format(abs($ba->balance), 2) }}{{ $ba->balance < 0 ? ')' : '' }}
                  <span class="text-[10px] font-medium ml-0.5" style="opacity:.7">{{ $ba->currency }}</span>
                </span>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-xs tw-muted">Add bank accounts to track balances.</p>
        @endif
      </a>

      {{-- Transporter Payables --}}
      <a href="{{ route('transporters.index') }}"
         class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:rgba(251,146,60,.10); border:1px solid rgba(251,146,60,.20)">
              <svg style="color:#fb923c;width:1.1rem;height:1.1rem" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414A1 1 0 0121 11.414V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0M15 17a2 2 0 104 0"/>
              </svg>
            </div>
            <div>
              <div class="text-sm font-semibold tw-fg">Transporter Payables</div>
              @if($byCurrency->isNotEmpty())
                @foreach($byCurrency as $cur => $amt)
                  <div class="text-[10px] tw-muted">{{ number_format($amt, 2) }} {{ $cur }}</div>
                @endforeach
              @else
                <div class="text-[10px] tw-muted">All settled</div>
              @endif
            </div>
          </div>
          <svg class="w-4 h-4 tw-muted opacity-40 group-hover:opacity-80 transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
          </svg>
        </div>
        @if($topTransporters->isNotEmpty())
          <div class="space-y-2">
            @foreach($topTransporters as $t)
              <div class="flex items-center justify-between text-xs">
                <span class="tw-fg truncate max-w-[55%]">{{ $t->name }}</span>
                <span class="font-semibold shrink-0" style="color:#fb923c">
                  {{ number_format($t->balance, 2) }}
                  <span class="text-[10px] font-medium ml-0.5" style="color:#fb923c;opacity:.7">{{ $t->currency }}</span>
                </span>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-xs tw-muted">No outstanding transporter balances.</p>
        @endif
      </a>

    </div>
  </div>

</div>

@push('scripts')
<script>
(function () {
  const labels    = @json($chartLabels);
  const purchased = @json($chartPurchased);
  const sold      = @json($chartSold);

  function renderChart() {
    const canvas = document.getElementById('throughputChart');
    if (!canvas) return;

    const dpr  = window.devicePixelRatio || 1;
    const rect = canvas.parentElement.getBoundingClientRect();
    const W    = rect.width;
    const H    = rect.height || 200;

    canvas.width  = W * dpr;
    canvas.height = H * dpr;
    canvas.style.width  = W + 'px';
    canvas.style.height = H + 'px';

    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    const isDark   = document.documentElement.classList.contains('dark');
    const gridCol  = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const labelCol = isDark ? 'rgba(255,255,255,0.38)' : 'rgba(0,0,0,0.38)';
    const valCol   = isDark ? 'rgba(255,255,255,0.70)' : 'rgba(0,0,0,0.60)';

    const padT = 16, padB = 28, padL = 54, padR = 16;
    const chartW = W - padL - padR;
    const chartH = H - padT - padB;

    const allVals = [...purchased, ...sold];
    const maxVal  = Math.max(...allVals, 1);

    // Grid lines (4)
    ctx.font = '10px system-ui, sans-serif';
    ctx.textAlign = 'right';
    for (let i = 0; i <= 4; i++) {
      const y = padT + chartH - (i / 4) * chartH;
      ctx.beginPath();
      ctx.strokeStyle = gridCol;
      ctx.lineWidth = 1;
      ctx.setLineDash([4, 4]);
      ctx.moveTo(padL, y);
      ctx.lineTo(W - padR, y);
      ctx.stroke();
      ctx.setLineDash([]);
      ctx.fillStyle = labelCol;
      const v = Math.round((maxVal * i / 4) / 1000);
      ctx.fillText(v + 'K', padL - 6, y + 3.5);
    }

    const n       = labels.length;
    const groupW  = chartW / n;
    const barW    = Math.max(6, Math.min(22, groupW * 0.28));
    const gap     = 3;

    labels.forEach((label, i) => {
      const groupX = padL + i * groupW + groupW / 2;

      const drawBar = (val, offsetX, color) => {
        const barH = chartH * (val / maxVal);
        const x = groupX + offsetX - barW / 2;
        const y = padT + chartH - barH;
        const r = Math.min(4, barW / 2);

        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + barW - r, y);
        ctx.quadraticCurveTo(x + barW, y, x + barW, y + r);
        ctx.lineTo(x + barW, y + barH);
        ctx.lineTo(x, y + barH);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
        ctx.fillStyle = color;
        ctx.fill();
      };

      const offset = barW / 2 + gap / 2;
      drawBar(purchased[i] || 0, -offset, '#10b981');
      drawBar(sold[i] || 0,      +offset, '#0ea5e9');

      // X label
      ctx.textAlign = 'center';
      ctx.fillStyle = labelCol;
      ctx.font = '10px system-ui, sans-serif';
      ctx.fillText(label, groupX, H - padB + 16);
    });
  }

  // Render on load and on theme toggle
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderChart);
  } else {
    renderChart();
  }

  // Re-render on resize
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(renderChart, 120);
  });

  // Re-render on theme change (watch html class)
  const obs = new MutationObserver(renderChart);
  obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
</script>
@endpush

@endsection
