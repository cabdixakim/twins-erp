@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $products */
    $products = $products ?? collect();
    $total = method_exists($products, 'total') ? $products->total() : $products->count();

    // Theme tokens (same pattern as the other premium pages)
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    // Buttons (no dim pills; emerald buttons = text-white)
    $btnGhost   = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition font-semibold";
    $btnPrimary = "inline-flex items-center justify-center rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
    $btnDanger  = "inline-flex items-center justify-center rounded-xl border border-rose-500/50 bg-rose-600 text-white hover:bg-rose-500 transition font-semibold";

    $label = "block text-[11px] $muted mb-1";
    $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg placeholder:opacity-70 focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
@endphp

@extends('layouts.app')

@section('title', 'Products')
@section('subtitle', 'Company-scoped products (AGO, PMS, etc)')

@section('content')
<div class="w-full">
    <div class="mx-auto max-w-[980px] px-1 sm:px-0">

        {{-- Header --}}
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight {{ $fg }}">
                    Products
                </h1>
                <p class="mt-1 text-[12px] {{ $muted }}">
                    Manage products for the active company. Used by purchases, batches, and inventory.
                </p>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                <div class="hidden sm:flex items-center text-[11px] {{ $muted }} rounded-xl px-2.5 py-1 border {{ $border }} {{ $surface }}">
                    <span class="{{ $fg }} font-semibold">{{ $total }}</span>
                    <span class="ml-1">total</span>
                </div>

                <button type="button"
                        id="btnOpenCreateProduct"
                        class="{{ $btnPrimary }} h-9 px-3 text-[12px] cursor-pointer">
                    New product
                </button>
            </div>
        </div>

        {{-- Flash --}}
        @if(session('status'))
            <div class="mb-4 rounded-2xl border border-emerald-500/35 bg-emerald-600 text-white px-4 py-3 text-[12px] font-semibold">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-2xl border border-rose-500/35 bg-rose-600 text-white px-4 py-3 text-[12px]">
                <div class="font-semibold">Fix the following:</div>
                <ul class="mt-2 list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Search --}}
        <div class="mb-3">
            <div class="relative max-w-[520px]">
                <div class="absolute inset-y-0 left-3 grid place-items-center {{ $muted }}">
                    <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                    </svg>
                </div>

                <input id="twProductSearch"
                       class="w-full h-10 pl-9 pr-3 rounded-xl border {{ $border }} {{ $bg }}
                              text-[13px] {{ $fg }} placeholder:opacity-70
                              focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                       placeholder="Search products…"
                       autocomplete="off">
            </div>
        </div>

        {{-- Table --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
                <div class="text-[11px] uppercase tracking-wide {{ $muted }}">List</div>
                <div class="text-[11px] {{ $muted }}">{{ $total }} total</div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead class="{{ $surface }}">
                        <tr class="text-[11px] uppercase tracking-wide {{ $muted }} border-b {{ $border }}">
                            <th class="px-4 py-2.5 font-semibold">Name</th>
                            <th class="px-4 py-2.5 font-semibold">Code</th>
                            <th class="px-4 py-2.5 font-semibold">UOM</th>
                            <th class="px-4 py-2.5 font-semibold">Status</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="twProductList" class="divide-y divide-[color:var(--tw-border)]">
                        @forelse(($products?->items() ?? $products) as $p)
                            @php
                                $isActive = (bool)($p->is_active ?? false);
                            @endphp

                            {{-- MAIN ROW --}}
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="h-2 w-2 rounded-full {{ $isActive ? 'bg-emerald-400' : 'bg-[color:var(--tw-border)]' }} shrink-0"></span>
                                        <div class="tw-product-name text-[13px] font-semibold {{ $fg }} truncate">
                                            {{ $p->name }}
                                        </div>
                                    </div>
                                    <div class="text-[11px] {{ $muted }} mt-1">
                                        Company-scoped
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-[12px] {{ $fg }}">
                                    {{ $p->code ?: '—' }}
                                </td>

                                <td class="px-4 py-3 text-[12px] {{ $fg }}">
                                    {{ $p->base_uom ?: 'L' }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($isActive)
                                        <span class="inline-flex items-center text-[11px] font-semibold text-white bg-emerald-600 border border-emerald-500/50 px-2 py-0.5 rounded-lg">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-[11px] font-semibold {{ $fg }} border {{ $border }} {{ $surface2 }} px-2 py-0.5 rounded-lg">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                        <button type="button"
                                                class="btnEdit {{ $btnGhost }} h-9 px-3 text-[12px]"
                                                data-edit="edit-{{ $p->id }}">
                                            Edit
                                        </button>

                                        <form method="POST" action="{{ route('products.toggle-active', $p) }}">
                                            @csrf
                                            @method('PATCH')

                                            @if($isActive)
                                                <button type="submit" class="{{ $btnDanger }} h-9 px-3 text-[12px]">
                                                    Disable
                                                </button>
                                            @else
                                                <button type="submit" class="{{ $btnPrimary }} h-9 px-3 text-[12px]">
                                                    Enable
                                                </button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- FULL-WIDTH INLINE EDIT (premium) --}}
                            <tr id="edit-{{ $p->id }}" class="hidden">
                                <td colspan="5" class="px-4 pb-4">
                             <div class="rounded-xl border {{ $border }} {{ $surface2 }}
                                        overflow-hidden
                                        p-3 sm:p-0
                                        sm:rounded-2xl">
                                        {{-- header --}}
                                        <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
                                            <div class="min-w-0">
                                                <div class="text-[11px] uppercase tracking-wide {{ $muted }}">Edit product</div>
                                                <div class="text-[13px] font-semibold {{ $fg }} truncate">{{ $p->name }}</div>
                                            </div>

                                            <button type="button"
                                                    class="btnCancelEdit {{ $btnGhost }} h-9 w-9 text-lg leading-none"
                                                    data-edit="edit-{{ $p->id }}">
                                                ×
                                            </button>
                                        </div>

                                        <form method="POST" action="{{ route('products.update', $p) }}"
                                            class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            @csrf
                                            @method('PATCH')

                                            <div class="sm:col-span-1">
                                                <label class="{{ $label }}">Name</label>
                                                <input name="name" required value="{{ $p->name }}" class="h-9 {{ $input }}">
                                            </div>

                                            <div class="sm:col-span-1">
                                                <label class="{{ $label }}">Code (optional)</label>
                                                <input name="code" value="{{ $p->code }}" class="h-9 {{ $input }}" placeholder="AGO / PMS">
                                            </div>

                                            <div class="sm:col-span-1">
                                                <label class="{{ $label }}">Base UOM</label>
                                                <input name="base_uom" value="{{ $p->base_uom ?? 'L' }}" class="h-9 {{ $input }}" placeholder="L">
                                            </div>

                                            <div class="sm:col-span-3 flex items-center justify-end gap-2 pt-1">
                                                <button type="button"
                                                        class="btnCancelEdit {{ $btnGhost }} h-9 px-3 text-[12px]"
                                                        data-edit="edit-{{ $p->id }}">
                                                    Cancel
                                                </button>

                                                <button type="submit"
                                                        class="{{ $btnPrimary }} h-9 px-3 text-[12px]">
                                                    Save
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-center {{ $muted }}">
                                    No products found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($products, 'links'))
                <div class="px-4 py-3 border-t {{ $border }}">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div id="twCreateProductOverlay" class="hidden fixed inset-0 z-[80] bg-black/55"></div>

{{-- NOTE: we keep your IDs + JS contract EXACTLY the same --}}
<div id="twCreateProductModal"
     class="hidden fixed inset-0 z-[90] p-4 sm:p-6
            flex items-end sm:items-center justify-center">
    <div class="max-w-[560px] w-full rounded-2xl overflow-hidden
                border {{ $border }} {{ $surface }}
                shadow-[0_30px_90px_rgba(0,0,0,.70)]">

        <div class="px-4 py-3 border-b {{ $border }} flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold {{ $fg }}">Create product</div>
                <div class="text-[11px] {{ $muted }}">Company scoped</div>
            </div>

            <button type="button"
                    id="btnCloseCreateProduct"
                    class="{{ $btnGhost }} h-9 w-9"
                    aria-label="Close">
                <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('products.store') }}" class="p-4 space-y-3">
            @csrf

            <div>
                <label class="{{ $label }}">Name</label>
                <input name="name" required
                       class="h-10 {{ $input }}"
                       placeholder="e.g. AGO">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="{{ $label }}">Code (optional)</label>
                    <input name="code"
                           class="h-10 {{ $input }}"
                           placeholder="AGO / PMS">
                </div>

                <div>
                    <label class="{{ $label }}">Base UOM</label>
                    <input name="base_uom" value="L"
                           class="h-10 {{ $input }}"
                           placeholder="L">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button"
                        id="btnCancelCreateProduct"
                        class="{{ $btnGhost }} h-9 px-3 text-[12px]">
                    Cancel
                </button>

                <button type="submit"
                        class="{{ $btnPrimary }} h-9 px-3 text-[12px] cursor-pointer">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    // Search filter
    const input = document.getElementById('twProductSearch');
    const list  = document.getElementById('twProductList');

    if (input && list) {
        input.addEventListener('input', () => {
            const q = (input.value || '').toLowerCase().trim();
            list.querySelectorAll('.tw-product-name').forEach(nameEl => {
                const row = nameEl.closest('tr');
                const txt = (nameEl.textContent || '').toLowerCase();
                row.style.display = (!q || txt.includes(q)) ? '' : 'none';
            });
        });
    }

    // Inline edit toggles
    function toggleEdit(id, open){
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('hidden', !open);
    }

    document.querySelectorAll('.btnEdit').forEach(btn => {
        btn.addEventListener('click', () => toggleEdit(btn.dataset.edit, true));
    });
    document.querySelectorAll('.btnCancelEdit').forEach(btn => {
        btn.addEventListener('click', () => toggleEdit(btn.dataset.edit, false));
    });

    // Create modal (KEEP SAME IDs)
    const openBtn = document.getElementById('btnOpenCreateProduct');
    const overlay = document.getElementById('twCreateProductOverlay');
    const modal   = document.getElementById('twCreateProductModal');
    const closeBtn = document.getElementById('btnCloseCreateProduct');
    const cancelBtn = document.getElementById('btnCancelCreateProduct');

 function open(){
    if (!overlay || !modal) return;
    overlay.classList.remove('hidden');
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
}

function close(){
    if (!overlay || !modal) return;
    overlay.classList.add('hidden');
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

    openBtn?.addEventListener('click', open);
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
})();
</script>
@endsection