@php
    $title    = 'Audit Log';
    $subtitle = 'Full history of sensitive actions performed in this company.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";

    $eventColors = [
        'created'   => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'updated'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'deleted'   => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'posted'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'voided'    => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'paid'      => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'confirmed' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        'received'  => 'bg-teal-500/10 text-teal-400 border-teal-500/20',
        'cancelled' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        'issued'    => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        'adjusted'  => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
    ];
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

{{-- Filters --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Event</label>
            <select name="event" class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All events</option>
                @foreach($events as $ev)
                    <option value="{{ $ev }}" @selected(request('event') === $ev)>{{ ucfirst($ev) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">User</label>
            <select name="user_id" class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All users</option>
                @foreach($users as $u)
                    <option value="{{ $u->user_id }}" @selected(request('user_id') == $u->user_id)>{{ $u->user_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Reference, description, user…"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 w-52 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>
        <button type="submit" class="{{ $btnPrimary }}">Filter</button>
        @if(request()->hasAny(['event','user_id','search','from','to']))
            <a href="{{ route('admin.audit-log') }}" class="{{ $btnGhost }}">Clear</a>
        @endif
    </form>
</div>

{{-- Log count --}}
<div class="{{ $muted }} text-xs mb-2 px-1">
    {{ number_format($logs->total()) }} entries
</div>

{{-- Table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b {{ $border }} {{ $surface2 }}">
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-36">When</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Event</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Description</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">Record</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-32">User</th>
                <th class="text-left px-4 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-28">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[color:var(--tw-border)]">
            @forelse($logs as $log)
            @php
                $evClass = $eventColors[$log->event] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                $modelShort = $log->model_type ? class_basename($log->model_type) : null;
            @endphp
            <tr class="hover:bg-[color:var(--tw-surface-2)] transition">
                <td class="px-4 py-3 {{ $muted }}">
                    <div>{{ $log->created_at->format('d M Y') }}</div>
                    <div class="text-[10px]">{{ $log->created_at->format('H:i:s') }}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide border {{ $evClass }}">
                        {{ $log->event }}
                    </span>
                </td>
                <td class="px-4 py-3 {{ $fg }}">{{ $log->description }}</td>
                <td class="px-4 py-3">
                    @if($modelShort)
                        <div class="{{ $muted }} text-[10px] uppercase tracking-wide">{{ $modelShort }}</div>
                    @endif
                    @if($log->model_label)
                        <div class="{{ $fg }} font-medium">{{ $log->model_label }}</div>
                    @elseif($log->model_id)
                        <div class="{{ $muted }}">#{{ $log->model_id }}</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if($log->user_name)
                        <div class="{{ $fg }} font-medium">{{ $log->user_name }}</div>
                    @else
                        <span class="{{ $muted }}">System</span>
                    @endif
                </td>
                <td class="px-4 py-3 {{ $muted }} font-mono text-[10px]">
                    {{ $log->ip_address ?? '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center {{ $muted }}">No audit entries found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif

@endsection
