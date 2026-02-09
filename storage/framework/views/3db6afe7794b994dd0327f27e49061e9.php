<?php
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
?>



<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startPush('styles'); ?>
    
    <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
    <style>
        /* keep cropper looking crisp inside our modal */
        .cropper-view-box, .cropper-face { border-radius: 18px; }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('status')): ?>
    <div class="mb-4 rounded-xl bg-emerald-600 text-white border border-emerald-500/50 px-3 py-2 text-xs font-semibold">
        <?php echo e(session('status')); ?>

    </div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-[280px,minmax(0,1fr)]">

    
    <div class="space-y-4">
        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl <?php echo e($surface2); ?> flex items-center justify-center overflow-hidden border <?php echo e($border); ?>">
                    <?php if($company->logo_path): ?>
                        <img src="<?php echo e(asset('storage/'.$company->logo_path)); ?>"
                             alt="Logo"
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-lg">🏭</span>
                    <?php endif; ?>
                </div>

                <div class="min-w-0">
                    <div class="text-xs uppercase tracking-wide <?php echo e($muted); ?> mb-0.5">
                        Company
                    </div>
                    <div class="text-sm font-semibold truncate <?php echo e($fg); ?>">
                        <?php echo e($company->name ?? 'Not set'); ?>

                    </div>
                    <div class="text-[11px] <?php echo e($muted); ?> truncate">
                        <?php echo e($company->country ?: 'Country not set'); ?>

                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1 text-[11px] <?php echo e($muted); ?>">
                <div>
                    Base currency:
                    <span class="font-semibold <?php echo e($fg); ?>">
                        <?php echo e($company->base_currency ?: 'Not set'); ?>

                    </span>
                </div>
                <div>
                    Timezone:
                    <span class="font-semibold <?php echo e($fg); ?>">
                        <?php echo e($company->timezone ?: 'Not set'); ?>

                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 text-[11px] <?php echo e($muted); ?>">
            <div class="font-semibold <?php echo e($fg); ?> mb-1 text-xs">Tips</div>
            <ul class="space-y-1 list-disc list-inside">
                <li>Logo appears on invoices and statements.</li>
                <li>Base currency is used as default when creating documents.</li>
                <li>Timezone helps align ETAs and reports.</li>
            </ul>
        </div>
    </div>

    
    <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-5 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold <?php echo e($fg); ?>">Edit company profile</h2>
            <span class="text-[11px] <?php echo e($muted); ?>">Branding + defaults</span>
        </div>

        <form id="companyForm"
              method="POST"
              action="<?php echo e(route('settings.company.update')); ?>"
              enctype="multipart/form-data"
              class="space-y-4">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            
            <input type="hidden" name="logo_cropped" id="logoCroppedInput" value="">
            <input type="hidden" name="remove_logo" id="removeLogoInput" value="0">

            <div>
                <label class="<?php echo e($label); ?>">Trading name *</label>
                <input type="text" name="name"
                       value="<?php echo e(old('name', $company->name)); ?>"
                       class="<?php echo e($input); ?>">
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="mt-1 text-[11px] text-rose-600"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="<?php echo e($label); ?>">Base currency</label>
                    <input type="text" name="base_currency"
                           placeholder="USD, ZMW, CDF..."
                           value="<?php echo e(old('base_currency', $company->base_currency)); ?>"
                           class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['base_currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="mt-1 text-[11px] text-rose-600"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="<?php echo e($label); ?>">Country</label>
                    <input type="text" name="country"
                           value="<?php echo e(old('country', $company->country)); ?>"
                           class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="mt-1 text-[11px] text-rose-600"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label class="<?php echo e($label); ?>">Timezone</label>
                    <input type="text" name="timezone"
                           placeholder="Africa/Lubumbashi"
                           value="<?php echo e(old('timezone', $company->timezone)); ?>"
                           class="<?php echo e($input); ?>">
                    <?php $__errorArgs = ['timezone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="mt-1 text-[11px] text-rose-600"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            
            <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold <?php echo e($fg); ?>">Logo</div>
                        <div class="mt-0.5 text-[11px] <?php echo e($muted); ?>">
                            Upload then crop/position for clean invoice branding. PNG/JPG, max 2MB.
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <?php if($company->logo_path): ?>
                            <button type="button"
                                    id="btnRemoveLogo"
                                    class="<?php echo e($btnDanger); ?> px-3 py-1.5 text-[11px]">
                                Remove
                            </button>
                        <?php endif; ?>

                        <button type="button"
                                id="btnOpenCropper"
                                class="<?php echo e($btnGhost); ?> px-3 py-1.5 text-[11px]">
                            Crop / fit
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr),140px] items-start">
                    <div>
                        <label class="<?php echo e($label); ?>">Upload logo</label>

                        <input
                            id="logoFileInput"
                            type="file"
                            name="logo"
                            accept="image/*"
                            class="block w-full text-[11px] <?php echo e($fg); ?>

                                   file:mr-3 file:rounded-xl file:border file:border-[color:var(--tw-border)]
                                   file:bg-[color:var(--tw-btn)] file:px-3 file:py-2
                                   file:text-xs file:font-semibold file:text-[color:var(--tw-fg)]
                                   hover:file:bg-[color:var(--tw-btn-hover)]
                                   cursor-pointer"
                        />

                        <div class="mt-2 text-[11px] <?php echo e($muted); ?>">
                            Tip: click <span class="font-semibold <?php echo e($fg); ?>">Crop / fit</span> to scale + position perfectly.
                        </div>

                        <?php $__errorArgs = ['logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="mt-1 text-[11px] text-rose-600"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="flex justify-center">
                        <div class="w-28 h-28 rounded-2xl border <?php echo e($border); ?> <?php echo e($bg); ?> flex items-center justify-center overflow-hidden">
                            <?php if($company->logo_path): ?>
                                <img id="logoPreview"
                                     src="<?php echo e(asset('storage/'.$company->logo_path)); ?>"
                                     alt="Logo preview"
                                     class="w-full h-full object-contain">
                            <?php else: ?>
                                <img id="logoPreview" src="" alt="Logo preview" class="hidden w-full h-full object-contain">
                                <span id="logoPreviewPlaceholder" class="text-xs <?php echo e($muted); ?> text-center px-2">
                                    Logo preview
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="<?php echo e($btnPrimary); ?> px-4 py-2 text-sm">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>


<div id="logoCropModal"
     class="fixed inset-0 z-50 hidden bg-black/60 p-3 sm:p-6">

    
    <div class="absolute inset-0" onclick="document.getElementById('btnCancelCropper')?.click()"></div>

    
    <div class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?>

                shadow-[0_35px_120px_rgba(0,0,0,.55)]
                max-h-[calc(100vh-1.5rem)] sm:max-h-[calc(100vh-3rem)]
                flex flex-col">

        
        <div class="px-4 sm:px-5 py-3 border-b <?php echo e($border); ?> flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="text-sm font-semibold <?php echo e($fg); ?>">Crop logo</div>
                <div class="text-[11px] <?php echo e($muted); ?> truncate">
                    Drag to position • zoom to fit • exports a clean square
                </div>
            </div>

            <button type="button" id="btnCloseCropper"
                    class="<?php echo e($btnGhost); ?> h-9 w-9 text-lg leading-none shrink-0">×</button>
        </div>

        
        <div class="flex-1 overflow-y-auto p-4 sm:p-5">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr),260px]">

                
                <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($bg); ?> overflow-hidden">
                    <div class="w-full aspect-[16/9]">
                        <img id="cropperImage" alt="Cropper image" class="max-w-full hidden">
                        <div id="cropperEmpty"
                             class="h-full w-full flex items-center justify-center text-[11px] <?php echo e($muted); ?>">
                            Select a logo first (choose file), then crop here.
                        </div>
                    </div>
                </div>

                
                <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-4 space-y-3">
                    <div class="text-xs font-semibold <?php echo e($fg); ?>">Controls</div>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="btnZoomIn" class="<?php echo e($btnGhost); ?> px-3 py-2 text-[11px]">Zoom in</button>
                        <button type="button" id="btnZoomOut" class="<?php echo e($btnGhost); ?> px-3 py-2 text-[11px]">Zoom out</button>
                        <button type="button" id="btnRotate" class="<?php echo e($btnGhost); ?> px-3 py-2 text-[11px]">Rotate</button>
                        <button type="button" id="btnReset" class="<?php echo e($btnGhost); ?> px-3 py-2 text-[11px]">Reset</button>
                    </div>

                    <div class="pt-2 border-t <?php echo e($border); ?>"></div>

                    <div class="space-y-2">
                        <div class="text-[11px] <?php echo e($muted); ?>">Export size</div>
                        <select id="exportSize" class="w-full rounded-xl border <?php echo e($border); ?> <?php echo e($bg); ?> px-3 py-2 text-sm <?php echo e($fg); ?>">
                            <option value="256">256 × 256</option>
                            <option value="384">384 × 384</option>
                            <option value="512" selected>512 × 512 (recommended)</option>
                            <option value="768">768 × 768</option>
                        </select>

                        <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($bg); ?> p-3">
                            <div class="text-[11px] <?php echo e($muted); ?>">
                                Tip: make sure your logo sits comfortably in the square — invoices look best with padding.
                            </div>
                        </div>
                    </div>

                    
                    <button type="button"
                            data-apply-crop="1"
                            class="<?php echo e($btnPrimary); ?> w-full px-3 py-2 text-[11px] lg:hidden">
                        Use cropped logo
                    </button>
                </div>
            </div>
        </div>

        
        <div class="sticky bottom-0 border-t <?php echo e($border); ?> <?php echo e($surface); ?>

                    px-4 sm:px-5 py-3">
            <div class="flex items-center justify-between gap-3">
                <div class="hidden sm:block text-[11px] <?php echo e($muted); ?>">
                    This will save the crop as the official logo.
                </div>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" id="btnCancelCropper"
                            class="<?php echo e($btnGhost); ?> px-3 py-2 text-[11px]">
                        Close
                    </button>

                    
                    <button type="button"
                            data-apply-crop="1"
                            class="<?php echo e($btnPrimary); ?> px-4 py-2 text-[11px] hidden lg:inline-flex">
                        Use cropped logo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/settings/company.blade.php ENDPATH**/ ?>