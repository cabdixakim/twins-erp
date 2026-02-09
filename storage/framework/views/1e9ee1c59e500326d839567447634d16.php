

<?php $__env->startSection('title', 'Purchases'); ?>
<?php $__env->startSection('subtitle', 'Draft → Confirmed creates a Batch'); ?>

<?php $__env->startSection('content'); ?>

<?php
  $totalCount = method_exists($purchases, 'total') ? $purchases->total() : (is_countable($purchases) ? count($purchases) : null);

  // If controller hasn't provided options yet, fallback.
  $supplierOptions = $supplierOptions ?? [];
  $typeOptions     = $typeOptions ?? ['import','local_depot','cross_dock'];
  $statusOptions   = $statusOptions ?? ['draft','confirmed'];

  // Theme tokens
  $fg      = 'text-[color:var(--tw-fg)]';
  $muted   = 'text-[color:var(--tw-muted)]';
  $bg      = 'bg-[color:var(--tw-bg)]';
  $surface = 'bg-[color:var(--tw-surface)]';
  $surface2= 'bg-[color:var(--tw-surface-2)]';
  $border  = 'border-[color:var(--tw-border)]';
  $ring    = 'focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]';

  // Reusable button styles
  $btnBase   = 'inline-flex items-center justify-center gap-2 rounded-xl border font-semibold transition select-none';
  $btnGhost  = $btnBase.' border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] '.$fg.' hover:bg-[color:var(--tw-surface)]';
  $btnPrime  = $btnBase.' border-[color:var(--tw-accent)] bg-[color:var(--tw-accent-soft)] '.$fg.' hover:brightness-110';

  $pillBase  = 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold';
?>

<div class="flex flex-col gap-4">

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 sm:p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-3">
          <span class="h-9 w-9 rounded-2xl grid place-items-center <?php echo e($surface2); ?> border <?php echo e($border); ?>">
            <span class="text-base" aria-hidden="true">🧾</span>
          </span>

          <div class="min-w-0">
            <div class="flex items-center gap-2 min-w-0">
              <h1 class="text-[15px] sm:text-base font-semibold <?php echo e($fg); ?> leading-tight truncate">Purchases</h1>

              <?php if(!is_null($totalCount)): ?>
                <span class="shrink-0 <?php echo e($pillBase); ?> <?php echo e($border); ?> <?php echo e($surface2); ?> <?php echo e($fg); ?>">
                  <?php echo e(number_format($totalCount)); ?>

                </span>
              <?php endif; ?>
            </div>

            <p class="mt-0.5 text-[11px] sm:text-[12px] <?php echo e($muted); ?> leading-snug">
              Draft → Confirmed creates a Batch.
            </p>
          </div>
        </div>
      </div>

      <div class="shrink-0 flex items-center gap-2">
        
        <button
          type="button"
          id="btnExportPurchasesCsvTop"
          class="<?php echo e($btnGhost); ?> h-9 w-9 sm:w-auto sm:px-3 sm:gap-2 text-[12px]"
          aria-label="Export CSV"
        >
          <span class="text-base leading-none" aria-hidden="true">⤓</span>
          <span class="hidden sm:inline">Export</span>
        </button>

        
        <a
          href="<?php echo e(route('purchases.create')); ?>"
          class="<?php echo e($btnPrime); ?> h-9 px-3 sm:h-10 sm:px-4 text-[12px] sm:text-[13px]"
        >
          <span class="text-base leading-none" aria-hidden="true">＋</span>
          <span class="hidden xs:inline">New</span>
          <span class="xs:hidden">Add</span>
        </a>
      </div>
    </div>

    <p class="mt-3 text-[12px] sm:text-sm <?php echo e($muted); ?> leading-snug">
      Manage procurement records. Confirming a draft creates a batch and routes it into the correct workflow.
    </p>
  </div>

  
  <style>
    @media (min-width: 420px){ .xs\:inline{display:inline} .xs\:hidden{display:none} }
    @media (max-width: 419px){ .xs\:inline{display:none} .xs\:hidden{display:inline} }
  </style>

  <?php if(session('status')): ?>
    <div class="rounded-xl border border-emerald-500/30 bg-[color:var(--tw-accent-soft)] p-3 text-sm text-emerald-100">
      <?php echo nl2br(e(session('status'))); ?>

    </div>
  <?php endif; ?>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 sm:p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="text-[13px] sm:text-sm font-semibold <?php echo e($fg); ?>">Filters</div>
        <div class="mt-1 text-[11px] sm:text-xs <?php echo e($muted); ?> leading-snug">
          Search + dropdowns. Export downloads only the visible rows on this page.
        </div>
      </div>

      <!-- 
      <button
        type="button"
        id="btnExportPurchasesCsvInline"
        class="hidden sm:inline-flex <?php echo e($btnGhost); ?> h-10 px-3 text-[12px]"
      >
        Export CSV
      </button> -->
    </div>

    <form method="GET" action="<?php echo e(url()->current()); ?>"
          class="mt-4 grid grid-cols-1 gap-3
                 lg:grid-cols-[minmax(0,1fr)_220px_170px_170px_auto] lg:items-end">

      
      <div class="min-w-0">
        <label class="block text-[11px] <?php echo e($muted); ?> mb-1">Search</label>
        <div class="relative">
          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 <?php echo e($muted); ?>">⌕</span>
          <input
            type="text"
            name="q"
            value="<?php echo e(request('q')); ?>"
            placeholder="Purchase #, batch #, supplier..."
            class="h-10 w-full rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> pl-9 pr-3 text-sm <?php echo e($fg); ?>

                   placeholder:text-[color:var(--tw-muted)] focus:outline-none <?php echo e($ring); ?>"
          />
        </div>
      </div>

      
      <div class="min-w-0">
        <label class="block text-[11px] <?php echo e($muted); ?> mb-1">Supplier</label>
        <select
          name="supplier"
          class="h-10 w-full rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 text-sm <?php echo e($fg); ?>

                 focus:outline-none <?php echo e($ring); ?>"
        >
          <option value="">All</option>
          <?php $__currentLoopData = $supplierOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($s); ?>" <?php if(request('supplier') === $s): echo 'selected'; endif; ?>><?php echo e($s); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      
      <div class="min-w-0">
        <label class="block text-[11px] <?php echo e($muted); ?> mb-1">Type</label>
        <select
          name="type"
          class="h-10 w-full rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 text-sm <?php echo e($fg); ?>

                 focus:outline-none <?php echo e($ring); ?>"
        >
          <option value="">All</option>
          <?php $__currentLoopData = $typeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($t); ?>" <?php if(request('type') === $t): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_',' ', $t))); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      
      <div class="min-w-0">
        <label class="block text-[11px] <?php echo e($muted); ?> mb-1">Status</label>
        <select
          name="status"
          class="h-10 w-full rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 text-sm <?php echo e($fg); ?>

                 focus:outline-none <?php echo e($ring); ?>"
        >
          <option value="">All</option>
          <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($st); ?>" <?php if(request('status') === $st): echo 'selected'; endif; ?>><?php echo e(ucfirst($st)); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      
      <div class="flex items-center gap-2 justify-end">
        <button
          type="submit"
          class="h-10 <?php echo e($btnGhost); ?> px-4 text-[13px]"
        >
          Filter
        </button>

        <a
          href="<?php echo e(url()->current()); ?>"
          class="h-10 <?php echo e($btnGhost); ?> px-4 text-[13px]"
        >
          Reset
        </a>
      </div>
    </form>

    
    <button
      type="button"
      id="btnExportPurchasesCsvMobile"
      class="mt-3 sm:hidden w-full <?php echo e($btnGhost); ?> h-10 px-4 text-[13px]"
    >
      Export CSV
    </button>
  </div>

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> overflow-hidden">
    <div class="px-3 sm:px-5 py-3 sm:py-4 border-b <?php echo e($border); ?> flex items-start sm:items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="text-[13px] sm:text-sm font-semibold <?php echo e($fg); ?>">Recent purchases</div>
        <div class="mt-0.5 text-[11px] sm:text-xs <?php echo e($muted); ?>">Tap a row to open the purchase.</div>
      </div>

      <div class="hidden sm:flex items-center gap-2">
        <button
          type="button"
          id="btnExportPurchasesCsvTable"
          class="<?php echo e($btnGhost); ?> h-10 px-3 text-[12px]"
        >
          Export CSV
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm" id="purchasesTable">
        <thead class="<?php echo e($surface2); ?>">
          <tr class="text-left text-xs <?php echo e($muted); ?>">
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Purchase</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Supplier</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Type</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Qty</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Unit price</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Est. total</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Status</th>
            <th class="px-3 sm:px-5 py-3 whitespace-nowrap">Date</th>
            <th class="px-3 sm:px-5 py-3 text-right whitespace-nowrap">Action</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-[color:var(--tw-border)]">
          <?php $__empty_1 = true; $__currentLoopData = $purchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $typeLabel = match($p->type) {
                'import' => 'Import',
                'local_depot' => 'Local depot',
                'cross_dock' => 'Cross dock',
                default => ucfirst($p->type),
              };

              $statusClasses = match($p->status) {
                'draft' =>
                    'border-slate-400/40 bg-slate-200 text-slate-900
                    dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',

                'confirmed' =>
                    'border-emerald-600 bg-emerald-500 text-white
                    dark:border-emerald-400 dark:bg-emerald-400 dark:text-emerald-950',

                default =>
                    'border-slate-400/40 bg-slate-200 text-slate-900
                    dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
              };

              $qty = (float) ($p->qty ?? 0);
              $unit = (float) ($p->unit_price ?? 0);
              $total = $qty * $unit;

              // Adjust to your actual supplier storage
              $supplierName = $p->supplier_name ?? ($p->supplier?->name ?? ($p->supplier ?? '—'));

              $showUrl = route('purchases.show', $p);
            ?>

            <tr class="group hover:bg-[color:var(--tw-surface-2)] cursor-pointer"
                data-href="<?php echo e($showUrl); ?>"
                data-export-row="1">
              <td class="px-3 sm:px-5 py-4">
                <div class="font-semibold <?php echo e($fg); ?>">
                  Purchase #<?php echo e($p->display_ref ?? $p->id); ?>

                </div>
                <div class="mt-1 text-xs <?php echo e($muted); ?>">
                  <?php if($p->batch_id): ?>
                    Batch: <span class="<?php echo e($fg); ?>">#<?php echo e($p->batch_id); ?></span>
                  <?php else: ?>
                    <span>No batch yet</span>
                  <?php endif; ?>
                </div>
              </td>

              <td class="px-3 sm:px-5 py-4 <?php echo e($fg); ?>">
                <?php echo e($supplierName); ?>

              </td>

              <td class="px-3 sm:px-5 py-4">
                <span class="inline-flex items-center rounded-full border <?php echo e($border); ?> <?php echo e($surface2); ?> px-2.5 py-1 text-xs <?php echo e($fg); ?>">
                  <?php echo e($typeLabel); ?>

                </span>
              </td>

              <td class="px-3 sm:px-5 py-4 <?php echo e($fg); ?>">
                <?php echo e(number_format($qty, 3)); ?>

                <span class="text-xs <?php echo e($muted); ?>">L</span>
              </td>

              <td class="px-3 sm:px-5 py-4 <?php echo e($fg); ?>">
                <span class="<?php echo e($muted); ?>"><?php echo e(strtoupper($p->currency ?? 'USD')); ?></span>
                <?php echo e(number_format($unit, 6)); ?>

              </td>

              <td class="px-3 sm:px-5 py-4 <?php echo e($fg); ?>">
                <span class="<?php echo e($muted); ?>"><?php echo e(strtoupper($p->currency ?? 'USD')); ?></span>
                <?php echo e(number_format($total, 2)); ?>

              </td>

              <td class="px-3 sm:px-5 py-4">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs <?php echo e($statusClasses); ?>">
                  <?php echo e(ucfirst($p->status)); ?>

                </span>
              </td>

              <td class="px-3 sm:px-5 py-4 <?php echo e($muted); ?>">
                <?php echo e($p->purchase_date?->format('Y-m-d') ?? '—'); ?>

              </td>

              <td class="px-3 sm:px-5 py-4 text-right">
                <a href="<?php echo e($showUrl); ?>"
                   class="inline-flex items-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 py-1.5 text-xs font-semibold <?php echo e($fg); ?>

                          hover:bg-[color:var(--tw-surface)]"
                   onclick="event.stopPropagation();">
                  Open
                </a>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="9" class="px-5 py-10 text-center text-sm <?php echo e($muted); ?>">
                No purchases yet.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="px-3 sm:px-5 py-4 border-t <?php echo e($border); ?>">
      <?php echo e($purchases->links()); ?>

    </div>
  </div>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
  // Row click → open show page
  document.addEventListener('click', (e) => {
    const row = e.target.closest('tr[data-href]');
    if (!row) return;
    if (e.target.closest('a,button,input,select,textarea,label')) return;
    window.location.href = row.getAttribute('data-href');
  });

  function exportCsv() {
    const table = document.getElementById('purchasesTable');
    if (!table) return;

    const rows = Array.from(table.querySelectorAll('tbody tr[data-export-row="1"]'));
    if (!rows.length) return;

    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
    headers.pop(); // remove Action

    const csvEscape = (v) => {
      const s = String(v ?? '').replace(/\r?\n|\r/g, ' ').trim();
      if (/[",]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
      return s;
    };

    const data = rows.map(tr => {
      const tds = Array.from(tr.querySelectorAll('td')).map(td => td.innerText.trim());
      tds.pop(); // remove Action
      return tds.map(csvEscape).join(',');
    });

    const csv = [headers.map(csvEscape).join(','), ...data].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });

    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `purchases_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 500);
  }

  [
    'btnExportPurchasesCsvTop',
    'btnExportPurchasesCsvInline',
    'btnExportPurchasesCsvMobile',
    'btnExportPurchasesCsvTable'
  ].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('click', exportCsv);
  });
})();
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/purchases/index.blade.php ENDPATH**/ ?>