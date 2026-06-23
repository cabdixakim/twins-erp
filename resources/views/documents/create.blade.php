@extends('layouts.app')
@section('title','Upload Document')

@section('content')
@php
    $fg      = 'text-[color:var(--tw-fg)]';
    $muted   = 'text-[color:var(--tw-muted)]';
    $border  = 'border-[color:var(--tw-border)]';
    $surface = 'bg-[color:var(--tw-surface)]';
    $surface2= 'bg-[color:var(--tw-surface-2)]';
@endphp

<div class="max-w-lg mx-auto space-y-6">
    <div>
        <a href="{{ route('documents.index') }}" class="text-sm {{ $muted }} hover:text-[color:var(--tw-fg)] flex items-center gap-1.5 mb-4">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/>
            </svg>
            Documents
        </a>
        <h1 class="text-xl font-bold {{ $fg }}">Upload Document</h1>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-600">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
          class="rounded-2xl border {{ $border }} {{ $surface }} p-6 space-y-4">
        @csrf

        <input type="hidden" name="documentable_type" value="App\Models\Purchase">
        <input type="hidden" name="documentable_id" value="0">

        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">File <span class="text-rose-400">*</span></label>
            <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                   class="w-full text-sm {{ $fg }} file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:btn-soft-green file:cursor-pointer">
            <p class="text-xs {{ $muted }} mt-1">PDF, images, Word, Excel — max 20 MB</p>
        </div>

        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Category <span class="text-rose-400">*</span></label>
            <select name="category" required
                    class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
                @foreach(\App\Models\Document::$categories as $cat)
                    <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Display name <span class="{{ $muted }} font-normal">(optional)</span></label>
            <input type="text" name="name" maxlength="255" placeholder="Leave blank to use filename"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
        </div>

        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1">Notes <span class="{{ $muted }} font-normal">(optional)</span></label>
            <input type="text" name="notes" maxlength="500" placeholder="Any notes about this document"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
        </div>

        <div class="pt-2 flex gap-3">
            <button type="submit"
                    class="h-10 px-5 rounded-xl border text-sm font-bold transition btn-soft-green">
                Upload document
            </button>
            <a href="{{ route('documents.index') }}"
               class="h-10 px-5 rounded-xl border {{ $border }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition flex items-center">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
