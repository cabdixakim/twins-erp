@extends('layouts.standalone')

@section('title', 'Switch company · Twins')

@section('content')
@php
    $u = auth()->user();

    $companies = (method_exists($u, 'companies'))
        ? $u->companies()->orderBy('name')->get()
        : collect();

    $activeId = (int) ($u->active_company_id ?? 0);

    $activeCompany = $activeId
        ? $companies->firstWhere('id', $activeId)
        : null;

    $isOwner = ($u->role?->slug ?? null) === 'owner';
@endphp

<div class="w-full max-w-3xl">
    <div class="mb-6 text-center">
        <div class="text-2xl font-semibold tracking-tight">Switch company</div>
        <div class="mt-1 text-sm text-slate-400">
            Choose the workspace you want to operate in.
        </div>

        @if($activeCompany)
            <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/50 px-3 py-1 text-xs text-slate-300">
                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                Active: <span class="text-slate-100 font-medium">{{ $activeCompany->name }}</span>
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-800">
            <div class="text-sm text-slate-300">
                {{ $companies->count() }} {{ $companies->count() === 1 ? 'company' : 'companies' }}
            </div>

            <div class="flex items-center gap-2">
                @if($isOwner)
                    <a href="{{ route('company.create') }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900/50 px-3 py-2 text-xs text-slate-200 hover:bg-slate-800 transition">
                        <span class="text-lg leading-none">+</span>
                        Create company
                    </a>
                @endif

                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs text-slate-300 hover:text-white transition">
                    Back
                </a>
            </div>
        </div>

        <div class="p-5">
            @if($companies->isEmpty())
                <div class="rounded-xl border border-slate-800 bg-slate-900/30 p-4 text-sm text-slate-300">
                    No companies found for your user.
                </div>
            @else
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($companies as $c)
                        @php $isActive = ((int)$c->id === $activeId); @endphp

                        <a href="{{ route('companies.switch', $c) }}"
                           class="group rounded-2xl border border-slate-800 bg-slate-900/20 p-4 hover:bg-slate-900/40 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-slate-100">
                                        {{ $c->name }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-400">
                                        ID: {{ $c->id }}
                                    </div>
                                </div>

                                <div class="shrink-0">
                                    @if($isActive)
                                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 border border-emerald-500/30 px-3 py-1 text-xs text-emerald-300">
                                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border border-slate-700 px-3 py-1 text-xs text-slate-300 group-hover:text-white transition">
                                            Switch
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 text-xs text-slate-500">
                                Click to set as active company
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mt-6 text-center text-xs text-slate-500">
        Twins ERP · Company context controls what you see and create.
    </div>
</div>
@endsection