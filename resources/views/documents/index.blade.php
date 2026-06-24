@extends('layouts.app')
@section('title','Company Documents')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';

    $catMeta = [
        'trade_license'         => ['label' => 'Trade Licence',    'class' => 's-blue'],
        'insurance_certificate' => ['label' => 'Insurance',        'class' => 's-purple'],
        'tax_clearance'         => ['label' => 'Tax Clearance',    'class' => 's-green'],
        'company_registration'  => ['label' => 'Registration',     'class' => 's-amber'],
        'contract'              => ['label' => 'Contract',         'class' => 's-slate'],
        'certificate'           => ['label' => 'Certificate',      'class' => 's-blue'],
        'company_profile'       => ['label' => 'Company Profile',  'class' => 's-green'],
        'permit'                => ['label' => 'Permit',           'class' => 's-orange'],
        'other'                 => ['label' => 'Other',            'class' => 's-slate'],
    ];
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold {{ $fg }}">Company Documents</h1>
            <p class="{{ $muted }} text-sm mt-0.5">Certificates, licences, contracts and key company files</p>
        </div>
        <a href="{{ route('documents.create') }}"
           class="h-9 px-4 rounded-xl border text-sm font-semibold transition btn-soft-green flex items-center gap-2">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
            </svg>
            Upload
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="rounded-xl border border-emerald-300 px-4 py-3 text-sm text-emerald-700" style="background:rgba(16,185,129,.06)">
        {{ session('success') }}
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex items-center gap-2 flex-wrap">
        <select name="category" onchange="this.form.submit()"
                class="h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
            <option value="">All categories</option>
            @foreach(\App\Models\Document::$categories as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                    {{ $catMeta[$cat]['label'] ?? ucfirst(str_replace('_',' ',$cat)) }}
                </option>
            @endforeach
        </select>

        <select name="status" onchange="this.form.submit()"
                class="h-9 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
            <option value="">All statuses</option>
            <option value="expired"       {{ request('status') === 'expired'       ? 'selected' : '' }}>Expired</option>
            <option value="expiring_soon" {{ request('status') === 'expiring_soon' ? 'selected' : '' }}>Expiring soon (≤30 days)</option>
            <option value="valid"         {{ request('status') === 'valid'         ? 'selected' : '' }}>Valid</option>
            <option value="no_expiry"     {{ request('status') === 'no_expiry'     ? 'selected' : '' }}>No expiry set</option>
        </select>

        @if(request()->anyFilled(['category','status']))
            <a href="{{ route('documents.index') }}"
               class="h-9 px-3 rounded-xl border {{ $border }} text-sm {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition flex items-center">
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
                <p class="{{ $muted }} text-sm">No documents in the vault yet</p>
                <p class="{{ $muted }} text-xs mt-1 opacity-70">Upload licences, certificates and key company documents to track their expiry dates</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b {{ $border }} {{ $surface2 }}">
                        <th class="text-left px-4 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider">Document</th>
                        <th class="text-left px-3 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider">Category</th>
                        <th class="text-left px-3 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider">Validity</th>
                        <th class="text-left px-3 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider hidden lg:table-cell">Uploaded</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold {{ $muted }} uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                    @php
                        $cm     = $catMeta[$doc->category] ?? $catMeta['other'];
                        $status = $doc->expiry_status;
                        $days   = $doc->days_until_expiry;
                    @endphp
                    <tr class="border-b {{ $border }} last:border-0 hover:bg-[color:var(--tw-surface-2)] transition-colors">

                        {{-- Name + size --}}
                        <td class="px-4 py-3">
                            <div class="font-semibold {{ $fg }} truncate max-w-[220px]" title="{{ $doc->name }}">{{ $doc->name }}</div>
                            <div class="text-xs {{ $muted }}">{{ $doc->file_size_human }}</div>
                            @if($doc->notes)
                            <div class="text-xs {{ $muted }} opacity-70 truncate max-w-[220px] mt-0.5" title="{{ $doc->notes }}">{{ $doc->notes }}</div>
                            @endif
                        </td>

                        {{-- Category badge --}}
                        <td class="px-3 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $cm['class'] }}">
                                {{ $cm['label'] }}
                            </span>
                        </td>

                        {{-- Validity --}}
                        <td class="px-3 py-3 whitespace-nowrap">
                            @if($status === 'expired')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold s-rose">
                                    Expired {{ $doc->valid_until?->format('d M Y') }}
                                </span>
                            @elseif($status === 'expiring_soon')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-semibold s-amber">
                                    Expires in {{ $days }}d &mdash; {{ $doc->valid_until?->format('d M Y') }}
                                </span>
                            @elseif($status === 'valid')
                                <div class="text-xs {{ $fg }}">{{ $doc->valid_from?->format('d M Y') ?? '—' }} → {{ $doc->valid_until?->format('d M Y') }}</div>
                                <div class="text-xs {{ $muted }}">{{ $days }}d remaining</div>
                            @else
                                <span class="text-xs {{ $muted }}">—</span>
                            @endif
                        </td>

                        {{-- Uploaded --}}
                        <td class="px-3 py-3 hidden lg:table-cell">
                            <div class="text-xs {{ $fg }}">{{ $doc->created_at->format('d M Y') }}</div>
                            <div class="text-xs {{ $muted }}">{{ $doc->uploader?->name ?? '—' }}</div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('documents.download', $doc) }}"
                                   class="h-8 px-3 rounded-xl border {{ $border }} text-xs font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15V3m0 12l-4-4m4 4l4-4M3 18h18v2H3z"/>
                                    </svg>
                                    Download
                                </a>
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

{{-- Delete modal --}}
<div id="doc-delete-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"
     onclick="if(event.target===this)closeDocDeleteModal()">
    <div class="relative w-full max-w-sm rounded-2xl border shadow-2xl p-6 space-y-5"
         style="background:var(--tw-surface);border-color:var(--tw-border)"
         onclick="event.stopPropagation()">
        <div class="flex items-center justify-center w-12 h-12 rounded-2xl mx-auto"
             style="background:rgba(239,68,68,.12)">
            <svg class="w-6 h-6" style="color:#ef4444" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <div class="text-center space-y-1">
            <h3 class="text-base font-bold" style="color:var(--tw-fg)">Delete document?</h3>
            <p class="text-sm" style="color:var(--tw-muted)">
                "<span id="doc-delete-name" class="font-semibold" style="color:var(--tw-fg)"></span>"
                will be permanently removed.
            </p>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="closeDocDeleteModal()"
                    class="flex-1 h-10 rounded-xl border text-sm font-semibold transition hover:bg-[color:var(--tw-surface-2)]"
                    style="border-color:var(--tw-border);color:var(--tw-fg)">
                Cancel
            </button>
            <button type="button" id="doc-delete-confirm-btn" onclick="submitDocDelete()"
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
    const m = document.getElementById('doc-delete-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeDocDeleteModal() {
    _docDeleteId = null;
    const m = document.getElementById('doc-delete-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
function submitDocDelete() {
    if (!_docDeleteId) return;
    const btn = document.getElementById('doc-delete-confirm-btn');
    btn.disabled = true; btn.textContent = 'Deleting…';
    document.getElementById('del-form-' + _docDeleteId).submit();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDocDeleteModal(); });
</script>
@endsection
