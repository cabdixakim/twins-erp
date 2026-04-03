@extends('layouts.app')

@section('title', 'Clients')
@section('subtitle', 'Manage your customer accounts')

@section('content')

@php
  $fg      = 'text-[color:var(--tw-fg)]';
  $muted   = 'text-[color:var(--tw-muted)]';
  $surface = 'bg-[color:var(--tw-surface)]';
  $surface2= 'bg-[color:var(--tw-surface-2)]';
  $border  = 'border-[color:var(--tw-border)]';
  $ring    = 'focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]';
  $btnBase = 'inline-flex items-center justify-center gap-2 rounded-xl border font-semibold transition select-none';
  $btnGhost= $btnBase.' border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] '.$fg.' hover:bg-[color:var(--tw-surface)]';
  $btnPrime= $btnBase.' border-[color:var(--tw-accent)] bg-[color:var(--tw-accent-soft)] '.$fg.' hover:brightness-110';
  $pillBase= 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
  $totalCount = $clients->total();

  $typeLabels = ['government' => 'Government', 'private' => 'Private', 'retail' => 'Retail', 'industrial' => 'Industrial', 'other' => 'Other'];
@endphp

<div class="flex flex-col gap-4">

  {{-- Header --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 sm:p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0 flex items-center gap-3">
        <span class="h-9 w-9 rounded-2xl grid place-items-center {{ $surface2 }} border {{ $border }} shrink-0">
          <svg class="w-5 h-5 {{ $fg }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </span>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h1 class="text-[15px] sm:text-base font-semibold {{ $fg }} leading-tight truncate">Clients</h1>
            <span class="{{ $pillBase }} {{ $border }} {{ $surface2 }} {{ $fg }}">{{ number_format($totalCount) }}</span>
          </div>
          <p class="mt-0.5 text-[11px] sm:text-[12px] {{ $muted }} leading-snug">Customer accounts for dispatches &amp; sales.</p>
        </div>
      </div>
      <a href="{{ route('clients.create') }}" class="{{ $btnPrime }} h-9 px-3 sm:h-10 sm:px-4 text-[12px] sm:text-[13px] shrink-0">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        <span class="hidden xs:inline">New client</span>
      </a>
    </div>
  </div>

  @if(session('status'))
    <div class="alert-ok rounded-xl px-4 py-2.5 text-sm font-medium">
      {{ session('status') }}
    </div>
  @endif

  @if(session('error'))
    <div class="alert-err rounded-xl px-4 py-2.5 text-sm font-medium">
      {{ session('error') }}
    </div>
  @endif

  {{-- Filters --}}
  <form method="GET" action="{{ route('clients.index') }}" class="flex flex-wrap items-center gap-2">
    <input type="text" name="q" value="{{ $q }}" placeholder="Search name, code, city…"
           class="h-9 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} {{ $ring }} focus:outline-none w-full sm:w-56" />

    <select name="type" class="h-9 rounded-xl border {{ $border }} {{ $surface2 }} px-2.5 text-sm {{ $fg }} {{ $ring }} focus:outline-none">
      <option value="">All types</option>
      @foreach($typeLabels as $val => $label)
        <option value="{{ $val }}" @selected($type === $val)>{{ $label }}</option>
      @endforeach
    </select>

    <select name="status" class="h-9 rounded-xl border {{ $border }} {{ $surface2 }} px-2.5 text-sm {{ $fg }} {{ $ring }} focus:outline-none">
      <option value="">All statuses</option>
      <option value="active" @selected($status === 'active')>Active</option>
      <option value="inactive" @selected($status === 'inactive')>Inactive</option>
    </select>

    <button type="submit" class="{{ $btnGhost }} h-9 px-3 text-[12px]">Filter</button>
    @if($q || $status || $type)
      <a href="{{ route('clients.index') }}" class="{{ $btnGhost }} h-9 px-3 text-[12px]">Clear</a>
    @endif
  </form>

  {{-- Table --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
    @if($clients->isEmpty())
      <div class="py-16 text-center">
        <div class="mx-auto h-12 w-12 rounded-2xl {{ $surface2 }} border {{ $border }} grid place-items-center mb-3">
          <svg class="w-6 h-6 {{ $muted }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <p class="text-sm font-semibold {{ $fg }}">No clients found</p>
        <p class="mt-1 text-xs {{ $muted }}">Add your first client to link them to dispatches.</p>
        <a href="{{ route('clients.create') }}" class="{{ $btnPrime }} mt-4 h-9 px-4 text-[13px] mx-auto">
          Add client
        </a>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b {{ $border }} {{ $surface2 }}">
              <th class="text-left px-4 py-3 text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Name</th>
              <th class="text-left px-4 py-3 text-[11px] font-semibold {{ $muted }} uppercase tracking-wide hidden sm:table-cell">Code</th>
              <th class="text-left px-4 py-3 text-[11px] font-semibold {{ $muted }} uppercase tracking-wide hidden md:table-cell">Type</th>
              <th class="text-left px-4 py-3 text-[11px] font-semibold {{ $muted }} uppercase tracking-wide hidden lg:table-cell">City</th>
              <th class="text-left px-4 py-3 text-[11px] font-semibold {{ $muted }} uppercase tracking-wide hidden md:table-cell">Contact</th>
              <th class="text-left px-4 py-3 text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y {{ $border }}">
            @foreach($clients as $client)
              <tr class="hover:{{ $surface2 }} transition">
                <td class="px-4 py-3">
                  <a href="{{ route('clients.show', $client) }}" class="font-semibold {{ $fg }} hover:text-[color:var(--tw-accent)] transition">
                    {{ $client->name }}
                  </a>
                </td>
                <td class="px-4 py-3 {{ $muted }} hidden sm:table-cell">{{ $client->code ?: '—' }}</td>
                <td class="px-4 py-3 hidden md:table-cell">
                  @if($client->type)
                    <span class="{{ $pillBase }} {{ $border }} {{ $surface2 }} {{ $fg }}">
                      {{ $typeLabels[$client->type] ?? ucfirst($client->type) }}
                    </span>
                  @else
                    <span class="{{ $muted }}">—</span>
                  @endif
                </td>
                <td class="px-4 py-3 {{ $muted }} hidden lg:table-cell">{{ $client->city ?: '—' }}</td>
                <td class="px-4 py-3 {{ $muted }} hidden md:table-cell">{{ $client->contact_person ?: '—' }}</td>
                <td class="px-4 py-3">
                  @if($client->is_active)
                    <span class="{{ $pillBase }} s-green">Active</span>
                  @else
                    <span class="{{ $pillBase }} s-slate">Inactive</span>
                  @endif
                </td>
                <td class="px-4 py-3 text-right">
                  <a href="{{ route('clients.edit', $client) }}" class="{{ $btnGhost }} h-8 px-3 text-[11px]">Edit</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if($clients->hasPages())
        <div class="px-4 py-3 border-t {{ $border }}">
          {{ $clients->links() }}
        </div>
      @endif
    @endif
  </div>

</div>
@endsection
