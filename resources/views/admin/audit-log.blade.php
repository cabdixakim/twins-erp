@php
    $title    = 'Audit Log';
    $subtitle = 'Complete history of every sensitive action in this company.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-xs px-3 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-xs px-3 py-2";

    $severityConfig = [
        'info'     => ['cls' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',    'dot' => 'bg-sky-400',    'label' => 'Info'],
        'warning'  => ['cls' => 'bg-amber-500/10 text-amber-400 border-amber-500/20', 'dot' => 'bg-amber-400', 'label' => 'Warning'],
        'critical' => ['cls' => 'bg-rose-500/10 text-rose-400 border-rose-500/20', 'dot' => 'bg-rose-400',   'label' => 'Critical'],
    ];

    $eventColors = [
        'created'     => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        'updated'     => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'deleted'     => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'posted'      => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'voided'      => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        'paid'        => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'confirmed'   => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        'received'    => 'bg-teal-500/10 text-teal-400 border-teal-500/20',
        'cancelled'   => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        'issued'      => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        'adjusted'    => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
        'dispatched'  => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        'transferred' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
        'nominated'   => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'login'       => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        'logout'      => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    ];

    $moduleColors = [
        'Purchase'  => 'bg-blue-500/10 text-blue-400',
        'Sale'      => 'bg-emerald-500/10 text-emerald-400',
        'Invoice'   => 'bg-teal-500/10 text-teal-400',
        'Client'    => 'bg-purple-500/10 text-purple-400',
        'Supplier'  => 'bg-orange-500/10 text-orange-400',
        'Transport' => 'bg-amber-500/10 text-amber-400',
        'Depot'     => 'bg-sky-500/10 text-sky-400',
        'Admin'     => 'bg-rose-500/10 text-rose-400',
    ];
@endphp

@extends('layouts.app')
@section('title', $title)
@section('subtitle', $subtitle)

@section('content')

{{-- KPI Bar --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Total Events</div>
        <div class="text-2xl font-bold {{ $fg }}">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Today</div>
        <div class="text-2xl font-bold {{ $fg }}">{{ number_format($stats['today']) }}</div>
    </div>
    <div class="rounded-2xl border border-amber-500/20 {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide text-amber-400 mb-1">Warnings</div>
        <div class="text-2xl font-bold text-amber-400">{{ number_format($stats['warning']) }}</div>
    </div>
    <div class="rounded-2xl border border-rose-500/20 {{ $surface }} p-4">
        <div class="text-[10px] uppercase tracking-wide text-rose-400 mb-1">Critical</div>
        <div class="text-2xl font-bold text-rose-400">{{ number_format($stats['critical']) }}</div>
    </div>
</div>

{{-- Filters --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-end">

        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Severity</label>
            <select name="severity" class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All</option>
                <option value="info"     @selected(request('severity') === 'info')>Info</option>
                <option value="warning"  @selected(request('severity') === 'warning')>Warning</option>
                <option value="critical" @selected(request('severity') === 'critical')>Critical</option>
            </select>
        </div>

        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Module</label>
            <select name="module" class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                <option value="">All modules</option>
                @foreach($modules as $mod)
                    <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                @endforeach
            </select>
        </div>

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
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">From</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>

        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">To</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>

        <div>
            <label class="block text-[10px] uppercase tracking-wide {{ $muted }} mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Description, record, user…"
                   class="rounded-xl border {{ $border }} {{ $bg }} {{ $fg }} text-xs px-3 py-1.5 w-48 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
        </div>

        <button type="submit" class="{{ $btnPrimary }}">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            Filter
        </button>

        @if(request()->hasAny(['event','severity','module','user_id','search','from','to']))
            <a href="{{ route('admin.audit-log') }}" class="{{ $btnGhost }}">Clear</a>
        @endif

        <div class="ml-auto flex items-end">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="{{ $btnGhost }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </div>
    </form>
</div>

{{-- Log count --}}
<div class="{{ $muted }} text-xs mb-2 px-1">
    {{ number_format($logs->total()) }} {{ Str::plural('entry', $logs->total()) }}
    @if(request()->hasAny(['event','severity','module','user_id','search','from','to']))
        <span class="text-amber-400">· filtered</span>
    @endif
</div>

{{-- Table --}}
<div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    <table class="w-full text-xs">
        <thead>
            <tr class="border-b {{ $border }} {{ $surface2 }}">
                <th class="w-6 px-3 py-3"></th>{{-- expand toggle --}}
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-36">When</th>
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-20">Sev.</th>
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Module</th>
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">Event</th>
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px]">Description</th>
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-36">Record</th>
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-32">User</th>
                <th class="text-left px-3 py-3 font-semibold {{ $muted }} uppercase tracking-wide text-[10px] w-24">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[color:var(--tw-border)]">
            @forelse($logs as $log)
            @php
                $evClass  = $eventColors[$log->event] ?? 'bg-slate-500/10 text-slate-400 border-slate-500/20';
                $sevCfg   = $severityConfig[$log->severity] ?? $severityConfig['info'];
                $modCls   = $moduleColors[$log->module] ?? 'bg-slate-500/10 text-slate-400';
                $hasDetail = $log->before_data || $log->after_data || $log->url || $log->user_agent;
            @endphp
            <tr class="hover:bg-[color:var(--tw-surface-2)] transition audit-row" data-id="{{ $log->id }}">
                <td class="px-3 py-3 text-center">
                    @if($hasDetail)
                    <button type="button"
                        onclick="toggleDetail({{ $log->id }})"
                        class="text-[color:var(--tw-muted)] hover:text-[color:var(--tw-fg)] transition"
                        title="Show detail">
                        <svg id="icon-{{ $log->id }}" class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    @endif
                </td>
                <td class="px-3 py-3 {{ $muted }}">
                    <div class="font-medium" style="color:var(--tw-fg)">{{ $log->created_at->format('d M Y') }}</div>
                    <div class="text-[10px]">{{ $log->created_at->format('H:i:s') }}</div>
                </td>
                <td class="px-3 py-3">
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide border {{ $sevCfg['cls'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $sevCfg['dot'] }}"></span>
                        {{ $sevCfg['label'] }}
                    </span>
                </td>
                <td class="px-3 py-3">
                    @if($log->module)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $modCls }}">
                        {{ $log->module }}
                    </span>
                    @else
                        <span class="{{ $muted }}">—</span>
                    @endif
                </td>
                <td class="px-3 py-3">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide border {{ $evClass }}">
                        {{ $log->event }}
                    </span>
                </td>
                <td class="px-3 py-3 {{ $fg }} max-w-xs">
                    <div class="truncate" title="{{ $log->description }}">{{ $log->description }}</div>
                </td>
                <td class="px-3 py-3">
                    @if($log->model_label)
                        @php
                            $recordUrl = null;
                            if ($log->model_type && $log->model_id) {
                                try {
                                    $recordUrl = match(class_basename($log->model_type)) {
                                        'Purchase'            => route('purchases.show', $log->model_id),
                                        'ImportTruck'         => route('purchases.show', \App\Models\ImportTruck::find($log->model_id)?->nomination?->purchase_id ?? 0),
                                        'Client'              => route('clients.show', $log->model_id),
                                        'Supplier'            => route('suppliers.show', $log->model_id),
                                        'Depot'               => route('depots.show', $log->model_id),
                                        'User'                => route('admin.users'),
                                        default               => null,
                                    };
                                } catch (\Throwable) { $recordUrl = null; }
                                // Discard routes pointing to model_id = 0 (unresolved)
                                if ($recordUrl && str_ends_with($recordUrl, '/0')) $recordUrl = null;
                            }
                        @endphp
                        @if($recordUrl)
                        <a href="{{ $recordUrl }}"
                           class="font-semibold hover:underline"
                           style="color:var(--tw-accent)"
                           title="Open record">{{ $log->model_label }}</a>
                        @else
                        <div class="{{ $fg }} font-medium">{{ $log->model_label }}</div>
                        @endif
                        @if($log->model_type)
                        <div class="{{ $muted }} text-[10px]">{{ class_basename($log->model_type) }}</div>
                        @endif
                    @elseif($log->model_id)
                        <div class="{{ $muted }}">#{{ $log->model_id }}</div>
                    @else
                        <span class="{{ $muted }}">—</span>
                    @endif
                </td>
                <td class="px-3 py-3">
                    @if($log->user_name)
                        <div class="{{ $fg }} font-medium">{{ $log->user_name }}</div>
                    @else
                        <span class="{{ $muted }}">System</span>
                    @endif
                </td>
                <td class="px-3 py-3 {{ $muted }} font-mono text-[10px]">
                    {{ $log->ip_address ?? '—' }}
                </td>
            </tr>

            {{-- Expandable detail row --}}
            @if($hasDetail)
            <tr id="detail-{{ $log->id }}" class="hidden border-t-0">
                <td colspan="9" class="px-4 pb-4 pt-0">
                    <div class="rounded-xl border {{ $border }} {{ $surface2 }} p-4 ml-5 space-y-4">

                        {{-- Before / After diff --}}
                        @if($log->before_data || $log->after_data)
                        <div>
                            <div class="text-[10px] uppercase tracking-wide {{ $muted }} font-semibold mb-2">Changes</div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @if($log->before_data)
                                <div>
                                    <div class="text-[10px] font-bold text-rose-400 uppercase mb-1.5">Before</div>
                                    <div class="space-y-1">
                                        @foreach($log->before_data as $key => $val)
                                        @php
                                            $changed = $log->after_data && array_key_exists($key, $log->after_data) && $log->after_data[$key] != $val;
                                        @endphp
                                        <div class="flex gap-2 text-[11px] rounded-lg px-2 py-1 {{ $changed ? 'bg-rose-500/10' : '' }}">
                                            <span class="{{ $muted }} w-32 shrink-0 truncate">{{ $key }}</span>
                                            <span class="{{ $changed ? 'text-rose-400 line-through' : $fg }} truncate">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                @if($log->after_data)
                                <div>
                                    <div class="text-[10px] font-bold text-emerald-400 uppercase mb-1.5">After</div>
                                    <div class="space-y-1">
                                        @foreach($log->after_data as $key => $val)
                                        @php
                                            $changed = $log->before_data && array_key_exists($key, $log->before_data) && $log->before_data[$key] != $val;
                                        @endphp
                                        <div class="flex gap-2 text-[11px] rounded-lg px-2 py-1 {{ $changed ? 'bg-emerald-500/10' : '' }}">
                                            <span class="{{ $muted }} w-32 shrink-0 truncate">{{ $key }}</span>
                                            <span class="{{ $changed ? 'text-emerald-400 font-semibold' : $fg }} truncate">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Request metadata --}}
                        @if($log->url || $log->user_agent)
                        <div class="pt-3 border-t {{ $border }}">
                            <div class="text-[10px] uppercase tracking-wide {{ $muted }} font-semibold mb-2">Request</div>
                            <div class="space-y-1 text-[11px]">
                                @if($log->url)
                                <div class="flex gap-2">
                                    <span class="{{ $muted }} w-20 shrink-0">Endpoint</span>
                                    <span class="font-mono {{ $fg }} truncate">
                                        @if($log->method)
                                        <span class="inline-block bg-sky-500/10 text-sky-400 rounded px-1.5 py-0.5 text-[10px] font-bold mr-1">{{ $log->method }}</span>
                                        @endif
                                        {{ $log->url }}
                                    </span>
                                </div>
                                @endif
                                @if($log->user_agent)
                                <div class="flex gap-2">
                                    <span class="{{ $muted }} w-20 shrink-0">User agent</span>
                                    <span class="{{ $fg }} truncate">{{ Str::limit($log->user_agent, 120) }}</span>
                                </div>
                                @endif
                                @if($log->ip_address)
                                <div class="flex gap-2">
                                    <span class="{{ $muted }} w-20 shrink-0">IP address</span>
                                    <span class="font-mono {{ $fg }}">{{ $log->ip_address }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif

                    </div>
                </td>
            </tr>
            @endif

            @empty
            <tr>
                <td colspan="9" class="px-4 py-16 text-center">
                    <div class="{{ $muted }} text-sm">No audit entries found.</div>
                    @if(request()->hasAny(['event','severity','module','user_id','search','from','to']))
                    <div class="mt-1 text-xs {{ $muted }}">Try clearing the filters.</div>
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($logs->hasPages())
    <div class="mt-4">{{ $logs->links() }}</div>
@endif

<script>
function toggleDetail(id) {
    const row  = document.getElementById('detail-' + id);
    const icon = document.getElementById('icon-' + id);
    if (!row) return;
    const open = !row.classList.contains('hidden');
    row.classList.toggle('hidden', open);
    icon.style.transform = open ? '' : 'rotate(90deg)';
}
</script>

@endsection
