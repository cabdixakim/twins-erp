@extends('layouts.app')
@section('title','Documents')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';

    $catMeta = [
        'tr8'      => ['label' => 'TR8',      'class' => 's-purple'],
        't1'       => ['label' => 'T1',       'class' => 's-blue'],
        'customs'  => ['label' => 'Customs',  'class' => 's-amber'],
        'invoice'  => ['label' => 'Invoice',  'class' => 's-green'],
        'permit'   => ['label' => 'Permit',   'class' => 's-orange'],
        'contract' => ['label' => 'Contract', 'class' => 's-slate'],
        'other'    => ['label' => 'Other',    'class' => 's-slate'],
    ];
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold {{ $fg }}">Documents</h1>
            <p class="{{ $muted }} text-sm mt-0.5">All uploaded files across purchases and trucks</p>
        </div>
        <a href="{{ route('documents.create') }}"
           class="h-9 px-4 rounded-xl border text-sm font-semibold transition btn-soft-green flex items-center gap-2">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Upload
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex items-center gap-2 flex-wrap">
        <select name="category"
                onchange="this.form.submit()"
                class="h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
            <option value="">All categories</option>
            @foreach(\App\Models\Document::$categories as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                    {{ ucfirst($cat) }}
                </option>
            @endforeach
        </select>
        @if(request()->anyFilled(['category']))
            <a href="{{ route('documents.index') }}" class="h-9 px-3 rounded-xl border {{ $border }} text-sm {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition flex items-center">
                Clear
            </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
        @if($documents->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-10 h-10 mx-auto {{ $muted }} opacity-40 mb-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="{{ $muted }} text-sm">No documents yet</p>
                <p class="{{ $muted }} text-xs mt-1 opacity-70">Documents uploaded during border clearance or from purchase pages will appear here</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b {{ $border }} {{ $surface2 }}">
                        <th class="text-left px-4 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider">File</th>
                        <th class="text-left px-3 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider">Category</th>
                        <th class="text-left px-3 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider hidden md:table-cell">Attached to</th>
                        <th class="text-left px-3 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider hidden md:table-cell">Uploaded</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                    @php
                        $cm = $catMeta[$doc->category] ?? $catMeta['other'];
                    @endphp
                    <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-semibold {{ $fg }} truncate max-w-[200px]">{{ $doc->name }}</div>
                            <div class="text-xs {{ $muted }}">{{ $doc->file_size_human }}</div>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $cm['class'] }}">
                                {{ $cm['label'] }}
                            </span>
                        </td>
                        <td class="px-3 py-3 hidden md:table-cell">
                            @php $morphed = $doc->documentable @endphp
                            @if($morphed instanceof \App\Models\ImportTruck)
                                <div class="text-xs {{ $fg }}">Truck: {{ $morphed->truck_reg }}</div>
                                <div class="text-xs {{ $muted }}">{{ $morphed->nomination?->purchase?->reference ?? '—' }}</div>
                            @elseif($morphed instanceof \App\Models\Purchase)
                                <div class="text-xs {{ $fg }}">Purchase: {{ $morphed->reference }}</div>
                            @else
                                <span class="{{ $muted }}">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 hidden md:table-cell">
                            <div class="text-xs {{ $fg }}">{{ $doc->created_at->format('d M Y') }}</div>
                            <div class="text-xs {{ $muted }}">{{ $doc->uploader?->name ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('documents.download', $doc) }}"
                                   class="h-8 px-3 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3m0 12l-4-4m4 4l4-4M3 18h18v2H3z"/>
                                    </svg>
                                    Download
                                </a>
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                      onsubmit="return confirm('Delete this document?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="h-8 px-3 rounded-xl border text-xs font-semibold transition btn-soft-rose">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($documents->hasPages())
                <div class="px-4 py-3 border-t {{ $border }}">
                    {{ $documents->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
