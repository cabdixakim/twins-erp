

<?php
  /** @var \App\Models\Purchase $purchase */
  $purchase = $purchase;

  // Theme tokens (from your app.css)
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  // Labels
  $typeLabel = match($purchase->type) {
    'import' => 'Import',
    'local_depot' => 'Local depot',
    'cross_dock' => 'Cross dock',
    default => ucfirst(str_replace('_',' ', (string) $purchase->type)),
  };

  // Status pill (tokenised + consistent)
  $statusPill = match($purchase->status) {
    'draft' => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'confirmed' => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100',
    'received' => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100',
    default => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };

  $qty   = (float) ($purchase->qty ?? 0);
  $unit  = (float) ($purchase->unit_price ?? 0);
  $total = $qty * $unit;

  $currency     = strtoupper($purchase->currency ?? 'USD');

  // Supplier display
  $supplierName = $purchase->supplier_name ?? ($purchase->supplier?->name ?? ($purchase->supplier ?? '—'));

  // Safe display values (won't crash even if relations missing)
  $productName = data_get($purchase, 'product.name') ?: ('Product #' . (int)($purchase->product_id ?? 0));
  $depotName   = data_get($purchase, 'depot.name') ?: ($purchase->depot_id ? ('Depot #' . (int)$purchase->depot_id) : '—');

  $ref = $purchase->reference ?? ($purchase->display_ref ?? $purchase->id);
?>



<?php $__env->startSection('title', 'Purchase'); ?>
<?php $__env->startSection('subtitle', 'Review and confirm'); ?>

<?php $__env->startSection('content'); ?>

<div class="flex flex-col gap-4">

  
  <div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="flex items-center gap-3">
        <h1 class="text-xl font-semibold <?php echo e($fg); ?>">
          Purchase #<?php echo e($ref); ?>

        </h1>

        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?php echo e($statusPill); ?>">
          <?php echo e(ucfirst((string)$purchase->status)); ?>

        </span>

        <?php if($purchase->batch_id): ?>
          <span class="inline-flex items-center rounded-full border <?php echo e($border); ?> <?php echo e($surface2); ?> px-2.5 py-1 text-xs font-semibold <?php echo e($fg); ?>">
            Batch #<?php echo e($purchase->batch_id); ?>

          </span>
        <?php endif; ?>
      </div>

      <p class="mt-1 text-sm <?php echo e($muted); ?>">
        Review key details and confirm when ready. Confirming creates a Batch and routes it into the correct workflow.
      </p>
    </div>

    <div class="shrink-0 flex items-center gap-2">
      <a href="<?php echo e(route('purchases.index')); ?>"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($fg); ?>

                hover:bg-[color:var(--tw-surface)]">
        <span class="text-base">←</span>
        Back
      </a>

      
      <?php if($purchase->status === 'draft'): ?>
        
        <form method="POST" action="<?php echo e(route('purchases.confirm', $purchase)); ?>" id="confirmForm">
          <?php echo csrf_field(); ?>
          <button type="button"
                  id="btnConfirm"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600
                         text-sm font-semibold text-white hover:bg-emerald-500/20 transition">
            Confirm
            <span class="text-emerald-200/90">→</span>
          </button>
        </form>

      <?php else: ?>
        
        <span class="inline-flex items-center h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($muted); ?>">
          <?php echo e(ucfirst((string)$purchase->status)); ?>

        </span>

        
        <?php if($purchase->type === 'local_depot' && $purchase->status === 'confirmed'): ?>
          <form method="POST" action="<?php echo e(route('purchases.receive', $purchase)); ?>" id="receiveForm">
            <?php echo csrf_field(); ?>
            <button type="button"
                    id="btnReceive"
                    class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30
                           bg-[color:var(--tw-accent-soft)] text-emerald-900 dark:text-emerald-100
                           text-sm font-semibold hover:bg-emerald-500/20 transition">
              Receive into depot
              <span class="opacity-80">↓</span>
            </button>
          </form>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>

  <?php if(session('status')): ?>
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 dark:bg-emerald-500/20 p-3 text-sm text-emerald-900 dark:text-emerald-100">
      <?php echo nl2br(e(session('status'))); ?>

    </div>
  <?php endif; ?>

  <?php if(session('error')): ?>
    <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 dark:bg-rose-500/20 p-3 text-sm text-rose-900 dark:text-rose-100">
      <?php echo e(session('error')); ?>

    </div>
  <?php endif; ?>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-5">
    <div class="grid gap-4 sm:grid-cols-2">

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Supplier</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($supplierName); ?></div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Product</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($productName); ?></div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Type</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($typeLabel); ?></div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Date</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($purchase->purchase_date?->format('Y-m-d') ?? '—'); ?></div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Quantity</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
          <?php echo e(number_format($qty, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
        </div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Unit price</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
          <span class="<?php echo e($muted); ?>"><?php echo e($currency); ?></span>
          <?php echo e(number_format($unit, 6)); ?>

        </div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Estimated total</div>
        <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
          <span class="<?php echo e($muted); ?>"><?php echo e($currency); ?></span>
          <?php echo e(number_format($total, 2)); ?>

        </div>
      </div>

      <?php if($purchase->type === 'local_depot'): ?>
        <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
          <div class="text-[11px] <?php echo e($muted); ?>">Depot</div>
          <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($depotName); ?></div>
        </div>
      <?php endif; ?>

      <div class="sm:col-span-2 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
        <div class="text-[11px] <?php echo e($muted); ?>">Notes</div>
        <div class="mt-1 text-sm <?php echo e($fg); ?>"><?php echo e($purchase->notes ?: '—'); ?></div>
      </div>
    </div>

    
    <div class="mt-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($fg); ?>">
      <?php if($purchase->type === 'import'): ?>
        <span class="<?php echo e($muted); ?>">After confirmation:</span>
        this purchase waits for nominations/offload to be received into a depot.
      <?php elseif($purchase->type === 'local_depot'): ?>
        <span class="<?php echo e($muted); ?>">After confirmation:</span>
        receive it into the selected depot from Depot Stock.
      <?php else: ?>
        <span class="<?php echo e($muted); ?>">After confirmation:</span>
        receipt into <span class="font-semibold">CROSS DOCK</span> immediately, ready for direct sale.
      <?php endif; ?>
    </div>

    
    <div class="mt-4 flex flex-wrap items-center gap-2">
      <a href="<?php echo e(route('purchases.index')); ?>"
         class="inline-flex items-center h-9 px-3 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-xs font-semibold <?php echo e($fg); ?>

                hover:bg-[color:var(--tw-surface)]">
        ← Back to list
      </a>
    </div>
  </div>

</div>


<?php if($purchase->status === 'draft'): ?>
  <div id="confirmModal" class="fixed inset-0 z-50 hidden">
    
    <div class="absolute inset-0 bg-black/60" data-close="confirm"></div>

    
    <div class="relative h-full w-full flex items-center justify-center p-4">
      <div class="w-full max-w-xl rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-2xl overflow-hidden">
        
        <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?>">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-base font-semibold <?php echo e($fg); ?>">Confirm purchase</div>
              <div class="mt-1 text-xs <?php echo e($muted); ?>">
                Locks the draft, creates/attaches a batch, and routes it into the correct workflow.
              </div>
            </div>

            <button type="button" data-close="confirm"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?>

                           <?php echo e($fg); ?> hover:bg-[color:var(--tw-surface-2)] transition"
                    aria-label="Close">
              ✕
            </button>
          </div>
        </div>

        
        <div class="p-5 space-y-4">
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Purchase</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($ref); ?></div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Supplier</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($supplierName); ?></div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Product</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($productName); ?></div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Type</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($typeLabel); ?></div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Quantity</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
                <?php echo e(number_format($qty, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
              </div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Cost</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
                <span class="<?php echo e($muted); ?>"><?php echo e($currency); ?></span> <?php echo e(number_format($unit, 6)); ?>

                <span class="mx-2 <?php echo e($muted); ?>">·</span>
                <span class="<?php echo e($muted); ?>"><?php echo e($currency); ?></span> <?php echo e(number_format($total, 2)); ?>

              </div>
            </div>
          </div>

          <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($fg); ?>">
            <div class="font-semibold">What happens after confirm</div>

            <?php if($purchase->type === 'import'): ?>
              <div class="mt-1 <?php echo e($muted); ?>">
                Batch is created. Stock is <span class="<?php echo e($fg); ?> font-semibold">not received</span> yet — it will be received during offload.
              </div>
            <?php elseif($purchase->type === 'local_depot'): ?>
              <div class="mt-1 <?php echo e($muted); ?>">
                Batch is created. Next step is receiving into: <span class="<?php echo e($fg); ?> font-semibold"><?php echo e($depotName); ?></span>.
              </div>
            <?php else: ?>
              <div class="mt-1 <?php echo e($muted); ?>">
                Batch is created and stock is receipted into <span class="<?php echo e($fg); ?> font-semibold">CROSS DOCK</span> immediately.
              </div>
            <?php endif; ?>
          </div>

          <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($muted); ?>">
            Tip: if you confirm twice by mistake, the backend should stay idempotent (no duplicate receipts).
          </div>
        </div>

        
        <div class="p-5 border-t <?php echo e($border); ?> <?php echo e($surface2); ?> flex items-center justify-end gap-2">
          <button type="button" data-close="confirm"
                  class="h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> text-sm font-semibold <?php echo e($fg); ?>

                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>

          <button type="button" id="confirmConfirm"
                  class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white
                         hover:bg-emerald-500/20 transition">
            Yes, confirm
          </button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>


<?php if($purchase->type === 'local_depot' && $purchase->status === 'confirmed'): ?>
  <div id="receiveModal" class="fixed inset-0 z-50 hidden">
    
    <div class="absolute inset-0 bg-black/60" data-close="receive"></div>

    
    <div class="relative h-full w-full flex items-center justify-center p-4">
      <div class="w-full max-w-xl rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-2xl overflow-hidden">
        
        <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?>">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="text-base font-semibold <?php echo e($fg); ?>">Receive into depot</div>
              <div class="mt-1 text-xs <?php echo e($muted); ?>">
                Posts a receipt movement, updates depot stock, and marks the purchase as received.
              </div>
            </div>

            <button type="button" data-close="receive"
                    class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?>

                           <?php echo e($fg); ?> hover:bg-[color:var(--tw-surface-2)] transition"
                    aria-label="Close">
              ✕
            </button>
          </div>
        </div>

        
        <div class="p-5 space-y-4">
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Depot</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($depotName); ?></div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Product</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?> truncate"><?php echo e($productName); ?></div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Quantity</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
                <?php echo e(number_format($qty, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span>
              </div>
            </div>

            <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
              <div class="text-[11px] <?php echo e($muted); ?>">Cost impact</div>
              <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
                <span class="<?php echo e($muted); ?>"><?php echo e($currency); ?></span> <?php echo e(number_format($unit, 6)); ?>

                <span class="mx-2 <?php echo e($muted); ?>">·</span>
                <span class="<?php echo e($muted); ?>"><?php echo e($currency); ?></span> <?php echo e(number_format($total, 2)); ?>

              </div>
            </div>
          </div>

          <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($fg); ?>">
            <div class="font-semibold">What will be posted</div>
            <ul class="mt-2 list-disc pl-5 <?php echo e($muted); ?> space-y-1">
              <li>Inventory movement: <span class="<?php echo e($fg); ?>">receipt</span> to <span class="<?php echo e($fg); ?>"><?php echo e($depotName); ?></span></li>
              <li>Depot stock row updated/created for this batch (FIFO-ready)</li>
              <li>Purchase status becomes <span class="<?php echo e($fg); ?>">received</span></li>
            </ul>
          </div>

          <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($muted); ?>">
            Tip: this is safe to retry — duplicates should be blocked by the receipt reference.
          </div>
        </div>

        
        <div class="p-5 border-t <?php echo e($border); ?> <?php echo e($surface2); ?> flex items-center justify-end gap-2">
          <button type="button" data-close="receive"
                  class="h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> text-sm font-semibold <?php echo e($fg); ?>

                         hover:bg-[color:var(--tw-surface-2)] transition">
            Cancel
          </button>

          
          <button type="button" id="confirmReceive"
                  class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-[color:var(--tw-accent-soft)]
                         text-sm font-semibold text-emerald-900 dark:text-emerald-100 hover:bg-emerald-500/20 transition">
            Yes, receive
          </button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<script>
  (function () {
    const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

    // Confirm modal
    const btnConfirm     = document.getElementById('btnConfirm');
    const confirmModal   = document.getElementById('confirmModal');
    const confirmConfirm = document.getElementById('confirmConfirm');
    const confirmForm    = document.getElementById('confirmForm');

    function openConfirm() {
      if (!confirmModal) return;
      confirmModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    }
    function closeConfirm() {
      if (!confirmModal) return;
      confirmModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    }

    on(btnConfirm, 'click', openConfirm);
    if (confirmModal) {
      confirmModal.querySelectorAll('[data-close="confirm"]').forEach(el => on(el, 'click', closeConfirm));
    }
    on(confirmConfirm, 'click', () => { closeConfirm(); confirmForm && confirmForm.submit(); });

    // Receive modal
    const btnReceive     = document.getElementById('btnReceive');
    const receiveModal   = document.getElementById('receiveModal');
    const confirmReceive = document.getElementById('confirmReceive');
    const receiveForm    = document.getElementById('receiveForm');

    function openReceive() {
      if (!receiveModal) return;
      receiveModal.classList.remove('hidden');
      document.documentElement.classList.add('overflow-hidden');
    }
    function closeReceive() {
      if (!receiveModal) return;
      receiveModal.classList.add('hidden');
      document.documentElement.classList.remove('overflow-hidden');
    }

    on(btnReceive, 'click', openReceive);
    if (receiveModal) {
      receiveModal.querySelectorAll('[data-close="receive"]').forEach(el => on(el, 'click', closeReceive));
    }
    on(confirmReceive, 'click', () => { closeReceive(); receiveForm && receiveForm.submit(); });

    // ESC closes any open modal
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      closeConfirm();
      closeReceive();
    });
  })();
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/show.blade.php ENDPATH**/ ?>