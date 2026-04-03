@extends('layouts.app')

@section('title', $client->name)
@section('subtitle', 'Client · ' . ($client->is_active ? 'Active' : 'Inactive'))

@section('content')

@php
  $fg      = 'text-[color:var(--tw-fg)]';
  $muted   = 'text-[color:var(--tw-muted)]';
  $surface = 'bg-[color:var(--tw-surface)]';
  $surface2= 'bg-[color:var(--tw-surface-2)]';
  $border  = 'border-[color:var(--tw-border)]';
  $btnBase = 'inline-flex items-center justify-center gap-2 rounded-xl border font-semibold transition select-none';
  $btnGhost= $btnBase.' border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] '.$fg.' hover:bg-[color:var(--tw-surface)]';
  $pillBase= 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';

  $typeLabels = ['government' => 'Government', 'private' => 'Private', 'retail' => 'Retail', 'industrial' => 'Industrial', 'other' => 'Other'];
@endphp

<div class="flex flex-col gap-4">

  {{-- Header --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 sm:p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <a href="{{ route('clients.index') }}" class="{{ $btnGhost }} h-9 w-9 shrink-0">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-[15px] font-semibold {{ $fg }} truncate">{{ $client->name }}</h1>
            @if($client->code)
              <span class="{{ $pillBase }} {{ $border }} {{ $surface2 }} {{ $fg }}">{{ $client->code }}</span>
            @endif
            @if($client->is_active)
              <span class="{{ $pillBase }} s-green">Active</span>
            @else
              <span class="{{ $pillBase }} s-slate">Inactive</span>
            @endif
          </div>
          <p class="mt-0.5 text-[11px] {{ $muted }}">
            {{ $typeLabels[$client->type] ?? ($client->type ? ucfirst($client->type) : 'Client') }}
            @if($client->city) · {{ $client->city }} @endif
            @if($client->country) · {{ $client->country }} @endif
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('clients.edit', $client) }}" class="{{ $btnGhost }} h-9 px-3 text-[12px]">
          <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit
        </a>
        <form method="POST" action="{{ route('clients.destroy', $client) }}"
              onsubmit="return confirm('Delete this client? This cannot be undone.')">
          @csrf @method('DELETE')
          <button type="submit" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-xl border btn-soft-rose text-[12px] font-semibold transition">
            Delete
          </button>
        </form>
      </div>
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

  {{-- Info grid --}}
  <div class="grid gap-4 sm:grid-cols-2">

    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 space-y-3">
      <div class="text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Contact</div>

      <dl class="space-y-2 text-sm">
        <div class="flex justify-between gap-3">
          <dt class="{{ $muted }}">Person</dt>
          <dd class="font-semibold {{ $fg }} text-right">{{ $client->contact_person ?: '—' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="{{ $muted }}">Phone</dt>
          <dd class="font-semibold {{ $fg }} text-right">{{ $client->phone ?: '—' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="{{ $muted }}">Email</dt>
          <dd class="font-semibold {{ $fg }} text-right break-all">{{ $client->email ?: '—' }}</dd>
        </div>
      </dl>
    </div>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 space-y-3">
      <div class="text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Financial</div>

      <dl class="space-y-2 text-sm">
        <div class="flex justify-between gap-3">
          <dt class="{{ $muted }}">Currency</dt>
          <dd class="font-semibold {{ $fg }}">{{ $client->currency }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="{{ $muted }}">Credit limit</dt>
          <dd class="font-semibold {{ $fg }}">
            {{ $client->currency }} {{ number_format((float)$client->credit_limit, 2) }}
          </dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="{{ $muted }}">Dispatches</dt>
          <dd class="font-semibold {{ $fg }}">{{ number_format($dispatchCount) }}</dd>
        </div>
      </dl>
    </div>

  </div>

  @if($client->notes)
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
      <div class="text-[11px] font-semibold {{ $muted }} uppercase tracking-wide mb-2">Notes</div>
      <p class="text-sm {{ $fg }} whitespace-pre-wrap">{{ $client->notes }}</p>
    </div>
  @endif

  {{-- Recent dispatches --}}
  @if($client->purchases->isNotEmpty())
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
      <div class="px-4 py-3 border-b {{ $border }} {{ 'bg-[color:var(--tw-surface-2)]' }}">
        <div class="text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Recent dispatches</div>
      </div>
      <div class="divide-y {{ $border }}">
        @foreach($client->purchases as $p)
          <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm hover:{{ 'bg-[color:var(--tw-surface-2)]' }} transition">
            <div>
              <a href="{{ route('purchases.show', $p) }}" class="font-semibold {{ $fg }} hover:text-[color:var(--tw-accent)] transition">
                Purchase #{{ $p->id }}
              </a>
              <div class="text-[11px] {{ $muted }}">{{ $p->purchase_date?->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="text-right">
              <div class="font-semibold {{ $fg }}">{{ number_format((float)$p->qty, 3) }} L</div>
              <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold s-purple">dispatched</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

</div>
@endsection
