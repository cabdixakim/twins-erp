{{-- resources/views/depot-stock/index.blade.php --}}

@php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  // Your “nice green pill/button” style (like screenshot)
  $pillGreen = 'border-emerald-600 bg-emerald-500 text-white';
  $btnGreen  = 'border-emerald-600 bg-emerald-500 text-white hover:bg-emerald-600 hover:border-emerald-700';
@endphp

@extends('layouts.app')

@section('title', 'Depot stock')
@section('subtitle', 'Live position by depot (batch-aware / FIFO-ready)')

@section('content')

<div class="grid md:grid-cols-12 gap-4">

  {{-- Sidebar --}}
  <aside class="md:col-span-4 lg:col-span-3 rounded-2xl border {{ $border }} {{ $surface }} p-3">
    <div class="flex items-center justify-between gap-3 px-2 pt-2 pb-3">
      <div class="min-w-0">
        <div class="text-sm font-semibold {{ $fg }}">Depots</div>
        <div class="mt-0.5 text-xs {{ $muted }}">Pick a depot to view stock</div>
      </div>

      {{-- optional button slot --}}
      <span class="inline-flex items-center rounded-full border px-2 py-1 text-[10px] font-semibold {{ $border }} {{ $surface2 }} {{ $muted }}">
        {{ $depots->count() }} total
      </span>
    </div>

    @if($depots->isEmpty())
      <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-3 text-xs {{ $muted }}">
        No depots yet. Go to <span class="{{ $fg }} font-semibold">Settings → Depots</span> and add one.
      </div>
    @else
      <div class="space-y-1">
        @foreach($depots as $d)
          @php
            $active = $currentDepot && $currentDepot->id === $d->id;
          @endphp

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
                         {{ $d->is_active ? ($active ? 'border-white/30 text-white/90' : 'border-emerald-500/30 text-emerald-300')
                                         : 'border-slate-500/30 text-slate-400' }}">
              {{ $d->is_active ? 'Active' : 'Inactive' }}
            </span>
          </a>
        @endforeach
      </div>
    @endif
  </aside>

  {{-- Main --}}
  <main class="md:col-span-8 lg:col-span-9">
    @include('depot-stock._details', [
      'border' => $border,
      'surface' => $surface,
      'surface2' => $surface2,
      'fg' => $fg,
      'muted' => $muted,
      'btnGreen' => $btnGreen,
      'pillGreen' => $pillGreen,
    ])
  </main>

</div>
@endsection