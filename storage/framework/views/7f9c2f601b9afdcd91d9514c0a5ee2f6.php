

<?php
  $suppliers = $suppliers ?? collect();
  $products  = $products ?? collect();
  $depots    = $depots ?? collect();

  // Theme tokens (from app.css)
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  // Buttons (stand out in BOTH light + dark)
  $btnGhost = "inline-flex items-center gap-2 rounded-xl border $border $surface2 px-4 py-2 text-sm font-semibold $fg hover:bg-[color:var(--tw-surface)]";
  $btnLink  = "text-sm $muted hover:text-[color:var(--tw-fg)]";

  // Strong primary button (bright in dark, crisp in light)
  // Uses your accent token + emerald text (like you’ve been doing successfully).
  $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold
                 px-4 py-2 text-sm font-semibold hover:bg-emerald-500/20";
?>



<?php $__env->startSection('title', 'New purchase'); ?>
<?php $__env->startSection('subtitle', 'Create a draft purchase'); ?>

<?php $__env->startSection('content'); ?>


<div class="flex items-start justify-between gap-4">
  <div class="min-w-0">
    <h1 class="text-xl font-semibold <?php echo e($fg); ?>">New purchase</h1>
    <p class="mt-1 text-sm <?php echo e($muted); ?>">
      Create a draft purchase now. Confirm later to create the batch.
    </p>
  </div>

  <a href="<?php echo e(route('purchases.index')); ?>" class="<?php echo e($btnGhost); ?>">
    <span class="text-base">←</span>
    Back
  </a>
</div>



<form method="POST" action="<?php echo e(route('purchases.store')); ?>" class="mt-6">
  <?php echo csrf_field(); ?>

  <?php
    // Validation UI tokens
    $errRing   = 'focus:ring-2 focus:ring-rose-500/35';
    $errBorder = 'border-rose-500/50';
    $errBg     = 'bg-rose-500/5';
    $errText   = 'text-rose-700 dark:text-rose-200';
    $hintText  = $muted;

    // Helpers (field classes)
    $fieldBase = "mt-1 w-full h-10 rounded-xl border $border $surface2 px-3 text-sm $fg
                  placeholder:text-[color:var(--tw-muted)]
                  focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";

    $areaBase  = "mt-1 w-full rounded-xl border $border $surface2 p-3 text-sm $fg
                  placeholder:text-[color:var(--tw-muted)]
                  focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]";
  ?>

  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-5">

    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-sm font-semibold <?php echo e($fg); ?>">Purchase details</div>
        <div class="mt-1 text-xs <?php echo e($muted); ?>">
          Choose a type, fill the fields, then save as draft.
        </div>
      </div>
    </div>

    
    <div class="mt-5">
      <div class="text-xs font-semibold <?php echo e($muted); ?>">Purchase type</div>

      <div class="mt-2 grid gap-3 sm:grid-cols-3" id="type-grid">

        <?php
          $typeErrCard = $errors->has('type')
            ? "border-rose-500/50 bg-rose-500/5 ring-1 ring-rose-500/15"
            : "";
        ?>

        <label data-type-card="import"
               class="type-card cursor-pointer rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 hover:border-emerald-500/40 transition <?php echo e($typeErrCard); ?>">
          <input type="radio" name="type" value="import" class="sr-only js-type"
                 <?php echo e(old('type','import') === 'import' ? 'checked' : ''); ?>>

          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold <?php echo e($fg); ?>">Import</div>
              <div class="mt-1 text-xs <?php echo e($muted); ?>">Transport → TR8 → Offload</div>
            </div>

            <div class="type-check hidden mt-0.5 rounded-full border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold">
              Selected
            </div>
          </div>
        </label>

        <label data-type-card="local_depot"
               class="type-card cursor-pointer rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 hover:border-emerald-500/40 transition <?php echo e($typeErrCard); ?>">
          <input type="radio" name="type" value="local_depot" class="sr-only js-type"
                 <?php echo e(old('type') === 'local_depot' ? 'checked' : ''); ?>>

          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold <?php echo e($fg); ?>">Local depot</div>
              <div class="mt-1 text-xs <?php echo e($muted); ?>">Ownership change only</div>
            </div>

            <div class="type-check hidden mt-0.5 rounded-full border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold">
              Selected
            </div>
          </div>
        </label>

        <label data-type-card="cross_dock"
               class="type-card cursor-pointer rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 hover:border-emerald-500/40 transition <?php echo e($typeErrCard); ?>">
          <input type="radio" name="type" value="cross_dock" class="sr-only js-type"
                 <?php echo e(old('type') === 'cross_dock' ? 'checked' : ''); ?>>

          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold <?php echo e($fg); ?>">Cross dock</div>
              <div class="mt-1 text-xs <?php echo e($muted); ?>">Loaded truck → direct delivery</div>
            </div>

            <div class="type-check hidden mt-0.5 rounded-full border border-emerald-600 bg-emerald-500 text-white
                        px-2 py-0.5 text-[11px] font-semibold">
              Selected
            </div>
          </div>
        </label>

      </div>

      <div id="type-context"
           class="mt-3 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($fg); ?>">
        <!-- JS will fill -->
      </div>

      <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="mt-2 flex items-center gap-2 text-xs <?php echo e($errText); ?>">
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-500/15 ring-1 ring-rose-500/20">!</span>
          <span><?php echo e($message); ?></span>
        </div>
      <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div class="mt-6 grid gap-4 sm:grid-cols-2">

      
      <div>
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Reference (optional)</label>
        <input name="reference" value="<?php echo e(old('reference')); ?>"
               class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
               placeholder="Leave blank to auto-generate (e.g. PO-2026-00001)">
        <div class="mt-1 text-xs <?php echo e($hintText); ?>">If blank, the system generates one.</div>
        <?php $__errorArgs = ['reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div>
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Product</label>
        <select name="product_id"
                class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
          <option value="">Select…</option>
          <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($p->id); ?>" <?php echo e((string)old('product_id')===(string)$p->id ? 'selected' : ''); ?>>
              <?php echo e($p->name); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div>
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Supplier (optional)</label>
        <select name="supplier_id"
                class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['supplier_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
          <option value="">—</option>
          <?php $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($s->id); ?>" <?php echo e((string)old('supplier_id')===(string)$s->id ? 'selected' : ''); ?>>
              <?php echo e($s->name); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['supplier_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div id="depot-wrap" class="hidden">
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Depot (required for local depot)</label>
        <select name="depot_id"
                class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['depot_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
          <option value="">Select…</option>
          <?php $__currentLoopData = $depots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($d->id); ?>" <?php echo e((string)old('depot_id')===(string)$d->id ? 'selected' : ''); ?>>
              <?php echo e($d->name); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['depot_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div>
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Quantity</label>
        <input name="qty" value="<?php echo e(old('qty')); ?>" inputmode="decimal"
               class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['qty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
               placeholder="e.g. 9000">
        <div class="mt-1 text-xs <?php echo e($hintText); ?>">Base unit (litres for fuel).</div>
        <?php $__errorArgs = ['qty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div>
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Unit price</label>
        <input name="unit_price" value="<?php echo e(old('unit_price')); ?>" inputmode="decimal"
               class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['unit_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
               placeholder="e.g. 0.65">
        <?php $__errorArgs = ['unit_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div>
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Currency</label>
        <input name="currency" value="<?php echo e(old('currency','USD')); ?>"
               class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
               placeholder="USD">
        <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div>
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Purchase date (optional)</label>
        <input type="date" name="purchase_date" value="<?php echo e(old('purchase_date')); ?>"
               class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['purchase_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
        <?php $__errorArgs = ['purchase_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

      
      <div class="sm:col-span-2">
        <label class="text-xs font-semibold <?php echo e($muted); ?>">Notes (optional)</label>
        <textarea name="notes" rows="3"
                  class="<?php echo e($areaBase); ?> <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($errBorder); ?> <?php echo e($errBg); ?> <?php echo e($errRing); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                  placeholder="Any extra context..."><?php echo e(old('notes')); ?></textarea>
        <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
          <div class="mt-1 text-xs <?php echo e($errText); ?>"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
      </div>

    </div>

    <div class="mt-6 flex items-center justify-between">
      <a href="<?php echo e(route('purchases.index')); ?>" class="<?php echo e($btnLink); ?>">Cancel</a>

      <button type="submit" class="<?php echo e($btnPrimary); ?>">
        Save draft
      </button>
    </div>

  </div>
</form>


<script>
  (function () {
    const hasDepotErr = <?php echo $errors->has('depot_id') ? 'true' : 'false'; ?>;
    if (hasDepotErr) {
      const depotWrap = document.getElementById('depot-wrap');
      depotWrap?.classList.remove('hidden');
    }
  })();
</script>

<script>
  function applySelectedTypeStyles(val) {
    document.querySelectorAll('.type-card').forEach(card => {
      const cardType = card.getAttribute('data-type-card');
      const badge = card.querySelector('.type-check');

      if (cardType === val) {
        card.classList.add(
          'border-emerald-500/50',
          'bg-gradient-to-r',
          'from-emerald-500/10',
          'via-emerald-500/5',
          'to-cyan-500/10'
        );
        badge?.classList.remove('hidden');
      } else {
        card.classList.remove(
          'border-emerald-500/50',
          'bg-gradient-to-r',
          'from-emerald-500/10',
          'via-emerald-500/5',
          'to-cyan-500/10'
        );
        badge?.classList.add('hidden');
      }
    });
  }

  function syncPurchaseTypeUI() {
    const val = document.querySelector('input[name="type"]:checked')?.value || 'import';

    const ctx = document.getElementById('type-context');
    const depotWrap = document.getElementById('depot-wrap');

    applySelectedTypeStyles(val);

    if (val === 'import') {
      ctx.textContent = "This purchase will enter nominations and transport workflow after confirmation.";
      depotWrap.classList.add('hidden');
    } else if (val === 'local_depot') {
      ctx.textContent = "This is a local depot ownership change. After confirmation, receive it into the selected depot.";
      depotWrap.classList.remove('hidden');
    } else {
      ctx.textContent = "Cross dock: on confirmation we receipt into CROSS DOCK and you can sell directly.";
      depotWrap.classList.add('hidden');
    }
  }

  document.querySelectorAll('.js-type').forEach(r => r.addEventListener('change', syncPurchaseTypeUI));
  syncPurchaseTypeUI();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/create.blade.php ENDPATH**/ ?>