@extends('layouts.app')
@section('title','Alerts')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';

    $groups = [
        'overdue_transit' => ['label' => 'Overdue In Transit', 'class' => 's-amber'],
        'overdue_border'  => ['label' => 'Stuck at Border',    'class' => 's-orange'],
        'pending_duty'    => ['label' => 'Pending Duty',       'class' => 's-blue'],
    ];
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="text-xl font-bold {{ $fg }}">Alerts</h1>
        <p class="{{ $muted }} text-sm mt-0.5">Active issues requiring attention</p>
    </div>

    @if(empty($alerts))
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-12 text-center">
            <svg class="w-10 h-10 mx-auto {{ $muted }} opacity-40 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-semibold {{ $fg }}">All clear</p>
            <p class="{{ $muted }} text-sm mt-1">No active alerts. Everything looks good.</p>
        </div>
    @else
        {{-- Summary bar --}}
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm {{ $muted }}">{{ count($alerts) }} active {{ Str::plural('alert', count($alerts)) }}</span>
            @php $byCategory = collect($alerts)->groupBy('category') @endphp
            @foreach($byCategory as $cat => $items)
                @php $g = $groups[$cat] ?? ['label' => ucfirst($cat), 'class' => 's-slate'] @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $g['class'] }}">
                    {{ count($items) }} {{ $g['label'] }}
                </span>
            @endforeach
        </div>

        <div class="space-y-3">
            @foreach($alerts as $alert)
            @php
                $g    = $groups[$alert['category']] ?? ['label' => 'Alert', 'class' => 's-slate'];
                $isWarn = $alert['type'] === 'warning';
            @endphp
            <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 flex items-start gap-4">
                <div class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center
                            {{ $isWarn ? 's-amber' : 's-blue' }}">
                    @if($alert['icon'] === 'truck')
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 1m8-1h3l3-3-1-5h-5v9z"/>
                        </svg>
                    @elseif($alert['icon'] === 'border')
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-sm {{ $fg }}">{{ $alert['title'] }}</div>
                            <div class="text-xs {{ $muted }} mt-0.5">{{ $alert['body'] }}</div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold flex-shrink-0 {{ $g['class'] }}">
                            {{ $g['label'] }}
                        </span>
                    </div>
                    @if($alert['link'])
                        <a href="{{ $alert['link'] }}"
                           class="mt-2 text-xs font-semibold text-[color:var(--tw-accent)] hover:underline inline-flex items-center gap-1">
                            View purchase
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
