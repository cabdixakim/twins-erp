{{-- resources/views/depot-stock/index.blade.php --}}

@php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';
  $pillGreen = 'border-emerald-600 bg-emerald-500 text-white';
  $btnGreen  = 'border-emerald-600 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-700';
@endphp

@extends('layouts.app')

@section('title', 'Depot movements')
@section('subtitle', 'Receipts, issues and adjustments by depot')

@section('content')

<div class="grid md:grid-cols-12 gap-4">

  {{-- Sidebar --}}
  <aside class="md:col-span-4 lg:col-span-3 rounded-2xl border {{ $border }} {{ $surface }} p-3">
    <div class="flex items-center justify-between gap-3 px-2 pt-2 pb-3">
      <div class="min-w-0">
        <div class="text-sm font-semibold {{ $fg }}">Depots</div>
        <div class="mt-0.5 text-xs {{ $muted }}">Pick a depot to view movements</div>
      </div>

      <span class="inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold {{ $border }} {{ $surface2 }} {{ $muted }}">
        {{ $depots->count() }}
      </span>
    </div>

    @if($depots->isEmpty())
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $muted }}">
        No depots yet. Go to <span class="{{ $fg }} font-semibold">Settings → Depots</span> and add one.
      </div>
    @else
      <div class="space-y-1">
        @php $shownDivider = false; @endphp
        @foreach($depots as $d)
          @php $active = $currentDepot && $currentDepot->id === $d->id; @endphp

          {{-- Divider between system depots and regular depots --}}
          @if(!$d->is_system && !$shownDivider)
            @php $shownDivider = true; @endphp
            @if($depots->where('is_system', true)->count())
              <div class="border-t {{ $border }} my-1"></div>
            @endif
          @endif

          @if($d->is_system)
            {{-- CROSS DOCK — bold amber card --}}
            <a href="{{ route('depot-stock.index', ['depot' => $d->id]) }}"
               class="group flex items-center gap-3 rounded-xl border-2 px-3 py-2.5 transition
                      {{ $active
                           ? 'border-amber-500 bg-amber-500'
                           : 'border-amber-500/60 bg-amber-500/10 hover:border-amber-500 hover:bg-amber-500/15' }}">
              {{-- truck icon --}}
              <span class="{{ $active ? 'text-white' : 'text-amber-400' }} shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 17h8M3 11l2-6h11l2 6M3 11h18M5 17a2 2 0 100-4 2 2 0 000 4zm14 0a2 2 0 100-4 2 2 0 000 4z"/>
                </svg>
              </span>
              <div class="min-w-0 flex-1">
                <div class="text-sm font-bold truncate {{ $active ? 'text-white' : 'text-amber-300' }}">
                  {{ $d->name }}
                </div>
                <div class="text-[11px] {{ $active ? 'text-white/80' : 'text-amber-400/80' }}">
                  Cross dock — in transit stock
                </div>
              </div>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border
                           {{ $active ? 'border-white/30 bg-white/20 text-white' : 'border-amber-500/50 text-amber-300' }}">
                Cross Dock
              </span>
            </a>
          @else
            {{-- Regular depot card --}}
            <a href="{{ route('depot-stock.index', ['depot' => $d->id]) }}"
               class="group flex items-center justify-between gap-3 rounded-xl border px-3 py-2 transition
                      {{ $active ? $pillGreen : $border . ' ' . $surface2 . ' ' . $fg }}
                      {{ $active ? '' : 'hover:border-emerald-500/40 hover:bg-[color:var(--tw-surface)]' }}">
              <div class="min-w-0">
                <div class="text-sm font-semibold truncate {{ $active ? 'text-white' : $fg }}">
                  {{ $d->name }}
                </div>
                <div class="text-[11px] truncate {{ $active ? 'text-white/80' : $muted }}">
                  {{ $d->city ?: 'City not set' }}
                </div>
              </div>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border
                           {{ $d->is_active
                                ? ($active ? 'border-white/30 text-white/90' : 'border-emerald-500/30 text-emerald-300')
                                : 'border-slate-500/30 text-slate-400' }}">
                {{ $d->is_active ? 'Active' : 'Inactive' }}
              </span>
            </a>
          @endif
        @endforeach
      </div>
    @endif
  </aside>

  {{-- Main --}}
  <main class="md:col-span-8 lg:col-span-9">
    @include('depot-stock._details', [
      'border'   => $border,
      'surface'  => $surface,
      'surface2' => $surface2,
      'fg'       => $fg,
      'muted'    => $muted,
      'btnGreen' => $btnGreen,
      'pillGreen'=> $pillGreen,
    ])
  </main>

</div>
@endsection
