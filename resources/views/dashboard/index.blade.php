@extends('layouts.app')
@section('title','Summary')
@section('content')
<div class="space-y-6">

  {{-- Welcome --}}
  <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-6 shadow shadow-emerald-500/10">
    <h1 class="text-lg font-semibold mb-1">Welcome to Twins</h1>
    <p class="text-sm text-slate-400">Here's a quick snapshot of what needs your attention today.</p>
  </div>

  {{-- Outstanding Freight Payables --}}
  <div>
    <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Freight Payables</h2>

    <a href="{{ route('transporters.index') }}"
       class="group block bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow shadow-amber-500/5 hover:border-amber-500/40 hover:shadow-amber-500/10 transition-all">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
          {{-- truck icon --}}
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.9 17.9 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
            </svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs text-slate-400 mb-1">Outstanding Freight Payables</p>
            @if($byCurrency->count() === 1)
              {{-- Single currency: preserve legacy $ format --}}
              <p class="text-2xl font-bold text-amber-400">${{ number_format($byCurrency->first(), 2) }}</p>
            @elseif($byCurrency->count() > 1)
              {{-- Multiple currencies: one figure per currency with code label --}}
              <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                @foreach($byCurrency as $currency => $total)
                  @if(!$loop->first)<span class="text-slate-600 text-lg leading-none">·</span>@endif
                  <span class="text-2xl font-bold text-amber-400 leading-none">
                    {{ number_format($total, 2) }}<span class="text-sm font-semibold text-amber-500/80 ml-1">{{ $currency }}</span>
                  </span>
                @endforeach
              </div>
            @else
              <p class="text-2xl font-bold text-slate-300">$0.00</p>
            @endif
          </div>
        </div>
        <svg class="w-4 h-4 text-slate-600 group-hover:text-amber-400 transition-colors flex-shrink-0 ml-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>

      @if($topTransporters->isNotEmpty())
        <div class="mt-4 border-t border-slate-800 pt-4 space-y-2">
          @foreach($topTransporters as $t)
            <div class="flex items-center justify-between text-sm">
              <span class="text-slate-300 truncate max-w-[60%]">{{ $t->name }}</span>
              <span class="font-semibold text-amber-300">
                {{ number_format($t->balance, 2) }}
                <span class="text-xs font-medium text-amber-400/70 ml-0.5">{{ $t->currency }}</span>
              </span>
            </div>
          @endforeach
          @if($topTransporters->count() >= 3)
            <p class="text-xs text-slate-500 pt-1">Showing top 3 — <span class="underline underline-offset-2">view all</span></p>
          @endif
        </div>
      @else
        <p class="mt-3 text-xs text-slate-500">No outstanding balances — all transporters are settled.</p>
      @endif
    </a>
  </div>

</div>
@endsection
