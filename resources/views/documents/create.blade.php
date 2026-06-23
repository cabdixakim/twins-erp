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
        <p class="text-sm {{ $muted }} mt-0.5">Attach a file to a truck, a purchase, or save as a standalone document.</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-600">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
          class="rounded-2xl border {{ $border }} {{ $surface }} p-6 space-y-5">
        @csrf

        {{-- File --}}
        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1.5">
                File <span class="text-rose-400">*</span>
            </label>
            <input type="file" name="file" required
                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                   class="w-full text-sm {{ $fg }} file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:btn-soft-green file:cursor-pointer">
            <p class="text-xs {{ $muted }} mt-1">PDF, images, Word, Excel — max 20 MB</p>
        </div>

        {{-- Category --}}
        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1.5">
                Category <span class="text-rose-400">*</span>
            </label>
            <select name="category" required
                    class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
                @foreach(\App\Models\Document::$categories as $cat)
                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                        {{ ucfirst($cat) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Attach to --}}
        <div class="space-y-3">
            <label class="block text-xs font-semibold {{ $fg }}">
                Attach to <span class="{{ $muted }} font-normal">(optional)</span>
            </label>

            {{-- Type selector --}}
            <div class="flex gap-2">
                <button type="button" onclick="setAttachTo('')"
                        id="attach-btn-none"
                        class="flex-1 h-9 rounded-xl border text-xs font-semibold transition attach-type-btn active-attach"
                        style="">
                    None
                </button>
                <button type="button" onclick="setAttachTo('truck')"
                        id="attach-btn-truck"
                        class="flex-1 h-9 rounded-xl border text-xs font-semibold transition attach-type-btn">
                    Truck
                </button>
                <button type="button" onclick="setAttachTo('purchase')"
                        id="attach-btn-purchase"
                        class="flex-1 h-9 rounded-xl border text-xs font-semibold transition attach-type-btn">
                    Purchase
                </button>
            </div>

            <input type="hidden" name="attach_to" id="attach-to-input" value="">

            {{-- Truck picker --}}
            <div id="picker-truck" class="hidden">
                <select name="documentable_id" id="truck-select"
                        class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
                    <option value="">Select a truck…</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}">
                            {{ $truck->truck_reg }}
                            @if($truck->nomination?->purchase)
                                — {{ $truck->nomination->purchase->reference }}
                            @endif
                            ({{ ucfirst(str_replace('_',' ',$truck->status)) }})
                        </option>
                    @endforeach
                </select>
                @if($trucks->isEmpty())
                    <p class="text-xs {{ $muted }} mt-1">No trucks found for this company.</p>
                @endif
            </div>

            {{-- Purchase picker --}}
            <div id="picker-purchase" class="hidden">
                <select name="documentable_id" id="purchase-select"
                        class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
                    <option value="">Select a purchase…</option>
                    @foreach($purchases as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->reference }} — {{ ucfirst(str_replace('_',' ',$p->type)) }}
                            ({{ ucfirst($p->status) }})
                        </option>
                    @endforeach
                </select>
                @if($purchases->isEmpty())
                    <p class="text-xs {{ $muted }} mt-1">No active purchases found.</p>
                @endif
            </div>
        </div>

        {{-- Display name --}}
        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1.5">
                Display name <span class="{{ $muted }} font-normal">(optional — leave blank to use filename)</span>
            </label>
            <input type="text" name="name" maxlength="255" value="{{ old('name') }}"
                   placeholder="e.g. TR8 clearance doc"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
        </div>

        {{-- Notes --}}
        <div>
            <label class="block text-xs font-semibold {{ $fg }} mb-1.5">
                Notes <span class="{{ $muted }} font-normal">(optional)</span>
            </label>
            <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}"
                   placeholder="Any notes about this document"
                   class="w-full h-10 rounded-xl border {{ $border }} {{ $surface2 }} px-3 text-sm {{ $fg }} focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent)]/40">
        </div>

        <div class="pt-1 flex gap-3">
            <button type="submit"
                    class="h-10 px-6 rounded-xl border text-sm font-bold transition btn-soft-green">
                Upload document
            </button>
            <a href="{{ route('documents.index') }}"
               class="h-10 px-5 rounded-xl border {{ $border }} text-sm font-semibold {{ $fg }} hover:bg-[color:var(--tw-surface-2)] transition flex items-center">
                Cancel
            </a>
        </div>
    </form>
</div>

<style>
.attach-type-btn {
    background: var(--tw-surface-2);
    border-color: var(--tw-border);
    color: var(--tw-muted);
}
.attach-type-btn:hover {
    border-color: var(--tw-accent);
    color: var(--tw-fg);
}
.attach-type-btn.active-attach {
    background: rgba(16,185,129,.12);
    border-color: rgba(16,185,129,.5);
    color: var(--tw-accent);
}
</style>

<script>
function setAttachTo(type) {
    document.getElementById('attach-to-input').value = type;

    // Reset button styles
    document.querySelectorAll('.attach-type-btn').forEach(b => b.classList.remove('active-attach'));
    document.getElementById('picker-truck').classList.add('hidden');
    document.getElementById('picker-purchase').classList.add('hidden');

    // Disable all selects so they don't send with the form
    document.getElementById('truck-select').disabled = true;
    document.getElementById('purchase-select').disabled = true;

    if (type === 'truck') {
        document.getElementById('attach-btn-truck').classList.add('active-attach');
        document.getElementById('picker-truck').classList.remove('hidden');
        document.getElementById('truck-select').disabled = false;
    } else if (type === 'purchase') {
        document.getElementById('attach-btn-purchase').classList.add('active-attach');
        document.getElementById('picker-purchase').classList.remove('hidden');
        document.getElementById('purchase-select').disabled = false;
    } else {
        document.getElementById('attach-btn-none').classList.add('active-attach');
    }
}

// Init: both selects disabled, "None" active
document.getElementById('truck-select').disabled = true;
document.getElementById('purchase-select').disabled = true;
</script>
@endsection
