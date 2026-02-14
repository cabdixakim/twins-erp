

<?php
  $border   = 'border-[color:var(--tw-border)]';
  $surface  = 'bg-[color:var(--tw-surface)]';
  $surface2 = 'bg-[color:var(--tw-surface-2)]';
  $fg       = 'text-[color:var(--tw-fg)]';
  $muted    = 'text-[color:var(--tw-muted)]';

  $selectedId = $selected?->id ?? null;

  $fieldBase = "mt-1 w-full rounded-xl border {$border} {$surface2} p-2 text-sm {$fg} outline-none focus:ring-2 focus:ring-emerald-500/30";
  $fieldErr  = "border-rose-500/40 ring-2 ring-rose-500/20";
  $errText   = "mt-1 text-[11px] text-rose-300";

  $statusPill = fn($s) => match($s) {
    'draft'    => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
    'posted'   => 'border-emerald-500/30 bg-[color:var(--tw-accent-soft)] text-emerald-100',
    default    => 'border-[color:var(--tw-border)] bg-[color:var(--tw-surface-2)] text-[color:var(--tw-fg)]',
  };
?>

<?php $__env->startSection('title', 'Sales'); ?>
<?php $__env->startSection('subtitle', 'Draft → Posted issues stock (FIFO)'); ?>

<?php $__env->startSection('content'); ?>

<?php if(session('status')): ?>
  <div class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-100">
    <?php echo nl2br(e(session('status'))); ?>

  </div>
<?php endif; ?>

<?php if(session('error')): ?>
  <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-100">
    <?php echo e(session('error')); ?>

  </div>
<?php endif; ?>

<div class="grid gap-6 md:grid-cols-3">

  
  <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="text-sm font-semibold <?php echo e($fg); ?>">Sales</div>
        <div class="mt-1 text-xs <?php echo e($muted); ?>">Select a sale to view details.</div>
      </div>

      <button type="button" id="btnNewSale"
        class="inline-flex items-center gap-2 h-9 px-3 rounded-xl border border-emerald-500/30 bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-500/20 transition">
        + New
      </button>
    </div>

    <div class="mt-4 space-y-2">
      <?php $__empty_1 = true; $__currentLoopData = $sales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php $isActive = $selectedId === $s->id; $pill = $statusPill($s->status); ?>
        <a href="<?php echo e(route('sales.index', ['sale' => $s->id])); ?>"
           class="block rounded-xl border <?php echo e($border); ?> <?php echo e($isActive ? $surface2 : ''); ?> p-3 hover:bg-[color:var(--tw-surface-2)] transition">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs <?php echo e($muted); ?>">#<?php echo e($s->reference); ?></div>
              <div class="mt-0.5 text-sm font-semibold <?php echo e($fg); ?> truncate">
                <?php echo e($s->client_name ?: 'Client —'); ?>

              </div>
              <div class="mt-1 text-[11px] <?php echo e($muted); ?> truncate">
                <?php echo e($s->depot?->name ?? 'Depot'); ?> · <?php echo e($s->product?->name ?? 'Product'); ?>

              </div>
            </div>

            <span class="shrink-0 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold <?php echo e($pill); ?>">
              <?php echo e(ucfirst($s->status)); ?>

            </span>
          </div>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-xs <?php echo e($muted); ?>">No sales yet.</div>
      <?php endif; ?>
    </div>

    <div class="mt-4">
      <?php echo e($sales->links()); ?>

    </div>
  </div>

  
  <div class="md:col-span-2 space-y-4">
    <?php if(!$selected): ?>
      <div class="rounded-2xl border border-dashed <?php echo e($border); ?> <?php echo e($surface); ?> p-6 text-center">
        <div class="text-sm <?php echo e($fg); ?>">No sale selected.</div>
        <div class="mt-1 text-xs <?php echo e($muted); ?>">Create one using “New”.</div>
      </div>
    <?php else: ?>
      <?php echo $__env->make('sales.partials.details', ['sale' => $selected], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
  </div>

</div>


<div id="newSaleModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 p-4">
  <div class="w-full max-w-2xl rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-xl overflow-hidden">
    
    <div class="max-h-[85vh] overflow-y-auto">
      <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?>">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-base font-semibold <?php echo e($fg); ?>">New sale</div>
            <div class="mt-1 text-xs <?php echo e($muted); ?>">Draft first, then post to issue stock FIFO.</div>
          </div>
          <button type="button" id="closeNewSale"
            class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?>

                   <?php echo e($fg); ?> hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
        </div>
      </div>

      <form method="POST" action="<?php echo e(route('sales.store')); ?>" class="p-5" novalidate>
        <?php echo csrf_field(); ?>

        
        <?php if($errors->any()): ?>
          <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-sm text-rose-100">
            Please fix the highlighted fields.
          </div>
        <?php endif; ?>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="text-xs font-semibold <?php echo e($muted); ?>">Depot</label>
            <select name="depot_id" class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['depot_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
              <option value="">— Select depot —</option>
              <?php $__currentLoopData = $depots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($d->id); ?>" <?php if(old('depot_id') == $d->id): echo 'selected'; endif; ?>><?php echo e($d->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['depot_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div>
            <label class="text-xs font-semibold <?php echo e($muted); ?>">Product</label>
            <select name="product_id" class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
              <option value="">— Select product —</option>
              <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($p->id); ?>" <?php if(old('product_id') == $p->id): echo 'selected'; endif; ?>><?php echo e($p->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div class="sm:col-span-2">
            <label class="text-xs font-semibold <?php echo e($muted); ?>">Client name</label>
            <input name="client_name" value="<?php echo e(old('client_name')); ?>"
                   class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['client_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   placeholder="e.g. Katanga Mining" />
            <?php $__errorArgs = ['client_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div>
            <label class="text-xs font-semibold <?php echo e($muted); ?>">Sale date</label>
            <input type="date" name="sale_date" value="<?php echo e(old('sale_date')); ?>"
                   class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['sale_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" />
            <?php $__errorArgs = ['sale_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div>
            <label class="text-xs font-semibold <?php echo e($muted); ?>">Currency</label>
            <input name="currency" value="<?php echo e(old('currency', 'USD')); ?>"
                   class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" />
            <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div>
            <label class="text-xs font-semibold <?php echo e($muted); ?>">Quantity</label>
            <input name="qty" inputmode="decimal" value="<?php echo e(old('qty')); ?>"
                   class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['qty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   placeholder="e.g. 20000" />
            <?php $__errorArgs = ['qty'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          <div>
            <label class="text-xs font-semibold <?php echo e($muted); ?>">Unit price</label>
            <input name="unit_price" inputmode="decimal" value="<?php echo e(old('unit_price')); ?>"
                   class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['unit_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                   placeholder="e.g. 1.35" />
            <?php $__errorArgs = ['unit_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          </div>

          
          <div class="sm:col-span-2 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> p-3">
            <div class="text-xs font-semibold <?php echo e($fg); ?>">Delivery</div>
            <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Choose one mode.</div>

            <?php $oldMode = old('delivery_mode', 'ex_depot'); ?>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <label class="cursor-pointer rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 text-xs <?php echo e($fg); ?>

                            transition peer-checked:border-emerald-500/50 peer-checked:bg-emerald-500/10"
                     id="modeExLabel">
                <input type="radio" name="delivery_mode" value="ex_depot" class="sr-only js-delivery" <?php echo e($oldMode === 'ex_depot' ? 'checked' : ''); ?>>
                <div class="font-semibold">Ex-depot</div>
                <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Client collects. No transport capture.</div>
              </label>

              <label class="cursor-pointer rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 text-xs <?php echo e($fg); ?>

                            transition"
                     id="modeDelLabel">
                <input type="radio" name="delivery_mode" value="delivered" class="sr-only js-delivery" <?php echo e($oldMode === 'delivered' ? 'checked' : ''); ?>>
                <div class="font-semibold">Delivered</div>
                <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Capture transporter + truck + trailer.</div>
              </label>
            </div>

            <?php $__errorArgs = ['delivery_mode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

            <div id="deliveryFields" class="mt-3 hidden grid gap-3 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label class="text-xs font-semibold <?php echo e($muted); ?>">Transporter</label>
                <select name="transporter_id" class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['transporter_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        style="background: var(--tw-surface);">
                  <option value="">—</option>
                  <?php $__currentLoopData = $transporters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($t->id); ?>" <?php if(old('transporter_id') == $t->id): echo 'selected'; endif; ?>><?php echo e($t->name); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['transporter_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>

              <div>
                <label class="text-xs font-semibold <?php echo e($muted); ?>">Truck no</label>
                <input name="truck_no" value="<?php echo e(old('truck_no')); ?>"
                       class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['truck_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       style="background: var(--tw-surface);" />
                <?php $__errorArgs = ['truck_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>

              <div>
                <label class="text-xs font-semibold <?php echo e($muted); ?>">Trailer no</label>
                <input name="trailer_no" value="<?php echo e(old('trailer_no')); ?>"
                       class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['trailer_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       style="background: var(--tw-surface);" />
                <?php $__errorArgs = ['trailer_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold <?php echo e($muted); ?>">Waybill</label>
                <input name="waybill_no" value="<?php echo e(old('waybill_no')); ?>"
                       class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['waybill_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       style="background: var(--tw-surface);" />
                <?php $__errorArgs = ['waybill_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>

              <div class="sm:col-span-2">
                <label class="text-xs font-semibold <?php echo e($muted); ?>">Delivery notes</label>
                <textarea name="delivery_notes" rows="2"
                          class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['delivery_notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                          style="background: var(--tw-surface);"><?php echo e(old('delivery_notes')); ?></textarea>
                <?php $__errorArgs = ['delivery_notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="<?php echo e($errText); ?>"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2">
          <button type="button" id="cancelNewSale"
            class="h-10 px-4 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> text-sm font-semibold <?php echo e($fg); ?>

                   hover:bg-[color:var(--tw-surface)] transition">
            Cancel
          </button>

          <button type="submit"
            class="h-10 px-4 rounded-xl border border-emerald-500/30 bg-emerald-600 text-sm font-semibold text-white hover:bg-emerald-500/20 transition">
            Save draft
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  const modal = document.getElementById('newSaleModal');
  const openBtn = document.getElementById('btnNewSale');
  const closeBtn = document.getElementById('closeNewSale');
  const cancelBtn = document.getElementById('cancelNewSale');

  const modeExLabel = document.getElementById('modeExLabel');
  const modeDelLabel = document.getElementById('modeDelLabel');

  const open = () => modal && modal.classList.remove('hidden');
  const close = () => modal && modal.classList.add('hidden');

  on(openBtn, 'click', open);
  on(closeBtn, 'click', close);
  on(cancelBtn, 'click', close);
  on(modal, 'click', (e) => { if (e.target === modal) close(); });

  // delivery toggle + selected visuals
  const fields = document.getElementById('deliveryFields');

  const paintModes = () => {
    const v = document.querySelector('input[name="delivery_mode"]:checked')?.value || 'ex_depot';

    // reset
    [modeExLabel, modeDelLabel].forEach(l => {
      l?.classList.remove('border-emerald-500/50', 'bg-emerald-500/10', 'ring-2', 'ring-emerald-500/20');
    });

    if (v === 'delivered') {
      modeDelLabel?.classList.add('border-emerald-500/50','bg-emerald-500/10','ring-2','ring-emerald-500/20');
      fields?.classList.remove('hidden');
    } else {
      modeExLabel?.classList.add('border-emerald-500/50','bg-emerald-500/10','ring-2','ring-emerald-500/20');
      fields?.classList.add('hidden');
    }
  };

  document.querySelectorAll('.js-delivery').forEach(r => on(r, 'change', paintModes));
  paintModes();

  // Auto-open modal if validation errors exist (server-side)
  const hasErrors = <?php echo e($errors->any() ? 'true' : 'false'); ?>;
  if (hasErrors) open();

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
})();
</script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/sales/index.blade.php ENDPATH**/ ?>