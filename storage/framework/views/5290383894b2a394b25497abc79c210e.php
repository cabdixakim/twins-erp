
<?php
  // Expect: $currentDepot, $border, $surface, $surface2, $fg, $muted
?>

<div id="depotAdjustModal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60"></div>

  <div class="relative h-full w-full p-4 flex items-center justify-center">
    <div class="w-full max-w-lg rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-xl overflow-hidden">
      <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?>">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <div class="text-base font-semibold <?php echo e($fg); ?>">Adjustment</div>
            <div class="mt-1 text-xs <?php echo e($muted); ?>">
              Create a manual stock correction for <span class="<?php echo e($fg); ?> font-semibold"><?php echo e($currentDepot->name); ?></span>.
              (Wiring comes next.)
            </div>
          </div>
          <button type="button" id="closeDepotAdjust"
                  class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?>

                         <?php echo e($fg); ?> hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
        </div>
      </div>

      <div class="p-5">
        <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-4">
          <div class="text-sm font-semibold <?php echo e($fg); ?>">Not wired yet</div>
          <div class="mt-1 text-xs <?php echo e($muted); ?>">
            This is the modal shell only. Next we’ll add:
            product selector, +/− quantity, reason, reference, and then post into inventory movements + ledger.
          </div>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
          <button type="button" id="cancelDepotAdjust"
                  class="h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($fg); ?>

                         hover:bg-[color:var(--tw-surface)] transition">
            Close
          </button>

          <button type="button" disabled
                  class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white opacity-50 cursor-not-allowed">
            Save adjustment
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  const modal = document.getElementById('depotAdjustModal');
  const openBtn = document.getElementById('btnDepotAdjust');
  const closeBtn = document.getElementById('closeDepotAdjust');
  const cancelBtn = document.getElementById('cancelDepotAdjust');
  const overlay = modal?.firstElementChild;

  const lockBody = (locked) => {
    document.documentElement.classList.toggle('overflow-hidden', !!locked);
    document.body.classList.toggle('overflow-hidden', !!locked);
  };

  const open = () => {
    if (!modal) return;
    modal.classList.remove('hidden');
    lockBody(true);
  };

  const close = () => {
    if (!modal) return;
    modal.classList.add('hidden');
    lockBody(false);
  };

  on(openBtn, 'click', open);
  on(closeBtn, 'click', close);
  on(cancelBtn, 'click', close);
  on(overlay, 'click', close);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) close();
  });
})();
</script><?php /**PATH /home/runner/workspace/resources/views/depot-stock/partials/adjustment-modal.blade.php ENDPATH**/ ?>