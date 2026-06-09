@extends('layouts.app')
@section('title','Summary')
@section('content')

<div class="space-y-6 max-w-4xl">

  {{-- ── Quick Actions ────────────────────────────────────────── --}}
  <section>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
          <a href="{{ route('purchases.create') }}"
             class="tw-card group rounded-xl p-3 flex items-center gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                   style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.2)">
                  <svg class="w-4 h-4" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                  </svg>
              </div>
              <span class="text-xs font-semibold tw-fg group-hover:text-[color:var(--tw-accent)] transition">New Purchase</span>
          </a>
          <a href="{{ route('sales.index') }}"
             class="tw-card group rounded-xl p-3 flex items-center gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                   style="background:rgba(14,165,233,.12); border:1px solid rgba(14,165,233,.2)">
                  <svg class="w-4 h-4" style="color:#0ea5e9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                  </svg>
              </div>
              <span class="text-xs font-semibold tw-fg group-hover:text-[color:var(--tw-accent)] transition">New Sale</span>
          </a>
          <a href="{{ route('petty-cash.index') }}"
             class="tw-card group rounded-xl p-3 flex items-center gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                   style="background:rgba(245,158,11,.12); border:1px solid rgba(245,158,11,.2)">
                  <svg class="w-4 h-4" style="color:#f59e0b" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                  </svg>
              </div>
              <span class="text-xs font-semibold tw-fg group-hover:text-[color:var(--tw-accent)] transition">Petty Cash</span>
          </a>
          <a href="{{ route('reports.index') }}"
             class="tw-card group rounded-xl p-3 flex items-center gap-2.5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                   style="background:rgba(168,85,247,.12); border:1px solid rgba(168,85,247,.2)">
                  <svg class="w-4 h-4" style="color:#a855f7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                  </svg>
              </div>
              <span class="text-xs font-semibold tw-fg group-hover:text-[color:var(--tw-accent)] transition">Reports</span>
          </a>
      </div>
  </section>

  {{-- Welcome banner --}}
  <div class="tw-card rounded-2xl px-6 py-5 flex items-center gap-4">
      <div class="h-10 w-10 rounded-xl flex-shrink-0 flex items-center justify-center"
           style="background:var(--tw-accent-soft); border:1px solid rgba(16,185,129,.3)">
          <svg class="w-5 h-5" style="color:var(--tw-accent)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
      </div>
      <div>
          <h1 class="text-base font-bold tw-fg">Welcome to Twins</h1>
          <p class="text-xs tw-muted mt-0.5">Here's a quick snapshot of what needs your attention today.</p>
      </div>
  </div>

  {{-- ── At a Glance ──────────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest tw-muted mb-3">At a Glance</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          {{-- Open Purchases --}}
          <a href="{{ route('purchases.index') }}"
             class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
              <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3 min-w-0">
                      <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                           style="background:rgba(16,185,129,.10); border:1px solid rgba(16,185,129,.20)">
                          <svg class="w-5 h-5" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                          </svg>
                      </div>
                      <div class="min-w-0">
                          <p class="text-xs tw-muted mb-1">Open Purchases</p>
                          <p class="text-2xl font-bold" style="color:#10b981">{{ number_format($openPurchasesCount) }}</p>
                      </div>
                  </div>
                  <svg class="w-4 h-4 flex-shrink-0 ml-3 tw-muted opacity-40 group-hover:opacity-80 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                  </svg>
              </div>
              @if($openByStatus->isNotEmpty())
                  <div class="mt-4 pt-4 flex flex-wrap gap-x-4 gap-y-1" style="border-top:1px solid var(--tw-border)">
                      @foreach(['draft' => '#94a3b8', 'confirmed' => '#10b981', 'nominated' => '#f59e0b'] as $st => $hex)
                          @if($openByStatus->has($st))
                              <div class="flex items-center gap-1.5 text-sm">
                                  <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $hex }}"></span>
                                  <span class="tw-muted capitalize">{{ $st }}</span>
                                  <span class="font-semibold tw-fg">{{ $openByStatus[$st] }}</span>
                              </div>
                          @endif
                      @endforeach
                  </div>
              @else
                  <p class="mt-3 text-xs tw-muted">No open purchases — all orders are finalised.</p>
              @endif
          </a>

          {{-- Stock on Hand --}}
          <a href="{{ route('depot-stock.index') }}"
             class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
              <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3 min-w-0">
                      <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                           style="background:rgba(14,165,233,.10); border:1px solid rgba(14,165,233,.20)">
                          <svg class="w-5 h-5" style="color:#0ea5e9" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
                          </svg>
                      </div>
                      <div class="min-w-0">
                          <p class="text-xs tw-muted mb-1">Stock on Hand</p>
                          <p class="text-2xl font-bold" style="color:#0ea5e9">{{ number_format($totalStockOnHand, 0) }} <span class="text-sm font-semibold" style="color:#0ea5e9;opacity:.7">L</span></p>
                      </div>
                  </div>
                  <svg class="w-4 h-4 flex-shrink-0 ml-3 tw-muted opacity-40 group-hover:opacity-80 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                  </svg>
              </div>
              @if($depotStockRows->isNotEmpty())
                  <div class="mt-4 pt-4 space-y-2" style="border-top:1px solid var(--tw-border)">
                      @foreach($depotStockRows as $row)
                          <div class="flex items-center justify-between text-sm">
                              <span class="tw-fg truncate max-w-[60%]">{{ $row->depot_name }}</span>
                              <span class="font-semibold" style="color:#0ea5e9">
                                  {{ number_format($row->total_qty, 0) }}
                                  <span class="text-xs font-medium ml-0.5" style="color:#0ea5e9;opacity:.7">L</span>
                              </span>
                          </div>
                      @endforeach
                  </div>
              @else
                  <p class="mt-3 text-xs tw-muted">No stock on hand — depots are empty.</p>
              @endif
          </a>

      </div>
  </section>

  {{-- ── Supplier Payables ────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest tw-muted mb-3">Supplier Payables</h2>
      <a href="{{ route('suppliers.index') }}"
         class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
          <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                       style="background:rgba(239,68,68,.10); border:1px solid rgba(239,68,68,.20)">
                      <svg class="w-5 h-5" style="color:#ef4444" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                      </svg>
                  </div>
                  <div class="min-w-0">
                      <p class="text-xs tw-muted mb-1">Outstanding Supplier Payables</p>
                      @if($supplierByCurrency->count() >= 1)
                          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                              @foreach($supplierByCurrency as $currency => $total)
                                  @if(!$loop->first)<span class="tw-muted text-lg leading-none">·</span>@endif
                                  <span class="text-2xl font-bold leading-none" style="color:#ef4444">
                                      {{ number_format($total, 2) }}<span class="text-sm font-semibold ml-1" style="color:#ef4444;opacity:.7">{{ $currency }}</span>
                                  </span>
                              @endforeach
                          </div>
                      @else
                          <p class="text-2xl font-bold tw-fg">$0.00</p>
                      @endif
                  </div>
              </div>
              <svg class="w-4 h-4 flex-shrink-0 ml-3 tw-muted opacity-40 group-hover:opacity-80 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
              </svg>
          </div>
          @if($topSuppliers->isNotEmpty())
              <div class="mt-4 pt-4 space-y-2" style="border-top:1px solid var(--tw-border)">
                  @foreach($topSuppliers as $s)
                      <div class="flex items-center justify-between text-sm">
                          <span class="tw-fg truncate max-w-[60%]">{{ $s->name }}</span>
                          <span class="font-semibold" style="color:#ef4444">
                              {{ number_format($s->balance, 2) }}
                              <span class="text-xs font-medium ml-0.5" style="color:#ef4444;opacity:.7">{{ $s->currency }}</span>
                          </span>
                      </div>
                  @endforeach
                  @if($topSuppliers->count() >= 3)
                      <p class="text-xs tw-muted pt-1">Showing top 3 — <a href="{{ route('suppliers.index') }}" class="underline underline-offset-2">view all</a></p>
                  @endif
              </div>
          @else
              <p class="mt-3 text-xs tw-muted">No outstanding balances — all suppliers are settled.</p>
          @endif
      </a>
  </section>

  {{-- ── Depot Payables ───────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest tw-muted mb-3">Depot Payables</h2>
      <a href="{{ route('depots.index') }}"
         class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
          <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                       style="background:rgba(168,85,247,.10); border:1px solid rgba(168,85,247,.20)">
                      <svg class="w-5 h-5" style="color:#a855f7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
                      </svg>
                  </div>
                  <div class="min-w-0">
                      <p class="text-xs tw-muted mb-1">Outstanding Depot Charges</p>
                      @if($depotByCurrency->count() >= 1)
                          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                              @foreach($depotByCurrency as $currency => $total)
                                  @if(!$loop->first)<span class="tw-muted text-lg leading-none">·</span>@endif
                                  <span class="text-2xl font-bold leading-none" style="color:#a855f7">
                                      {{ number_format($total, 2) }}<span class="text-sm font-semibold ml-1" style="color:#a855f7;opacity:.7">{{ $currency }}</span>
                                  </span>
                              @endforeach
                          </div>
                      @else
                          <p class="text-2xl font-bold tw-fg">$0.00</p>
                      @endif
                  </div>
              </div>
              <svg class="w-4 h-4 flex-shrink-0 ml-3 tw-muted opacity-40 group-hover:opacity-80 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
              </svg>
          </div>
          @if($topDepots->isNotEmpty())
              <div class="mt-4 pt-4 space-y-2" style="border-top:1px solid var(--tw-border)">
                  @foreach($topDepots as $d)
                      <div class="flex items-center justify-between text-sm">
                          <span class="tw-fg truncate max-w-[60%]">{{ $d->name }}</span>
                          <span class="font-semibold" style="color:#a855f7">
                              {{ number_format($d->balance, 2) }}
                              <span class="text-xs font-medium ml-0.5" style="color:#a855f7;opacity:.7">{{ $d->currency }}</span>
                          </span>
                      </div>
                  @endforeach
                  @if($topDepots->count() >= 3)
                      <p class="text-xs tw-muted pt-1">Showing top 3 — <a href="{{ route('depots.index') }}" class="underline underline-offset-2">view all</a></p>
                  @endif
              </div>
          @else
              <p class="mt-3 text-xs tw-muted">No outstanding charges — all depots are settled.</p>
          @endif
      </a>
  </section>

  {{-- ── Accounts Receivable ─────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest tw-muted mb-3">Accounts Receivable</h2>
      <a href="{{ route('clients.index') }}"
         class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
          <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                       style="background:rgba(16,185,129,.10); border:1px solid rgba(16,185,129,.20)">
                      <svg class="w-5 h-5" style="color:#10b981" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                  </div>
                  <div class="min-w-0">
                      <p class="text-xs tw-muted mb-1">Outstanding Client Receivables</p>
                      @if($clientARByCurrency->count() >= 1)
                          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                              @foreach($clientARByCurrency as $currency => $total)
                                  @if(!$loop->first)<span class="tw-muted text-lg leading-none">·</span>@endif
                                  <span class="text-2xl font-bold leading-none" style="color:#10b981">
                                      {{ number_format($total, 2) }}<span class="text-sm font-semibold ml-1" style="color:#10b981;opacity:.7">{{ $currency }}</span>
                                  </span>
                              @endforeach
                          </div>
                      @else
                          <p class="text-2xl font-bold tw-fg">$0.00</p>
                      @endif
                  </div>
              </div>
              <svg class="w-4 h-4 flex-shrink-0 ml-3 tw-muted opacity-40 group-hover:opacity-80 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
              </svg>
          </div>
          @if($topARClients->isNotEmpty())
              <div class="mt-4 pt-4 space-y-2" style="border-top:1px solid var(--tw-border)">
                  @foreach($topARClients as $c)
                      <div class="flex items-center justify-between text-sm">
                          <span class="tw-fg truncate max-w-[60%]">{{ $c->name }}</span>
                          <span class="font-semibold" style="color:#10b981">
                              {{ number_format($c->balance, 2) }}
                              <span class="text-xs font-medium ml-0.5" style="color:#10b981;opacity:.7">{{ $c->currency }}</span>
                          </span>
                      </div>
                  @endforeach
                  @if($topARClients->count() >= 3)
                      <p class="text-xs tw-muted pt-1">Showing top 3 — <a href="{{ route('clients.index') }}" class="underline underline-offset-2">view all</a></p>
                  @endif
              </div>
          @else
              <p class="mt-3 text-xs tw-muted">No outstanding receivables — all clients are settled.</p>
          @endif
      </a>
  </section>

  {{-- ── Throughput Chart ─────────────────────────────────────── --}}
  @php
      $chartLabels    = json_encode($chartLabels ?? []);
      $chartPurchased = json_encode($chartPurchased ?? []);
      $chartSold      = json_encode($chartSold ?? []);
  @endphp
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest tw-muted mb-3">Volume Throughput — Last 6 Months</h2>
      <div class="tw-card rounded-2xl p-5">
          <div class="flex items-center gap-4 mb-4 flex-wrap">
              <div class="flex items-center gap-1.5 text-xs tw-muted">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background:rgba(14,165,233,.7)"></span>
                  Purchased
              </div>
              <div class="flex items-center gap-1.5 text-xs tw-muted">
                  <span class="w-3 h-3 rounded-sm flex-shrink-0" style="background:rgba(16,185,129,.7)"></span>
                  Sold
              </div>
              <a href="{{ route('reports.throughput') }}" class="ml-auto text-xs tw-muted hover:underline underline-offset-2">
                  Full report →
              </a>
          </div>
          <div style="height:220px; position:relative">
              <canvas id="dashThroughputChart"></canvas>
          </div>
      </div>
  </section>

  {{-- ── AP / AR Summary ──────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest tw-muted mb-3">Financial Position</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {{-- AP total --}}
          <div class="tw-card rounded-2xl p-5">
              <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-semibold tw-muted">Total Payables (AP)</span>
                  <a href="{{ route('suppliers.index') }}" class="text-xs tw-muted hover:underline">View suppliers →</a>
              </div>
              <div class="text-3xl font-bold" style="color:#ef4444">
                  {{ number_format($totalAP ?? 0, 2) }}
              </div>
              <div class="mt-3 space-y-1.5">
                  @if($supplierPayableTotal > 0)
                  <div class="flex items-center justify-between text-xs">
                      <span class="tw-muted">Supplier</span>
                      <span class="font-semibold" style="color:#ef4444">{{ number_format($supplierPayableTotal, 2) }}</span>
                  </div>
                  @endif
                  @if($depotPayableTotal > 0)
                  <div class="flex items-center justify-between text-xs">
                      <span class="tw-muted">Depot charges</span>
                      <span class="font-semibold" style="color:#a855f7">{{ number_format($depotPayableTotal, 2) }}</span>
                  </div>
                  @endif
                  @if($byCurrency->sum() > 0)
                  <div class="flex items-center justify-between text-xs">
                      <span class="tw-muted">Freight</span>
                      <span class="font-semibold" style="color:#f59e0b">{{ number_format($byCurrency->sum(), 2) }}</span>
                  </div>
                  @endif
              </div>
          </div>
          {{-- AR total --}}
          <div class="tw-card rounded-2xl p-5">
              <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-semibold tw-muted">Total Receivables (AR)</span>
                  <a href="{{ route('reports.ar-aging') }}" class="text-xs tw-muted hover:underline">AR aging →</a>
              </div>
              <div class="text-3xl font-bold" style="color:#10b981">
                  {{ number_format($totalAR, 2) }}
              </div>
              @if($topARClients->isNotEmpty())
              <div class="mt-3 space-y-1.5">
                  @foreach($topARClients as $c)
                  <div class="flex items-center justify-between text-xs">
                      <span class="tw-fg truncate max-w-[60%]">{{ $c->name }}</span>
                      <span class="font-semibold" style="color:#10b981">{{ number_format($c->balance, 2) }} <span class="opacity-60">{{ $c->currency }}</span></span>
                  </div>
                  @endforeach
              </div>
              @else
              <p class="mt-3 text-xs tw-muted">No outstanding receivables.</p>
              @endif
          </div>
      </div>
  </section>

  {{-- ── Freight Payables ─────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest tw-muted mb-3">Freight Payables</h2>
      <a href="{{ route('transporters.index') }}"
         class="tw-card group block rounded-2xl p-5 hover:-translate-y-0.5 transition-transform duration-150">
          <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                       style="background:rgba(245,158,11,.10); border:1px solid rgba(245,158,11,.20)">
                      <svg class="w-5 h-5" style="color:#f59e0b" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.9 17.9 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                      </svg>
                  </div>
                  <div class="min-w-0">
                      <p class="text-xs tw-muted mb-1">Outstanding Freight Payables</p>
                      @if($byCurrency->count() >= 1)
                          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                              @foreach($byCurrency as $currency => $total)
                                  @if(!$loop->first)<span class="tw-muted text-lg leading-none">·</span>@endif
                                  <span class="text-2xl font-bold leading-none" style="color:#f59e0b">
                                      {{ number_format($total, 2) }}<span class="text-sm font-semibold ml-1" style="color:#f59e0b;opacity:.7">{{ $currency }}</span>
                                  </span>
                              @endforeach
                          </div>
                      @else
                          <p class="text-2xl font-bold tw-fg">$0.00</p>
                      @endif
                  </div>
              </div>
              <svg class="w-4 h-4 flex-shrink-0 ml-3 tw-muted opacity-40 group-hover:opacity-80 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
              </svg>
          </div>
          @if($topTransporters->isNotEmpty())
              <div class="mt-4 pt-4 space-y-2" style="border-top:1px solid var(--tw-border)">
                  @foreach($topTransporters as $t)
                      <div class="flex items-center justify-between text-sm">
                          <span class="tw-fg truncate max-w-[60%]">{{ $t->name }}</span>
                          <span class="font-semibold" style="color:#f59e0b">
                              {{ number_format($t->balance, 2) }}
                              <span class="text-xs font-medium ml-0.5" style="color:#f59e0b;opacity:.7">{{ $t->currency }}</span>
                          </span>
                      </div>
                  @endforeach
                  @if($topTransporters->count() >= 3)
                      <p class="text-xs tw-muted pt-1">Showing top 3 — <a href="{{ route('transporters.index') }}" class="underline underline-offset-2">view all</a></p>
                  @endif
              </div>
          @else
              <p class="mt-3 text-xs tw-muted">No outstanding balances — all transporters are settled.</p>
          @endif
      </a>
  </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const isDark    = document.documentElement.dataset.theme === 'dark' || window.matchMedia('(prefers-color-scheme: dark)').matches;
    const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.05)';
    const textColor = isDark ? 'rgba(255,255,255,.35)' : 'rgba(0,0,0,.35)';

    Chart.defaults.font.family = 'inherit';
    Chart.defaults.font.size   = 11;
    Chart.defaults.color       = textColor;

    const labels    = {!! json_encode($chartLabels ?? []) !!};
    const purchased = {!! json_encode($chartPurchased ?? []) !!};
    const sold      = {!! json_encode($chartSold ?? []) !!};

    if (!labels.length) return;

    new Chart(document.getElementById('dashThroughputChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Purchased (L)',
                    data: purchased,
                    backgroundColor: 'rgba(14,165,233,.3)',
                    borderColor: 'rgba(14,165,233,.85)',
                    borderWidth: 1.5,
                    borderRadius: 5,
                    order: 2,
                },
                {
                    label: 'Sold (L)',
                    data: sold,
                    backgroundColor: 'rgba(16,185,129,.3)',
                    borderColor: 'rgba(16,185,129,.85)',
                    borderWidth: 1.5,
                    borderRadius: 5,
                    order: 1,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString() + ' L'
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor },
                },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor,
                        callback: v => v >= 1000 ? (v/1000).toFixed(0) + 'k' : v
                    },
                    beginAtZero: true,
                }
            }
        }
    });
})();
</script>

@endsection
