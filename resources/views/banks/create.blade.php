@php
    $border    = 'border-[color:var(--tw-border)]';
    $surface   = 'bg-[color:var(--tw-surface)]';
    $surface2  = 'bg-[color:var(--tw-surface-2)]';
    $fg        = 'text-[color:var(--tw-fg)]';
    $muted     = 'text-[color:var(--tw-muted)]';
    $fieldBase = 'w-full rounded-xl border ' . $border . ' ' . $surface . ' px-3 py-2 text-sm ' . $fg . ' focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40';
    $fieldErr  = 'border-rose-500/60 focus:ring-rose-500/30';
    $errText   = 'mt-1 text-[11px] text-rose-500';
    $label     = 'block text-xs font-semibold ' . $fg . ' mb-1';
    $isEdit    = isset($bank);
@endphp

@extends('layouts.app')
@section('title', $isEdit ? 'Edit Bank Account' : 'Add Bank Account')
@section('subtitle', $isEdit ? 'Update account details.' : 'Add a new bank account to track.')

@section('content')

<div class="max-w-lg">
    <a href="{{ route('banks.index') }}"
       class="inline-flex items-center gap-1.5 text-xs {{ $muted }} hover:text-[color:var(--tw-fg)] transition mb-5">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Bank accounts
    </a>

    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-6">
        <h1 class="text-base font-bold {{ $fg }} mb-5">{{ $isEdit ? 'Edit Account' : 'New Bank Account' }}</h1>

        <form method="POST"
              action="{{ $isEdit ? route('banks.update', $bank) : route('banks.store') }}"
              class="space-y-4">
            @csrf
            @if($isEdit) @method('PATCH') @endif

            <div>
                <label class="{{ $label }}">Account name *</label>
                <input type="text" name="name" value="{{ old('name', $bank->name ?? '') }}"
                       class="{{ $fieldBase }} @error('name') {{ $fieldErr }} @enderror"
                       placeholder="e.g. Rawbank USD Operating">
                @error('name') <div class="{{ $errText }}">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="{{ $label }}">Bank name</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $bank->bank_name ?? '') }}"
                       class="{{ $fieldBase }} @error('bank_name') {{ $fieldErr }} @enderror"
                       placeholder="e.g. Rawbank">
                @error('bank_name') <div class="{{ $errText }}">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="{{ $label }}">Account number</label>
                <input type="text" name="account_number" value="{{ old('account_number', $bank->account_number ?? '') }}"
                       class="{{ $fieldBase }} @error('account_number') {{ $fieldErr }} @enderror"
                       placeholder="Optional">
                @error('account_number') <div class="{{ $errText }}">{{ $message }}</div> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $label }}">Currency *</label>
                    <select name="currency"
                            class="{{ $fieldBase }} @error('currency') {{ $fieldErr }} @enderror">
                        @foreach(['USD','EUR','GBP','ZAR','CDF','ZMW','ZWL'] as $c)
                            <option value="{{ $c }}" @selected(old('currency', $bank->currency ?? 'USD') === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('currency') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="{{ $label }}">Opening balance</label>
                    <input type="number" name="opening_balance" step="0.01"
                           value="{{ old('opening_balance', $bank->opening_balance ?? 0) }}"
                           class="{{ $fieldBase }} @error('opening_balance') {{ $fieldErr }} @enderror">
                    @error('opening_balance') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit"
                        class="h-9 px-5 rounded-xl border border-[color:var(--tw-accent)]/40 bg-[color:var(--tw-accent)]/10 text-xs font-semibold text-[color:var(--tw-accent)] hover:bg-[color:var(--tw-accent)]/20 transition">
                    {{ $isEdit ? 'Save changes' : 'Create account' }}
                </button>
                <a href="{{ route('banks.index') }}"
                   class="h-9 px-4 rounded-xl border {{ $border }} {{ $surface }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition inline-flex items-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
