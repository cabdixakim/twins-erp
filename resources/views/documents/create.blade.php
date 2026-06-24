@extends('layouts.app')
@section('title','Upload Document')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';

    $categories = [
        'trade_license'         => 'Trade Licence',
        'insurance_certificate' => 'Insurance Certificate',
        'tax_clearance'         => 'Tax Clearance',
        'company_registration'  => 'Company Registration',
        'contract'              => 'Contract',
        'certificate'           => 'Certificate',
        'company_profile'       => 'Company Profile',
        'permit'                => 'Permit',
        'other'                 => 'Other',
    ];
@endphp

<div class="max-w-2xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('documents.index') }}"
           class="h-8 w-8 rounded-xl border {{ $border }} flex items-center justify-center {{ $muted }} hover:bg-[color:var(--tw-surface-2)] transition">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold {{ $fg }}">Upload Company Document</h1>
            <p class="{{ $muted }} text-sm mt-0.5">Add a certificate, licence, contract or any important company file</p>
        </div>
    </div>

    @if($errors->any())
    <div class="rounded-xl border border-rose-300 px-4 py-3 text-sm text-rose-700 space-y-1" style="background:rgba(239,68,68,.06)">
        @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
    @endif

    @if(session('success'))
    <div class="rounded-xl border border-emerald-300 px-4 py-3 text-sm text-emerald-700" style="background:rgba(16,185,129,.06)">
        {{ session('success') }}
    </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
          class="rounded-2xl border {{ $border }} {{ $surface }} divide-y divide-[color:var(--tw-border)]">
        @csrf

        {{-- File drop zone --}}
        <div class="p-5 space-y-2">
            <label class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">File <span class="text-rose-500">*</span></label>
            <div id="drop-zone"
                 class="relative flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-8 transition cursor-pointer"
                 style="border-color:var(--tw-border)"
                 onclick="document.getElementById('file-input').click()">
                <svg class="w-9 h-9 {{ $muted }} opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm {{ $muted }}">Click to select a file, or drag &amp; drop</p>
                <p class="text-xs {{ $muted }} opacity-60">PDF, Word, Excel or image · max 20 MB</p>
                <p id="file-name" class="hidden text-sm font-semibold" style="color:var(--tw-accent)"></p>
            </div>
            <input type="file" id="file-input" name="file" class="hidden"
                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                   onchange="showFileName(this)">
        </div>

        {{-- Display name --}}
        <div class="p-5 space-y-1">
            <label for="name" class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">
                Display Name <span class="{{ $muted }} font-normal normal-case">(optional — uses filename if blank)</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                   placeholder="e.g. Trade Licence 2025–2026"
                   class="mt-1.5 w-full h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
        </div>

        {{-- Category --}}
        <div class="p-5 space-y-1">
            <label for="category" class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">Category <span class="text-rose-500">*</span></label>
            <select id="category" name="category"
                    class="mt-1.5 w-full h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
                <option value="">Select category…</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Validity dates --}}
        <div class="p-5 space-y-3">
            <div>
                <p class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">Validity / Expiry Dates <span class="{{ $muted }} font-normal normal-case">(optional)</span></p>
                <p class="text-xs {{ $muted }} mt-0.5">Set these to receive expiry alerts before the document lapses</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label for="valid_from" class="text-xs {{ $muted }}">Valid from</label>
                    <input type="date" id="valid_from" name="valid_from" value="{{ old('valid_from') }}"
                           class="w-full h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
                </div>
                <div class="space-y-1">
                    <label for="valid_until" class="text-xs {{ $muted }}">Expires on</label>
                    <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until') }}"
                           class="w-full h-10 px-3 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="p-5 space-y-1">
            <label for="notes" class="text-xs font-semibold {{ $muted }} uppercase tracking-wider">Notes <span class="{{ $muted }} font-normal normal-case">(optional)</span></label>
            <textarea id="notes" name="notes" rows="3"
                      placeholder="Any relevant details, licence number, issuing authority…"
                      class="mt-1.5 w-full px-3 py-2 rounded-xl border {{ $border }} {{ $surface2 }} text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40 resize-none">{{ old('notes') }}</textarea>
        </div>

        {{-- Actions --}}
        <div class="p-5 flex items-center justify-end gap-3">
            <a href="{{ route('documents.index') }}"
               class="h-10 px-5 rounded-xl border {{ $border }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition">
                Cancel
            </a>
            <button type="submit"
                    class="h-10 px-6 rounded-xl text-sm font-semibold text-white transition"
                    style="background:var(--tw-accent)">
                Upload Document
            </button>
        </div>
    </form>
</div>

<script>
function showFileName(input) {
    const el = document.getElementById('file-name');
    if (input.files[0]) {
        el.textContent = input.files[0].name;
        el.classList.remove('hidden');
    } else {
        el.textContent = '';
        el.classList.add('hidden');
    }
}

const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover',  e => { e.preventDefault(); dz.style.borderColor = 'var(--tw-accent)'; });
dz.addEventListener('dragleave', () => { dz.style.borderColor = 'var(--tw-border)'; });
dz.addEventListener('drop', e => {
    e.preventDefault();
    dz.style.borderColor = 'var(--tw-border)';
    const fi = document.getElementById('file-input');
    if (e.dataTransfer.files.length) {
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        fi.files = dt.files;
        showFileName(fi);
    }
});
</script>
@endsection
