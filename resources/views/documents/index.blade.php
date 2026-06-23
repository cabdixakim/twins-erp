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
                    @php $cm = $catMeta[$doc->category] ?? $catMeta['other']; @endphp
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
                                {{-- Hidden delete form, submitted by modal confirm --}}
                                <form id="del-form-{{ $doc->id }}" method="POST"
                                      action="{{ route('documents.destroy', $doc) }}" class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button"
                                        onclick="openDocDeleteModal({{ $doc->id }}, '{{ addslashes($doc->name) }}')"
                                        class="h-8 px-3 rounded-xl border text-xs font-semibold transition btn-soft-rose flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete
                                </button>
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

{{-- ── Delete Confirmation Modal ─────────────────────────── --}}
<div id="doc-delete-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"
     onclick="if(event.target===this)closeDocDeleteModal()">
    <div class="relative w-full max-w-sm rounded-2xl border shadow-2xl bg-[color:var(--tw-surface)] border-[color:var(--tw-border)] p-6 space-y-5"
         onclick="event.stopPropagation()">

        {{-- Icon --}}
        <div class="flex items-center justify-center w-12 h-12 rounded-2xl mx-auto"
             style="background:rgba(239,68,68,.12)">
            <svg class="w-6 h-6" style="color:#ef4444" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>

        {{-- Text --}}
        <div class="text-center space-y-1">
            <h3 class="text-base font-bold text-[color:var(--tw-fg)]">Delete document?</h3>
            <p class="text-sm text-[color:var(--tw-muted)]">
                "<span id="doc-delete-name" class="font-semibold text-[color:var(--tw-fg)]"></span>"
                will be permanently removed. This cannot be undone.
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="button"
                    onclick="closeDocDeleteModal()"
                    class="flex-1 h-10 rounded-xl border border-[color:var(--tw-border)] text-sm font-semibold text-[color:var(--tw-fg)] hover:bg-[color:var(--tw-surface-2)] transition">
                Cancel
            </button>
            <button type="button"
                    id="doc-delete-confirm-btn"
                    onclick="submitDocDelete()"
                    class="flex-1 h-10 rounded-xl text-sm font-semibold text-white transition"
                    style="background:#ef4444">
                Yes, delete
            </button>
        </div>
    </div>
</div>

<script>
let _docDeleteId = null;

function openDocDeleteModal(id, name) {
    _docDeleteId = id;
    document.getElementById('doc-delete-name').textContent = name;
    const modal = document.getElementById('doc-delete-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeDocDeleteModal() {
    _docDeleteId = null;
    const modal = document.getElementById('doc-delete-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

function submitDocDelete() {
    if (!_docDeleteId) return;
    const btn = document.getElementById('doc-delete-confirm-btn');
    btn.disabled = true;
    btn.textContent = 'Deleting…';
    document.getElementById('del-form-' + _docDeleteId).submit();
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDocDeleteModal();
});
</script>
@endsection
