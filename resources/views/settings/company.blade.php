@php
    $title = 'Company profile';
    $subtitle = 'Branding, base currency and home timezone for Twins.';
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

<div class="grid gap-6 lg:grid-cols-[260px,minmax(0,1fr)]">

    {{-- LEFT: small summary --}}
    <div class="space-y-4">

        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-slate-800 flex items-center justify-center overflow-hidden">
                    @if($company->logo_path)
                        <img src="{{ asset('storage/'.$company->logo_path) }}"
                             alt="Logo"
                             class="w-full h-full object-cover">
                    @else
                        <span class="text-lg">🏭</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="text-xs uppercase tracking-wide text-slate-500 mb-0.5">
                        Company
                    </div>
                    <div class="text-sm font-semibold truncate">
                        {{ $company->name ?? 'Not set' }}
                    </div>
                    <div class="text-[11px] text-slate-400 truncate">
                        {{ $company->country ?: 'Country not set' }}
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1 text-[11px] text-slate-400">
                <div>
                    Base currency:
                    <span class="font-semibold text-slate-100">
                        {{ $company->base_currency ?: 'Not set' }}
                    </span>
                </div>
                <div>
                    Timezone:
                    <span class="font-semibold text-slate-100">
                        {{ $company->timezone ?: 'Not set' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-3 text-[11px] text-slate-400">
            <div class="font-semibold text-slate-100 mb-1 text-xs">Tips</div>
            <ul class="space-y-1 list-disc list-inside">
                <li>Logo appears on invoices and statements.</li>
                <li>Base currency is used as default when creating documents.</li>
                <li>Timezone helps align ETAs and reports.</li>
            </ul>
        </div>

    </div>

    {{-- RIGHT: edit form --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
        <h2 class="text-sm font-semibold mb-1">Edit company profile</h2>

        <form method="POST"
              action="{{ route('settings.company.update') }}"
              enctype="multipart/form-data"
              class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-[11px] text-slate-400 mb-1">Trading name *</label>
                <input type="text" name="name"
                       value="{{ old('name', $company->name) }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                @error('name')
                    <div class="mt-1 text-[11px] text-rose-400">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Base currency</label>
                    <input type="text" name="base_currency"
                           placeholder="USD, ZMW, CDF..."
                           value="{{ old('base_currency', $company->base_currency) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                    @error('base_currency')
                        <div class="mt-1 text-[11px] text-rose-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Country</label>
                    <input type="text" name="country"
                           value="{{ old('country', $company->country) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                    @error('country')
                        <div class="mt-1 text-[11px] text-rose-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Timezone</label>
                    <input type="text" name="timezone"
                           placeholder="Africa/Lubumbashi"
                           value="{{ old('timezone', $company->timezone) }}"
                           class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-emerald-500/40">
                    @error('timezone')
                        <div class="mt-1 text-[11px] text-rose-400">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr),160px] items-center">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1">Logo</label>
                  <input
  type="file"
  name="logo"
  accept="image/*"
  class="block w-full text-[11px] text-slate-300
         file:mr-3 file:rounded-xl file:border-0
         file:bg-slate-800 file:px-3 file:py-2
         file:text-xs file:font-semibold file:text-slate-100
         hover:file:bg-slate-700
         cursor-pointer"
/>
                       <span class="text-[11px] text-slate-400"> PNG/JPG, max 2MB. Optional.</span>
                    </p>
                    @error('logo')
                        <div class="mt-1 text-[11px] text-rose-400">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex justify-center">
                    <div class="w-24 h-24 rounded-2xl border border-slate-700 bg-slate-950 flex items-center justify-center overflow-hidden">
                        @if($company->logo_path)
                            <img src="{{ asset('storage/'.$company->logo_path) }}"
                                 alt="Logo preview"
                                 class="w-full h-full object-contain">
                        @else
                            <span class="text-xs text-slate-500 text-center px-2">
                                Logo preview
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-500 hover:bg-emerald-400 text-slate-950">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection