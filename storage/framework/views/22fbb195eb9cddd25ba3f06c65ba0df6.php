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
    'posted' => 'border-emerald-500/30 bg-emerald-600/15 text-emerald-100',
    default  => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };

  $editPayload = [
    'id'            => (int) $sale->id,
    'depot_id'      => (int) $sale->depot_id,
    'product_id'    => (int) $sale->product_id,
    'client_name'   => (string) ($sale->client_name ?? ''),
    'sale_date'     => $sale->sale_date?->format('Y-m-d'),
    'currency'      => (string) ($sale->currency ?? 'USD'),
    'qty'           => (string) ($sale->qty ?? ''),
    'unit_price'    => (string) ($sale->unit_price ?? ''),
    'delivery_mode' => (string) ($sale->delivery_mode ?? 'ex_depot'),
    'transporter_id'=> $sale->transporter_id ? (int) $sale->transporter_id : null,
    'truck_no'      => (string) ($sale->truck_no ?? ''),
    'trailer_no'    => (string) ($sale->trailer_no ?? ''),
    'waybill_no'    => (string) ($sale->waybill_no ?? ''),
    'delivery_notes'=> (string) ($sale->delivery_notes ?? ''),
  ];
?>

<div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-5">
  <div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="flex items-center gap-3">
        <div class="text-xs <?php echo e($muted); ?>">#<?php echo e($sale->reference); ?></div>
        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?php echo e($sale->status === 'posted' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700' : 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]'); ?>">
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
        <button type="button"
                id="btnEditSale"
                data-sale='<?php echo json_encode($editPayload, 15, 512) ?>'
                class="inline-flex items-center gap-2 h-10 px-3 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($fg); ?>

                       hover:bg-[color:var(--tw-surface)] transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 0 4 19.5z"/>
            <path d="M14.5 6.5l3 3"/>
            <path d="M9.5 14.5l-1 4 4-1 6.5-6.5-3-3z"/>
          </svg>
          Edit
        </button>
      <?php endif; ?>

      
      <?php if($sale->status === 'draft'): ?>
        <?php $modalId = 'postSaleModal_' . $sale->id; $btnId = 'btnPostSale_' . $sale->id; $closeId = 'closePostSale_' . $sale->id; $cancelId = 'cancelPostSale_' . $sale->id; $confirmId = 'confirmPostSale_' . $sale->id; $formId = 'postSaleForm_' . $sale->id; ?>
        <form method="POST" action="<?php echo e(route('sales.post', $sale)); ?>" id="<?php echo e($formId); ?>">
          <?php echo csrf_field(); ?>
          <button type="button" id="<?php echo e($btnId); ?>"
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
<?php $modalId = 'postSaleModal_' . $sale->id; $btnId = 'btnPostSale_' . $sale->id; $closeId = 'closePostSale_' . $sale->id; $cancelId = 'cancelPostSale_' . $sale->id; $confirmId = 'confirmPostSale_' . $sale->id; $formId = 'postSaleForm_' . $sale->id; ?>
<div id="<?php echo e($modalId); ?>" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/60"></div>

  <div class="relative h-full w-full p-4 flex items-center justify-center">
    <div class="w-full max-w-lg rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-xl overflow-hidden">
      <div class="max-h-[85vh] overflow-y-auto">
        <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?>">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-base font-semibold <?php echo e($fg); ?>">Post sale</div>
              <div class="mt-1 text-xs <?php echo e($muted); ?>">This will issue stock FIFO and cannot be undone.</div>
            </div>
            <button type="button" id="<?php echo e($closeId); ?>"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> <?php echo e($fg); ?> hover:bg-(--tw-surface-2) transition">✕</button>
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
          <button type="button" id="<?php echo e($cancelId); ?>"
            class="h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> text-sm font-semibold <?php echo e($fg); ?> hover:bg-(--tw-surface-2) transition">
            Cancel
          </button>
          <button type="button" id="<?php echo e($confirmId); ?>"
            class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-500/20 transition">
            Yes, post
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  const btn = document.getElementById(<?php echo json_encode($btnId, 15, 512) ?>);
  const modal = document.getElementById(<?php echo json_encode($modalId, 15, 512) ?>);
  const closeBtn = document.getElementById(<?php echo json_encode($closeId, 15, 512) ?>);
  const cancelBtn = document.getElementById(<?php echo json_encode($cancelId, 15, 512) ?>);
  const confirmBtn = document.getElementById(<?php echo json_encode($confirmId, 15, 512) ?>);
  const form = document.getElementById(<?php echo json_encode($formId, 15, 512) ?>);

  const lockBody = (locked) => {
    document.documentElement.classList.toggle('overflow-hidden', !!locked);
    document.body.classList.toggle('overflow-hidden', !!locked);
  };

  const open = () => { modal && modal.classList.remove('hidden'); lockBody(true); };
  const close = () => { modal && modal.classList.add('hidden'); lockBody(false); };

  on(btn, 'click', open);
  on(closeBtn, 'click', close);
  on(cancelBtn, 'click', close);

  on(modal, 'click', (e) => {
    if (e.target === modal || e.target === modal.firstElementChild) close();
  });

  on(confirmBtn, 'click', () => { close(); form && form.submit(); });

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) close(); });
})();
</script>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/sales/partials/details.blade.php ENDPATH**/ ?>