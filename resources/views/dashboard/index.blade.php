@extends('layouts.app')
@section('title','Summary')
@section('content')

@php
    $card   = "tw-card rounded-2xl p-5";
    $border = "border-[color:var(--tw-border)]";
    $fg     = "text-[color:var(--tw-fg)]";
    $muted  = "text-[color:var(--tw-muted)]";
    $label  = "text-xs $muted mb-1";
    $div    = "mt-4 pt-4 border-t $border space-y-2";
    $rowL   = "flex items-center justify-between text-sm";
    $chevron = '<svg class="w-4 h-4 flex-shrink-0 ml-3 opacity-40 group-hover:opacity-80 transition" style="color:var(--tw-muted)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';
@endphp

<div class="space-y-6">

  {{-- Welcome banner --}}
  <div class="tw-card rounded-2xl px-6 py-5 flex items-center gap-4">
      <div class="h-10 w-10 rounded-xl flex-shrink-0 flex items-center justify-center
                  bg-[color:var(--tw-accent-soft)] border border-[color:rgba(16,185,129,.3)]">
          <svg class="w-5 h-5 text-[color:var(--tw-accent)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
      </div>
      <div>
          <h1 class="text-base font-bold {{ $fg }}">Welcome to Twins</h1>
          <p class="text-xs {{ $muted }} mt-0.5">Here's a quick snapshot of what needs your attention today.</p>
      </div>
  </div>

  {{-- ── At a Glance ──────────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest {{ $muted }} mb-3">At a Glance</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          {{-- Open Purchases --}}
          <a href="{{ route('purchases.index') }}"
             class="group {{ $card }} hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
              <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3 min-w-0">
                      <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center flex-shrink-0
                                  border border-emerald-500/20">
                          <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                          </svg>
                      </div>
                      <div class="min-w-0">
                          <p class="{{ $label }}">Open Purchases</p>
                          <p class="text-2xl font-bold text-emerald-500">{{ number_format($openPurchasesCount) }}</p>
                      </div>
                  </div>
                  {!! $chevron !!}
              </div>
              @if($openByStatus->isNotEmpty())
                  <div class="{{ $div }} flex flex-wrap gap-x-4 gap-y-1 !space-y-0">
                      @foreach(['draft' => 'slate', 'confirmed' => 'emerald', 'nominated' => 'amber'] as $st => $color)
                          @if($openByStatus->has($st))
                              <div class="flex items-center gap-1.5 text-sm">
                                  <span class="w-2 h-2 rounded-full bg-{{ $color }}-400 flex-shrink-0"></span>
                                  <span class="{{ $muted }} capitalize">{{ $st }}</span>
                                  <span class="font-semibold {{ $fg }}">{{ $openByStatus[$st] }}</span>
                              </div>
                          @endif
                      @endforeach
                  </div>
              @else
                  <p class="mt-3 text-xs {{ $muted }}">No open purchases — all orders are finalised.</p>
              @endif
          </a>

          {{-- Stock on Hand --}}
          <a href="{{ route('depot-stock.index') }}"
             class="group {{ $card }} hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
              <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3 min-w-0">
                      <div class="w-10 h-10 rounded-xl bg-sky-500/10 flex items-center justify-center flex-shrink-0
                                  border border-sky-500/20">
                          <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/>
                          </svg>
                      </div>
                      <div class="min-w-0">
                          <p class="{{ $label }}">Stock on Hand</p>
                          <p class="text-2xl font-bold text-sky-500">{{ number_format($totalStockOnHand, 0) }} <span class="text-sm font-semibold text-sky-500/70">L</span></p>
                      </div>
                  </div>
                  {!! $chevron !!}
              </div>
              @if($depotStockRows->isNotEmpty())
                  <div class="{{ $div }}">
                      @foreach($depotStockRows as $row)
                          <div class="{{ $rowL }}">
                              <span class="{{ $fg }} truncate max-w-[60%]">{{ $row->depot_name }}</span>
                              <span class="font-semibold text-sky-500">
                                  {{ number_format($row->total_qty, 0) }}
                                  <span class="text-xs font-medium text-sky-500/70 ml-0.5">L</span>
                              </span>
                          </div>
                      @endforeach
                  </div>
              @else
                  <p class="mt-3 text-xs {{ $muted }}">No stock on hand — depots are empty.</p>
              @endif
          </a>

      </div>
  </section>

  {{-- ── Supplier Payables ────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest {{ $muted }} mb-3">Supplier Payables</h2>
      <a href="{{ route('suppliers.index') }}"
         class="group {{ $card }} hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
          <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center flex-shrink-0
                              border border-rose-500/20">
                      <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                      </svg>
                  </div>
                  <div class="min-w-0">
                      <p class="{{ $label }}">Outstanding Supplier Payables</p>
                      @if($supplierByCurrency->count() === 1)
                          <p class="text-2xl font-bold text-rose-500">${{ number_format($supplierByCurrency->first(), 2) }}</p>
                      @elseif($supplierByCurrency->count() > 1)
                          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                              @foreach($supplierByCurrency as $currency => $total)
                                  @if(!$loop->first)<span class="{{ $muted }} text-lg leading-none">·</span>@endif
                                  <span class="text-2xl font-bold text-rose-500 leading-none">
                                      {{ number_format($total, 2) }}<span class="text-sm font-semibold text-rose-500/70 ml-1">{{ $currency }}</span>
                                  </span>
                              @endforeach
                          </div>
                      @else
                          <p class="text-2xl font-bold {{ $fg }}">$0.00</p>
                      @endif
                  </div>
              </div>
              {!! $chevron !!}
          </div>
          @if($topSuppliers->isNotEmpty())
              <div class="{{ $div }}">
                  @foreach($topSuppliers as $s)
                      <div class="{{ $rowL }}">
                          <span class="{{ $fg }} truncate max-w-[60%]">{{ $s->name }}</span>
                          <span class="font-semibold text-rose-500">
                              {{ number_format($s->balance, 2) }}
                              <span class="text-xs font-medium text-rose-500/70 ml-0.5">{{ $s->currency }}</span>
                          </span>
                      </div>
                  @endforeach
                  @if($topSuppliers->count() >= 3)
                      <p class="text-xs {{ $muted }} pt-1">Showing top 3 — <span class="underline underline-offset-2">view all</span></p>
                  @endif
              </div>
          @else
              <p class="mt-3 text-xs {{ $muted }}">No outstanding balances — all suppliers are settled.</p>
          @endif
      </a>
  </section>

  {{-- ── Depot Payables ───────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest {{ $muted }} mb-3">Depot Payables</h2>
      <a href="{{ route('depots.index') }}"
         class="group {{ $card }} hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
          <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center flex-shrink-0
                              border border-purple-500/20">
                      <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-5 9 5-9 5-9-5z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10v9l9 5 9-5v-9"/>
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v9"/>
                      </svg>
                  </div>
                  <div class="min-w-0">
                      <p class="{{ $label }}">Outstanding Depot Charges</p>
                      @if($depotByCurrency->count() === 1)
                          <p class="text-2xl font-bold text-purple-500">${{ number_format($depotByCurrency->first(), 2) }}</p>
                      @elseif($depotByCurrency->count() > 1)
                          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                              @foreach($depotByCurrency as $currency => $total)
                                  @if(!$loop->first)<span class="{{ $muted }} text-lg leading-none">·</span>@endif
                                  <span class="text-2xl font-bold text-purple-500 leading-none">
                                      {{ number_format($total, 2) }}<span class="text-sm font-semibold text-purple-500/70 ml-1">{{ $currency }}</span>
                                  </span>
                              @endforeach
                          </div>
                      @else
                          <p class="text-2xl font-bold {{ $fg }}">$0.00</p>
                      @endif
                  </div>
              </div>
              {!! $chevron !!}
          </div>
          @if($topDepots->isNotEmpty())
              <div class="{{ $div }}">
                  @foreach($topDepots as $d)
                      <div class="{{ $rowL }}">
                          <span class="{{ $fg }} truncate max-w-[60%]">{{ $d->name }}</span>
                          <span class="font-semibold text-purple-500">
                              {{ number_format($d->balance, 2) }}
                              <span class="text-xs font-medium text-purple-500/70 ml-0.5">{{ $d->currency }}</span>
                          </span>
                      </div>
                  @endforeach
                  @if($topDepots->count() >= 3)
                      <p class="text-xs {{ $muted }} pt-1">Showing top 3 — <span class="underline underline-offset-2">view all</span></p>
                  @endif
              </div>
          @else
              <p class="mt-3 text-xs {{ $muted }}">No outstanding charges — all depots are settled.</p>
          @endif
      </a>
  </section>

  {{-- ── Freight Payables ─────────────────────────────────────── --}}
  <section>
      <h2 class="text-[11px] font-semibold uppercase tracking-widest {{ $muted }} mb-3">Freight Payables</h2>
      <a href="{{ route('transporters.index') }}"
         class="group {{ $card }} hover:-translate-y-0.5 transition-all duration-150 cursor-pointer">
          <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0
                              border border-amber-500/20">
                      <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.9 17.9 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                      </svg>
                  </div>
                  <div class="min-w-0">
                      <p class="{{ $label }}">Outstanding Freight Payables</p>
                      @if($byCurrency->count() === 1)
                          <p class="text-2xl font-bold text-amber-500">${{ number_format($byCurrency->first(), 2) }}</p>
                      @elseif($byCurrency->count() > 1)
                          <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                              @foreach($byCurrency as $currency => $total)
                                  @if(!$loop->first)<span class="{{ $muted }} text-lg leading-none">·</span>@endif
                                  <span class="text-2xl font-bold text-amber-500 leading-none">
                                      {{ number_format($total, 2) }}<span class="text-sm font-semibold text-amber-500/70 ml-1">{{ $currency }}</span>
                                  </span>
                              @endforeach
                          </div>
                      @else
                          <p class="text-2xl font-bold {{ $fg }}">$0.00</p>
                      @endif
                  </div>
              </div>
              {!! $chevron !!}
          </div>
          @if($topTransporters->isNotEmpty())
              <div class="{{ $div }}">
                  @foreach($topTransporters as $t)
                      <div class="{{ $rowL }}">
                          <span class="{{ $fg }} truncate max-w-[60%]">{{ $t->name }}</span>
                          <span class="font-semibold text-amber-500">
                              {{ number_format($t->balance, 2) }}
                              <span class="text-xs font-medium text-amber-500/70 ml-0.5">{{ $t->currency }}</span>
                          </span>
                      </div>
                  @endforeach
                  @if($topTransporters->count() >= 3)
                      <p class="text-xs {{ $muted }} pt-1">Showing top 3 — <span class="underline underline-offset-2">view all</span></p>
                  @endif
              </div>
          @else
              <p class="mt-3 text-xs {{ $muted }}">No outstanding balances — all transporters are settled.</p>
          @endif
      </a>
  </section>

</div>
@endsection
