@php
  $title = 'Roles & permissions';
  $subtitle = 'Define what each role can see and do across depots, trips, sales and finance.';
@endphp

@extends('layouts.app')

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')
@if (session('status'))
  <div class="mb-4 rounded-lg bg-emerald-900/40 border border-emerald-500/60 px-3 py-2 text-xs text-emerald-100">
    {{ session('status') }}
  </div>
@endif
  <div class="grid md:grid-cols-3 gap-6">
    {{-- Roles list --}}
    <div>
      <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
        <h2 class="text-sm font-semibold mb-3">Roles</h2>
        <ul class="space-y-1 text-xs">
          @foreach($roles as $role)
            <li>
              <a href="{{ route('admin.roles.index', ['role' => $role->slug]) }}"
                 class="flex items-center justify-between px-3 py-2 rounded-xl
                   {{ $currentRole && $currentRole->id === $role->id
                        ? 'bg-slate-800 text-slate-50'
                        : 'bg-slate-950/40 text-slate-300 hover:bg-slate-900' }}">
                <div>
                  <div class="font-semibold text-[13px]">{{ $role->name }}</div>
                  <div class="text-[10px] text-slate-500">{{ $role->description }}</div>
                </div>
                @if($role->is_system)
                  <span class="text-[9px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">system</span>
                @endif
              </a>
            </li>
          @endforeach
        </ul>
      </div>
    </div>

    {{-- Permissions editor --}}
    <div class="md:col-span-2">
      <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 space-y-4">
        @if(!$currentRole)
          <p class="text-xs text-slate-400">No role selected.</p>
        @else
          <div class="flex items-center justify-between mb-1">
            <div>
              <h2 class="text-sm font-semibold">{{ $currentRole->name }}</h2>
              <p class="text-[11px] text-slate-400">
                Toggle what <span class="font-semibold text-slate-100">{{ $currentRole->name }}</span> can do in Twins.
              </p>
            </div>
          </div>

          @if($currentRole->slug === 'owner')
            <div class="rounded-xl border border-emerald-500/50 bg-emerald-950/40 px-3 py-2 text-[11px] text-emerald-100 mb-3">
              Owner always has full access to all modules. Permissions below are informational only.
            </div>
          @endif

          <form method="post" action="{{ route('admin.roles.permissions.sync', $currentRole) }}" class="space-y-3">
            @csrf
            {{-- Group permissions by module --}}
            @php
              $byModule = $permissions->groupBy(function($p) {
                  return $p->module ?: 'other';
              });
            @endphp

            @foreach($byModule as $module => $perms)
              <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                <div class="flex items-center justify-between mb-1">
                  <div class="text-[11px] uppercase tracking-wide text-slate-400">
                    {{ $module === 'other' ? 'System' : ucfirst($module) }}
                  </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-1">
                  @foreach($perms as $perm)
                    <label class="inline-flex items-center gap-1 text-[11px] text-slate-200">
                      <input
                        type="checkbox"
                        name="permissions[]"
                        value="{{ $perm->id }}"
                        class="h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500/60"
                        @checked(in_array($perm->id, $assignedIds))
                        @disabled($currentRole->slug === 'owner')
                      >
                      <span>{{ $perm->name }}</span>
                    </label>
                  @endforeach
                </div>
              </div>
            @endforeach

            @if($currentRole->slug !== 'owner')
              <div class="flex justify-end pt-1">
                <button class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-950">
                  Save permissions
                </button>
              </div>
            @endif
          </form>
        @endif
      </div>
    </div>
  </div>
@endsection