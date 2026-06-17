@extends('layouts.app')

@section('title', 'Customs Authorities')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';
@endphp

<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs {{ $muted }} uppercase tracking-widest mb-1">Payables</div>
            <h1 class="text-xl font-bold {{ $fg }}">Customs Authorities</h1>
            <p class="text-sm {{ $muted }} mt-0.5">Duty payable to customs authorities on import shipments.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('duties.index') }}"
               class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
                Duties register
            </a>
            <a href="{{ route('settings.duty-vendors.index') }}"
               class="h-8 px-3 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
                Manage authorities
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    @if($vendors->isEmpty())
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-16 text-center">
            <div class="text-sm font-semibold {{ $fg }} mb-1">No customs authorities</div>
            <p class="text-xs {{ $muted }} mb-4">Add authorities in Settings → Customs Authorities to start tracking duty payables.</p>
            <a href="{{ route('settings.duty-vendors.index') }}"
               class="inline-flex h-9 px-5 rounded-xl bg-[color:var(--tw-accent)] text-white text-sm font-semibold items-center hover:opacity-90 transition">
                Go to settings
            </a>
        </div>
    @else
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        <table class="w-full text-sm">
            <thead class="{{ $muted }} border-b {{ $border }} {{ $surface2 }}">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Authority</th>
                    <th class="text-left px-3 py-3 font-semibold">Code</th>
                    <th class="text-left px-3 py-3 font-semibold">Country</th>
                    <th class="text-right px-3 py-3 font-semibold">Total Duties</th>
                    <th class="text-right px-5 py-3 font-semibold">Balance Owed</th>
                </tr>
            </thead>
            <tbody class="divide-y {{ $border }}">
                @foreach($vendors as $v)
                @php
                    $vendorBalances = $balances[$v->id] ?? collect();
                    $vendorCharge   = number_format($chargeTotals[$v->id] ?? 0, 2);
                @endphp
                <tr class="hover:bg-[color:var(--tw-surface-2)] transition cursor-pointer"
                    onclick="window.location='{{ route('duty-vendors.show', $v) }}'">
                    <td class="px-5 py-3 font-semibold {{ $fg }}">{{ $v->name }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $v->code ?? '—' }}</td>
                    <td class="px-3 py-3 {{ $muted }}">{{ $v->country ?? '—' }}</td>
                    <td class="px-3 py-3 text-right {{ $muted }}">{{ $vendorCharge }}</td>
                    <td class="px-5 py-3 text-right">
                        @foreach($vendorBalances as $currency => $bal)
                            @php $b = (float) $bal; @endphp
                            <div class="font-semibold text-sm {{ $b > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $v->default_currency ?? $currency }} {{ number_format(abs($b), 2) }}
                                @if($b < 0) <span class="text-xs font-normal">CR</span> @endif
                            </div>
                        @endforeach
                        @if($vendorBalances->isEmpty())
                            <span class="{{ $muted }}">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
