<?php
  /** @var \App\Models\Sale $sale */

  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $qty   = (float) ($sale->qty ?? 0);
  $unit  = (float) ($sale->unit_price ?? 0);
  $total = (float) ($sale->total ?? ($qty * $unit));
  $cur   = strtoupper($sale->currency ?? 'USD');

  $statusPill = match($sale->status) {
    'draft'  => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'posted' => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-100',
    default  => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };
?>

<div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-5">
  <div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="flex items-center gap-3">
        <div class="text-xs <?php echo e($muted); ?>">#<?php echo e($sale->reference); ?></div>
        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?php echo e($statusPill); ?>">
          <?php echo e(ucfirst($sale->status)); ?>

        </span>
        <?php if($sale->inventory_movement_id): ?>
          <span class="inline-flex items-center rounded-full border <?php echo e($border); ?> <?php echo e($surface2); ?> px-2.5 py-1 text-xs font-semibold <?php echo e($fg); ?>">
            Movement #<?php echo e($sale->inventory_movement_id); ?>

          </span>
        <?php endif; ?>
      </div>

      <div class="mt-2 text-lg font-semibold <?php echo e($fg); ?> truncate">
        <?php echo e($sale->client_name ?: 'Client —'); ?>

      </div>

      <div class="mt-1 text-xs <?php echo e($muted); ?>">
        <?php echo e($sale->depot?->name ?? 'Depot'); ?> · <?php echo e($sale->product?->name ?? 'Product'); ?> · <?php echo e($sale->sale_date?->format('Y-m-d') ?? '—'); ?>

      </div>
    </div>

    <div class="shrink-0 flex items-center gap-2">
      <?php if($sale->status === 'draft'): ?>
        <form method="POST" action="<?php echo e(route('sales.post', $sale)); ?>" id="postSaleForm">
          <?php echo csrf_field(); ?>
          <button type="button" id="btnPostSale"
            class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-500/20 transition">
            Post sale
            <span class="text-emerald-200/90">→</span>
          </button>
        </form>
      <?php else: ?>
        <span class="inline-flex items-center h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($muted); ?>">
          Posted
        </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-5 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
      <div class="text-[11px] <?php echo e($muted); ?>">Quantity</div>
      <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e(number_format($qty, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span></div>
    </div>
    <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
      <div class="text-[11px] <?php echo e($muted); ?>">Unit price</div>
      <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($cur); ?> <?php echo e(number_format($unit, 6)); ?></div>
    </div>
    <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
      <div class="text-[11px] <?php echo e($muted); ?>">Total</div>
      <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($cur); ?> <?php echo e(number_format($total, 2)); ?></div>
    </div>
  </div>

  <div class="mt-3 grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
      <div class="text-[11px] <?php echo e($muted); ?>">COGS (FIFO)</div>
      <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($cur); ?> <?php echo e(number_format((float)$sale->cogs_total, 2)); ?></div>
    </div>
    <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
      <div class="text-[11px] <?php echo e($muted); ?>">Gross profit</div>
      <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($cur); ?> <?php echo e(number_format((float)$sale->gross_profit, 2)); ?></div>
    </div>
    <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
      <div class="text-[11px] <?php echo e($muted); ?>">Delivery</div>
      <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>">
        <?php echo e($sale->delivery_mode === 'delivered' ? 'Delivered' : 'Ex-depot'); ?>

      </div>
    </div>
  </div>

  <?php if($sale->delivery_mode === 'delivered'): ?>
    <div class="mt-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
      <div class="text-[11px] <?php echo e($muted); ?>">Transport details</div>
      <div class="mt-1 text-sm <?php echo e($fg); ?>">
        <span class="font-semibold">Transporter:</span> <?php echo e($sale->transporter?->name ?? '—'); ?>

        <span class="mx-2 <?php echo e($muted); ?>">·</span>
        <span class="font-semibold">Truck:</span> <?php echo e($sale->truck_no ?? '—'); ?>

        <span class="mx-2 <?php echo e($muted); ?>">·</span>
        <span class="font-semibold">Trailer:</span> <?php echo e($sale->trailer_no ?? '—'); ?>

      </div>
    </div>
  <?php endif; ?>
</div>


<?php if($sale->status === 'draft'): ?>
<div id="postSaleModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4">
  <div class="w-full max-w-lg rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-xl overflow-hidden">
    <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?>">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-base font-semibold <?php echo e($fg); ?>">Post sale</div>
          <div class="mt-1 text-xs <?php echo e($muted); ?>">This will issue stock FIFO and cannot be undone.</div>
        </div>
        <button type="button" id="closePostSale"
          class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?>

                 <?php echo e($fg); ?> hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
      </div>
    </div>

    <div class="p-5 space-y-3">
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
          <div class="text-[11px] <?php echo e($muted); ?>">Depot</div>
          <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($sale->depot?->name ?? '—'); ?></div>
        </div>
        <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
          <div class="text-[11px] <?php echo e($muted); ?>">Product</div>
          <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($sale->product?->name ?? '—'); ?></div>
        </div>
        <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
          <div class="text-[11px] <?php echo e($muted); ?>">Qty to issue</div>
          <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e(number_format($qty, 3)); ?> <span class="text-xs <?php echo e($muted); ?>">L</span></div>
        </div>
        <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
          <div class="text-[11px] <?php echo e($muted); ?>">Sale total</div>
          <div class="mt-1 text-sm font-semibold <?php echo e($fg); ?>"><?php echo e($cur); ?> <?php echo e(number_format($total, 2)); ?></div>
        </div>
      </div>

      <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3 text-xs <?php echo e($fg); ?>">
        <div class="font-semibold">What will happen</div>
        <ul class="mt-2 list-disc pl-5 <?php echo e($muted); ?> space-y-1">
          <li>Creates an <span class="<?php echo e($fg); ?>">ISSUE</span> movement</li>
          <li>Consumes depot stock by <span class="<?php echo e($fg); ?>">FIFO</span> (per batch)</li>
          <li>Writes consumption rows (audit proof)</li>
          <li>Updates batch remaining + depot stock</li>
        </ul>
      </div>
    </div>

    <div class="p-5 border-t <?php echo e($border); ?> <?php echo e($surface2); ?> flex items-center justify-end gap-2">
      <button type="button" id="cancelPostSale"
        class="h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> text-sm font-semibold <?php echo e($fg); ?>

               hover:bg-[color:var(--tw-surface-2)] transition">
        Cancel
      </button>
      <button type="button" id="confirmPostSale"
        class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-500/20 transition">
        Yes, post
      </button>
    </div>
  </div>
</div>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  const btn = document.getElementById('btnPostSale');
  const modal = document.getElementById('postSaleModal');
  const closeBtn = document.getElementById('closePostSale');
  const cancelBtn = document.getElementById('cancelPostSale');
  const confirmBtn = document.getElementById('confirmPostSale');
  const form = document.getElementById('postSaleForm');

  const open = () => modal && modal.classList.remove('hidden');
  const close = () => modal && modal.classList.add('hidden');

  on(btn, 'click', open);
  on(closeBtn, 'click', close);
  on(cancelBtn, 'click', close);
  on(modal, 'click', (e) => { if (e.target === modal) close(); });
  on(confirmBtn, 'click', () => { close(); form && form.submit(); });

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/sales/partials/details.blade.php ENDPATH**/ ?>