@extends('layouts.app')

@section('title', isset($client) ? 'Edit Client' : 'New Client')
@section('subtitle', isset($client) ? $client->name : 'Add a new client account')

@section('content')

@php
  $isEdit  = isset($client) && $client !== null;
  $fg      = 'text-[color:var(--tw-fg)]';
  $muted   = 'text-[color:var(--tw-muted)]';
  $surface = 'bg-[color:var(--tw-surface)]';
  $surface2= 'bg-[color:var(--tw-surface-2)]';
  $border  = 'border-[color:var(--tw-border)]';
  $ring    = 'focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]';
  $inputCls= "w-full h-10 rounded-xl border {$border} {$surface2} px-3 text-sm {$fg} {$ring} focus:outline-none";
  $labelCls= "block text-xs font-semibold {$fg} mb-1";
  $btnBase = 'inline-flex items-center justify-center gap-2 rounded-xl border font-semibold transition select-none';
  $btnGhost= $btnBase.' border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] '.$fg.' hover:bg-[color:var(--tw-surface)]';

  $typeOptions = ['' => 'Select type…', 'government' => 'Government', 'private' => 'Private', 'retail' => 'Retail', 'industrial' => 'Industrial', 'other' => 'Other'];
@endphp

<div class="flex flex-col gap-4 max-w-2xl">

  {{-- Header --}}
  <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 sm:p-4">
    <div class="flex items-center gap-3">
      <a href="{{ $isEdit ? route('clients.show', $client) : route('clients.index') }}"
         class="{{ $btnGhost }} h-9 w-9 shrink-0">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <div>
        <h1 class="text-[15px] font-semibold {{ $fg }}">{{ $isEdit ? 'Edit client' : 'New client' }}</h1>
        <p class="text-[11px] {{ $muted }}">{{ $isEdit ? $client->name : 'Fill in the client details below.' }}</p>
      </div>
    </div>
  </div>

  @if($errors->any())
    <div class="alert-err rounded-xl px-4 py-3 text-sm font-medium">
      <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST"
        action="{{ $isEdit ? route('clients.update', $client) : route('clients.store') }}"
        class="flex flex-col gap-4">
    @csrf
    @if($isEdit) @method('PATCH') @endif

    {{-- Core details --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 sm:p-5 space-y-4">
      <div class="text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Client details</div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label class="{{ $labelCls }}">Name <span class="text-rose-500">*</span></label>
          <input type="text" name="name" required maxlength="120"
                 value="{{ old('name', $client?->name) }}"
                 class="{{ $inputCls }}" placeholder="e.g. National Oil Corporation" />
        </div>

        <div>
          <label class="{{ $labelCls }}">Code</label>
          <input type="text" name="code" maxlength="50"
                 value="{{ old('code', $client?->code) }}"
                 class="{{ $inputCls }}" placeholder="e.g. NOC-001" />
        </div>

        <div>
          <label class="{{ $labelCls }}">Type</label>
          <select name="type" class="{{ $inputCls }}">
            @foreach($typeOptions as $val => $label)
              <option value="{{ $val }}" @selected(old('type', $client?->type) == $val)>{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="{{ $labelCls }}">Country</label>
          <input type="text" name="country" maxlength="100"
                 value="{{ old('country', $client?->country) }}"
                 class="{{ $inputCls }}" placeholder="e.g. Libya" />
        </div>

        <div>
          <label class="{{ $labelCls }}">City</label>
          <input type="text" name="city" maxlength="100"
                 value="{{ old('city', $client?->city) }}"
                 class="{{ $inputCls }}" placeholder="e.g. Tripoli" />
        </div>
      </div>
    </div>

    {{-- Contact --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 sm:p-5 space-y-4">
      <div class="text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Contact</div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label class="{{ $labelCls }}">Contact person</label>
          <input type="text" name="contact_person" maxlength="150"
                 value="{{ old('contact_person', $client?->contact_person) }}"
                 class="{{ $inputCls }}" placeholder="e.g. Ahmed Al-Mansouri" />
        </div>

        <div>
          <label class="{{ $labelCls }}">Phone</label>
          <input type="text" name="phone" maxlength="50"
                 value="{{ old('phone', $client?->phone) }}"
                 class="{{ $inputCls }}" placeholder="+218 91 …" />
        </div>

        <div>
          <label class="{{ $labelCls }}">Email</label>
          <input type="email" name="email" maxlength="150"
                 value="{{ old('email', $client?->email) }}"
                 class="{{ $inputCls }}" placeholder="contact@client.com" />
        </div>
      </div>
    </div>

    {{-- Financial & status --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4 sm:p-5 space-y-4">
      <div class="text-[11px] font-semibold {{ $muted }} uppercase tracking-wide">Financial &amp; Status</div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="{{ $labelCls }}">Currency</label>
          <input type="text" name="currency" maxlength="3"
                 value="{{ old('currency', $client?->currency ?? 'USD') }}"
                 class="{{ $inputCls }}" placeholder="USD" />
        </div>

        <div>
          <label class="{{ $labelCls }}">Credit limit</label>
          <input type="number" name="credit_limit" step="0.01" min="0"
                 value="{{ old('credit_limit', $client?->credit_limit ?? 0) }}"
                 class="{{ $inputCls }}" placeholder="0.00" />
        </div>

        @if($isEdit)
          <div class="flex items-end pb-0.5">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="is_active" value="1"
                     @checked(old('is_active', $client?->is_active ?? true))
                     class="h-4 w-4 rounded border-[color:var(--tw-border)]" />
              <span class="text-sm font-semibold {{ $fg }}">Active</span>
            </label>
          </div>
        @endif
      </div>

      <div>
        <label class="{{ $labelCls }}">Notes</label>
        <textarea name="notes" rows="3" maxlength="2000"
                  class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} {{ $ring }} focus:outline-none resize-none"
                  placeholder="Internal notes about this client…">{{ old('notes', $client?->notes) }}</textarea>
      </div>

      @if(!$isEdit)
      {{-- Opening balance — only shown when creating --}}
      <div class="rounded-xl border border-dashed {{ $border }} p-4 space-y-3">
        <p class="text-xs font-semibold {{ $muted }}">Opening Balance
          <span class="font-normal opacity-60">— leave blank if this is a brand-new client</span>
        </p>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="{{ $labelCls }}">Amount client owes us</label>
            <input type="number" name="opening_balance" step="0.01" min="0"
                   value="{{ old('opening_balance') }}"
                   placeholder="0.00"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} {{ $ring }} focus:outline-none">
          </div>
          <div>
            <label class="{{ $labelCls }}">As of date</label>
            <input type="date" name="opening_balance_date"
                   value="{{ old('opening_balance_date', now()->format('Y-m-d')) }}"
                   class="w-full rounded-xl border {{ $border }} {{ $surface2 }} px-3 py-2 text-sm {{ $fg }} {{ $ring }} focus:outline-none">
          </div>
        </div>
      </div>
      @endif
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-2">
      <a href="{{ $isEdit ? route('clients.show', $client) : route('clients.index') }}"
         class="{{ $btnGhost }} h-10 px-4 text-[13px]">Cancel</a>
      <button type="submit"
              class="inline-flex items-center justify-center gap-2 h-10 px-5 rounded-xl border
                     border-[color:var(--tw-accent)] bg-[color:var(--tw-accent)] text-black
                     text-[13px] font-semibold hover:brightness-110 transition">
        {{ $isEdit ? 'Save changes' : 'Create client' }}
      </button>
    </div>
  </form>

</div>
@endsection
