@php
    $title = 'Company profile';
    $subtitle = 'Branding, base currency and home timezone for Twins.';

    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost   = "inline-flex items-center justify-center rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition";
    $btnPrimary = "inline-flex items-center justify-center rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
    $btnDanger  = "inline-flex items-center justify-center rounded-xl border border-rose-500/50 bg-rose-600 text-white hover:bg-rose-500 transition font-semibold";

    $label = "block text-[11px] $muted mb-1";
    $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg placeholder:opacity-70 focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
@endphp

@extends('layouts.app')

@section('title', $title)
@section('subtitle', $subtitle)

@push('styles')
    {{-- Cropper.js CSS (CDN) --}}
    <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
    <style>
        /* keep cropper looking crisp inside our modal */
        .cropper-view-box, .cropper-face { border-radius: 18px; }
    </style>
@endpush

@section('content')

@if (session('status'))
    <div class="mb-4 rounded-xl bg-emerald-600 text-white border border-emerald-500/50 px-3 py-2 text-xs font-semibold">
        {{ session('status') }}
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-[280px,minmax(0,1fr)]">

    {{-- LEFT: summary --}}
    <div class="space-y-4">
        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl {{ $surface2 }} flex items-center justify-center overflow-hidden border {{ $border }}">
                    @if($company->logo_path)
                        <img src="{{ asset('storage/'.$company->logo_path) }}"
                             alt="Logo"
                             class="w-full h-full object-cover">
                    @else
                        <span class="text-lg">🏭</span>
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="text-xs uppercase tracking-wide {{ $muted }} mb-0.5">
                        Company
                    </div>
                    <div class="text-sm font-semibold truncate {{ $fg }}">
                        {{ $company->name ?? 'Not set' }}
                    </div>
                    <div class="text-[11px] {{ $muted }} truncate">
                        {{ $company->country ?: 'Country not set' }}
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1 text-[11px] {{ $muted }}">
                <div>
                    Base currency:
                    <span class="font-semibold {{ $fg }}">
                        {{ $company->base_currency ?: 'Not set' }}
                    </span>
                </div>
                <div>
                    Timezone:
                    <span class="font-semibold {{ $fg }}">
                        {{ $company->timezone ?: 'Not set' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border {{ $border }} {{ $surface }} p-3 text-[11px] {{ $muted }}">
            <div class="font-semibold {{ $fg }} mb-1 text-xs">Tips</div>
            <ul class="space-y-1 list-disc list-inside">
                <li>Logo appears on invoices and statements.</li>
                <li>Base currency is used as default when creating documents.</li>
                <li>Timezone helps align ETAs and reports.</li>
            </ul>
        </div>
    </div>

    {{-- RIGHT: form --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold {{ $fg }}">Edit company profile</h2>
            <span class="text-[11px] {{ $muted }}">Branding + defaults</span>
        </div>

        <form id="companyForm"
              method="POST"
              action="{{ route('settings.company.update') }}"
              enctype="multipart/form-data"
              class="space-y-4">
            @csrf
            @method('PATCH')

            {{-- hidden crop result --}}
            <input type="hidden" name="logo_cropped" id="logoCroppedInput" value="">
            <input type="hidden" name="remove_logo" id="removeLogoInput" value="0">

            <div>
                <label class="{{ $label }}">Trading name *</label>
                <input type="text" name="name"
                       value="{{ old('name', $company->name) }}"
                       class="{{ $input }}">
                @error('name')
                    <div class="mt-1 text-[11px] text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="{{ $label }}">Base currency</label>
                    <input type="text" name="base_currency"
                           placeholder="USD, ZMW, CDF..."
                           value="{{ old('base_currency', $company->base_currency) }}"
                           class="{{ $input }}">
                    @error('base_currency')
                        <div class="mt-1 text-[11px] text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Country</label>
                    <input type="text" name="country"
                           value="{{ old('country', $company->country) }}"
                           class="{{ $input }}">
                    @error('country')
                        <div class="mt-1 text-[11px] text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Timezone</label>
                    <input type="text" name="timezone"
                           placeholder="Africa/Lubumbashi"
                           value="{{ old('timezone', $company->timezone) }}"
                           class="{{ $input }}">
                    @error('timezone')
                        <div class="mt-1 text-[11px] text-rose-600">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- LOGO --}}
            <div class="rounded-2xl border {{ $border }} {{ $surface2 }} p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold {{ $fg }}">Logo</div>
                        <div class="mt-0.5 text-[11px] {{ $muted }}">
                            Upload then crop/position for clean invoice branding. PNG/JPG, max 2MB.
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($company->logo_path)
                            <button type="button"
                                    id="btnRemoveLogo"
                                    class="{{ $btnDanger }} px-3 py-1.5 text-[11px]">
                                Remove
                            </button>
                        @endif

                        <button type="button"
                                id="btnOpenCropper"
                                class="{{ $btnGhost }} px-3 py-1.5 text-[11px]">
                            Crop / fit
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr),140px] items-start">
                    <div>
                        <label class="{{ $label }}">Upload logo</label>

                        <input
                            id="logoFileInput"
                            type="file"
                            name="logo"
                            accept="image/*"
                            class="block w-full text-[11px] {{ $fg }}
                                   file:mr-3 file:rounded-xl file:border file:border-[color:var(--tw-border)]
                                   file:bg-[color:var(--tw-btn)] file:px-3 file:py-2
                                   file:text-xs file:font-semibold file:text-[color:var(--tw-fg)]
                                   hover:file:bg-[color:var(--tw-btn-hover)]
                                   cursor-pointer"
                        />

                        <div class="mt-2 text-[11px] {{ $muted }}">
                            Tip: click <span class="font-semibold {{ $fg }}">Crop / fit</span> to scale + position perfectly.
                        </div>

                        @error('logo')
                            <div class="mt-1 text-[11px] text-rose-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex justify-center">
                        <div class="w-28 h-28 rounded-2xl border {{ $border }} {{ $bg }} flex items-center justify-center overflow-hidden">
                            @if($company->logo_path)
                                <img id="logoPreview"
                                     src="{{ asset('storage/'.$company->logo_path) }}"
                                     alt="Logo preview"
                                     class="w-full h-full object-contain">
                            @else
                                <img id="logoPreview" src="" alt="Logo preview" class="hidden w-full h-full object-contain">
                                <span id="logoPreviewPlaceholder" class="text-xs {{ $muted }} text-center px-2">
                                    Logo preview
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="{{ $btnPrimary }} px-4 py-2 text-sm">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>

{{-- CROPPER MODAL (premium + always-visible actions) --}}
<div id="logoCropModal"
     class="fixed inset-0 z-50 hidden bg-black/60 p-3 sm:p-6">

    {{-- Backdrop click closes --}}
    <div class="absolute inset-0" onclick="document.getElementById('btnCancelCropper')?.click()"></div>

    {{-- Dialog --}}
    <div class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-2xl border {{ $border }} {{ $surface }}
                shadow-[0_35px_120px_rgba(0,0,0,.55)]
                max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)]
                flex flex-col">

        {{-- Header --}}
        <div class="px-4 sm:px-5 py-3 border-b {{ $border }} flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="text-sm font-semibold {{ $fg }}">Crop logo</div>
                <div class="text-[11px] {{ $muted }} truncate">
                    Drag to position • zoom to fit • exports a clean square
                </div>
            </div>

            <button type="button" id="btnCloseCropper"
                    class="{{ $btnGhost }} h-9 w-9 text-lg leading-none shrink-0">×</button>
        </div>

        {{-- Scrollable body --}}
        <div class="flex-1 overflow-y-auto p-4 sm:p-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr),260px]">

                {{-- Crop area --}}
                <div class="rounded-2xl border {{ $border }} {{ $bg }} overflow-hidden">
                    <div class="w-full aspect-[16/9]">
                        <img id="cropperImage" alt="Cropper image" class="max-w-full hidden">
                        <div id="cropperEmpty"
                             class="h-full w-full flex items-center justify-center text-[11px] {{ $muted }}">
                            Select a logo first (choose file), then crop here.
                        </div>
                    </div>
                </div>

                {{-- Controls --}}
                <div class="rounded-2xl border {{ $border }} {{ $surface2 }} p-4 space-y-3">
                    <div class="text-xs font-semibold {{ $fg }}">Controls</div>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="btnZoomIn" class="{{ $btnGhost }} px-3 py-2 text-[11px]">Zoom in</button>
                        <button type="button" id="btnZoomOut" class="{{ $btnGhost }} px-3 py-2 text-[11px]">Zoom out</button>
                        <button type="button" id="btnRotate" class="{{ $btnGhost }} px-3 py-2 text-[11px]">Rotate</button>
                        <button type="button" id="btnReset" class="{{ $btnGhost }} px-3 py-2 text-[11px]">Reset</button>
                    </div>

                    <div class="pt-2 border-t {{ $border }}"></div>

                    <div class="space-y-2">
                        <div class="text-[11px] {{ $muted }}">Export size</div>
                        <select id="exportSize" class="w-full rounded-xl border {{ $border }} {{ $bg }} px-3 py-2 text-sm {{ $fg }}">
                            <option value="256">256 × 256</option>
                            <option value="384">384 × 384</option>
                            <option value="512" selected>512 × 512 (recommended)</option>
                            <option value="768">768 × 768</option>
                        </select>

                        <div class="rounded-xl border {{ $border }} {{ $bg }} p-3">
                            <div class="text-[11px] {{ $muted }}">
                                Tip: make sure your logo sits comfortably in the square — invoices look best with padding.
                            </div>
                        </div>
                    </div>

                    {{-- Mobile-only primary action inside panel (so user sees it without scrolling) --}}
                    <button type="button"
                            data-apply-crop="1"
                            class="{{ $btnPrimary }} w-full px-3 py-2 text-[11px] lg:hidden">
                        Use cropped logo
                    </button>
                </div>
            </div>
        </div>

        {{-- Sticky footer (always visible) --}}
        <div class="sticky bottom-0 border-t {{ $border }} {{ $surface }}
                    px-4 sm:px-5 py-3">
            <div class="flex items-center justify-between gap-3">
                <div class="hidden sm:block text-[11px] {{ $muted }}">
                    This will save the crop as the official logo.
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" id="btnCancelCropper"
                            class="{{ $btnGhost }} px-3 py-2 text-[11px]">
                        Close
                    </button>

                    {{-- Desktop primary action in footer --}}
                    <button type="button"
                            data-apply-crop="1"
                            class="{{ $btnPrimary }} px-4 py-2 text-[11px] hidden lg:inline-flex">
                        Use cropped logo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>

    <script>
        (function () {
            const form = document.getElementById('companyForm');

            const fileInput = document.getElementById('logoFileInput');
            const previewImg = document.getElementById('logoPreview');
            const previewPlaceholder = document.getElementById('logoPreviewPlaceholder');

            const croppedInput = document.getElementById('logoCroppedInput');
            const removeLogoInput = document.getElementById('removeLogoInput');

            const modal = document.getElementById('logoCropModal');
            const cropperImage = document.getElementById('cropperImage');
            const cropperEmpty = document.getElementById('cropperEmpty');

            const btnOpen = document.getElementById('btnOpenCropper');
            const btnClose = document.getElementById('btnCloseCropper');
            const btnCancel = document.getElementById('btnCancelCropper');

            const btnRemove = document.getElementById('btnRemoveLogo');

            const btnZoomIn = document.getElementById('btnZoomIn');
            const btnZoomOut = document.getElementById('btnZoomOut');
            const btnRotate = document.getElementById('btnRotate');
            const btnReset = document.getElementById('btnReset');

            // ✅ FIX: no duplicate IDs — bind to both buttons
            const btnApplyAll = document.querySelectorAll('[data-apply-crop="1"]');

            const exportSize = document.getElementById('exportSize');

            let cropper = null;
            let currentObjectUrl = null;

            function openModal() {
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function destroyCropper() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }

            function setPreview(src) {
                if (!previewImg) return;
                previewImg.src = src;
                previewImg.classList.remove('hidden');
                if (previewPlaceholder) previewPlaceholder.classList.add('hidden');
            }

            function clearPreview() {
                if (previewImg) {
                    previewImg.src = '';
                    previewImg.classList.add('hidden');
                }
                if (previewPlaceholder) previewPlaceholder.classList.remove('hidden');
            }

            function loadIntoCropper(src) {
                destroyCropper();

                if (!cropperImage) return;
                cropperImage.src = src;
                cropperImage.classList.remove('hidden');
                if (cropperEmpty) cropperEmpty.classList.add('hidden');

                // init cropper
                cropper = new Cropper(cropperImage, {
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                    restore: true,
                    guides: false,
                    center: true,
                    highlight: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    aspectRatio: 1,
                });
            }

            // File selected -> prepare cropper image source
            fileInput?.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0];
                if (!file) return;

                // user is uploading new logo -> remove flag off
                removeLogoInput.value = '0';

                // clear any previous crop result (new file chosen)
                croppedInput.value = '';

                // object URL for cropper
                if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
                currentObjectUrl = URL.createObjectURL(file);

                // set preview immediately (raw)
                setPreview(currentObjectUrl);

                // load cropper
                loadIntoCropper(currentObjectUrl);
            });

            // open cropper modal
            btnOpen?.addEventListener('click', () => {
                openModal();

                // If cropper isn't ready but preview has src, try load
                const src = (previewImg && !previewImg.classList.contains('hidden') && previewImg.src) ? previewImg.src : null;
                if (src && (!cropper || cropperImage.src !== src)) {
                    loadIntoCropper(src);
                }
            });

            // close modal actions
            btnClose?.addEventListener('click', closeModal);
            btnCancel?.addEventListener('click', closeModal);

            // click outside to close
            modal?.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            // controls
            btnZoomIn?.addEventListener('click', () => cropper?.zoom(0.08));
            btnZoomOut?.addEventListener('click', () => cropper?.zoom(-0.08));
            btnRotate?.addEventListener('click', () => cropper?.rotate(90));
            btnReset?.addEventListener('click', () => cropper?.reset());

            // ✅ FIX: apply crop function + bind to BOTH apply buttons
            function applyCrop() {
                if (!cropper) return;

                const size = parseInt(exportSize?.value || '512', 10);
                const canvas = cropper.getCroppedCanvas({
                    width: size,
                    height: size,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                const dataUrl = canvas.toDataURL('image/png', 0.92);

                // store for backend
                croppedInput.value = dataUrl;

                // update preview with cropped image
                setPreview(dataUrl);

                // close modal
                closeModal();
            }

            btnApplyAll.forEach(btn => btn.addEventListener('click', applyCrop));

            // Remove logo
            btnRemove?.addEventListener('click', () => {
                // explicit intent: remove wins
                removeLogoInput.value = '1';

                // clear crop + file input
                croppedInput.value = '';
                if (fileInput) fileInput.value = '';

                // clear preview
                clearPreview();

                // also kill cropper if open
                destroyCropper();
            });

            // Safety: if user submits after pressing remove, clear crop/file is already done
            form?.addEventListener('submit', () => {
                if (removeLogoInput.value === '1') {
                    croppedInput.value = '';
                }
            });

            // Cleanup URL on page unload
            window.addEventListener('beforeunload', () => {
                if (currentObjectUrl) URL.revokeObjectURL(currentObjectUrl);
            });
        })();
    </script>
@endpush