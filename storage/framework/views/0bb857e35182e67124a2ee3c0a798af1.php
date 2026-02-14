


<div id="newSaleModal" class="fixed inset-0 z-50 hidden">
  
  <div class="absolute inset-0 bg-black/60"></div>

  
  <div class="relative h-full w-full p-4 flex items-center justify-center">
    
    <div class="w-full max-w-xl rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> shadow-xl overflow-hidden">
      
      <div class="max-h-[85vh] overflow-y-auto overscroll-contain">
        <div class="p-5 border-b <?php echo e($border); ?> <?php echo e($surface2); ?> sticky top-0 z-10">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div id="saleModalTitle" class="text-base font-semibold <?php echo e($fg); ?>">New sale</div>
              <div id="saleModalSub" class="mt-1 text-xs <?php echo e($muted); ?>">Draft first, then post to issue stock FIFO.</div>
            </div>
            <button type="button" id="closeNewSale"
              class="h-9 w-9 inline-flex items-center justify-center rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?>

                     <?php echo e($fg); ?> hover:bg-[color:var(--tw-surface-2)] transition">✕</button>
          </div>
        </div>

        <form id="saleForm" method="POST" action="<?php echo e(route('sales.store')); ?>" class="p-5" novalidate>
          <?php echo csrf_field(); ?>
          
          <input type="hidden" id="saleFormMethod" name="_method" value="POST" />

          
          <?php if($errors->any()): ?>
            <div class="mb-4 rounded-xl border border-rose-500/80 bg-rose-500/90 p-3 text-sm font-bold text-white">
              <span class="font-bold">Please fix the highlighted fields.</span>
            </div>
          <?php endif; ?>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="text-xs font-semibold <?php echo e($muted); ?>">Depot</label>
              <select id="f_depot_id" name="depot_id" class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['depot_id'];
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
              <select id="f_product_id" name="product_id" class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['product_id'];
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
              <input id="f_client_name" name="client_name" value="<?php echo e(old('client_name')); ?>"
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
              <input id="f_sale_date" type="date" name="sale_date" value="<?php echo e(old('sale_date')); ?>"
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
              <input id="f_currency" name="currency" value="<?php echo e(old('currency', 'USD')); ?>"
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
              <input id="f_qty" name="qty" inputmode="decimal" value="<?php echo e(old('qty')); ?>"
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
              <input id="f_unit_price" name="unit_price" inputmode="decimal" value="<?php echo e(old('unit_price')); ?>"
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
                <label class="cursor-pointer rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 text-xs <?php echo e($fg); ?> transition"
                       id="modeExLabel">
                  <input id="f_delivery_ex" type="radio" name="delivery_mode" value="ex_depot" class="sr-only js-delivery" <?php echo e($oldMode === 'ex_depot' ? 'checked' : ''); ?>>
                  <div class="font-semibold">Ex-depot</div>
                  <div class="mt-1 text-[11px] <?php echo e($muted); ?>">Client collects. No transport capture.</div>
                </label>

                <label class="cursor-pointer rounded-xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 text-xs <?php echo e($fg); ?> transition"
                       id="modeDelLabel">
                  <input id="f_delivery_del" type="radio" name="delivery_mode" value="delivered" class="sr-only js-delivery" <?php echo e($oldMode === 'delivered' ? 'checked' : ''); ?>>
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
                  <select id="f_transporter_id" name="transporter_id"
                          class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['transporter_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
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
                  <input id="f_truck_no" name="truck_no" value="<?php echo e(old('truck_no')); ?>"
                         class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['truck_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" />
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
                  <input id="f_trailer_no" name="trailer_no" value="<?php echo e(old('trailer_no')); ?>"
                         class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['trailer_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" />
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
                  <input id="f_waybill_no" name="waybill_no" value="<?php echo e(old('waybill_no')); ?>"
                         class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['waybill_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" />
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
                  <textarea id="f_delivery_notes" name="delivery_notes" rows="2"
                            class="<?php echo e($fieldBase); ?> <?php $__errorArgs = ['delivery_notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php echo e($fieldErr); ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('delivery_notes')); ?></textarea>
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

            <button id="saleSubmitBtn" type="submit"
              class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600 hover:border-emerald-700 transition">
              Save draft
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>

<script>
window.salesPrefill = <?php echo json_encode($prefill ?? ['open'=>false, 'depot_id'=>0, 'product_id'=>0]) ?>;
</script>

<script>
window.selectedSale = <?php echo json_encode($selected, 15, 512) ?>;
</script>

<script>
(function () {
  const on = (el, ev, fn) => el && el.addEventListener(ev, fn);

  // modal open/close
  const modal = document.getElementById('newSaleModal');
  const openBtn = document.getElementById('btnNewSale');
  const closeBtn = document.getElementById('closeNewSale');
  const cancelBtn = document.getElementById('cancelNewSale');

  const saleForm = document.getElementById('saleForm');
  const saleFormMethod = document.getElementById('saleFormMethod');
  const submitBtn = document.getElementById('saleSubmitBtn');
  const titleEl = document.getElementById('saleModalTitle');
  const subEl = document.getElementById('saleModalSub');

  const overlay = modal?.firstElementChild; // overlay div
  const isOpen = () => modal && !modal.classList.contains('hidden');

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

  // Only overlay click closes
  on(overlay, 'click', close);

  // Escape closes (only if open)
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isOpen()) close();
  });

  // Helpers
  const setVal = (id, v='') => {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = (v ?? '');
  };

  const setCreateMode = () => {
    if (titleEl) titleEl.textContent = 'New sale';
    if (subEl) subEl.textContent = 'Draft first, then post to issue stock FIFO.';
    if (saleForm) saleForm.action = <?php echo json_encode(route('sales.store'), 15, 512) ?>;
    if (saleFormMethod) {
      saleFormMethod.value = 'POST';
      saleFormMethod.disabled = true;
    }
    if (submitBtn) submitBtn.textContent = 'Save draft';
  };

  const setEditMode = (sale) => {
    if (titleEl) titleEl.textContent = 'Edit draft';
    if (subEl) subEl.textContent = 'Update the draft then post when ready.';
    if (saleForm) saleForm.action = <?php echo json_encode(url('/sales'), 15, 512) ?> + '/' + sale.id;

    if (saleFormMethod) {
      saleFormMethod.disabled = false;
      saleFormMethod.value = 'PUT';
    }
    if (submitBtn) submitBtn.textContent = 'Save changes';

    setVal('f_depot_id', sale.depot_id ? String(sale.depot_id) : '');
    setVal('f_product_id', sale.product_id ? String(sale.product_id) : '');
    setVal('f_client_name', sale.client_name || '');
    setVal('f_sale_date', sale.sale_date || '');
    setVal('f_currency', sale.currency || 'USD');
    setVal('f_qty', sale.qty || '');
    setVal('f_unit_price', sale.unit_price || '');

    const mode = (sale.delivery_mode === 'delivered') ? 'delivered' : 'ex_depot';
    const ex = document.getElementById('f_delivery_ex');
    const dl = document.getElementById('f_delivery_del');
    if (ex && dl) {
      ex.checked = (mode === 'ex_depot');
      dl.checked = (mode === 'delivered');
    }

    setVal('f_transporter_id', sale.transporter_id ? String(sale.transporter_id) : '');
    setVal('f_truck_no', sale.truck_no || '');
    setVal('f_trailer_no', sale.trailer_no || '');
    setVal('f_waybill_no', sale.waybill_no || '');
    const notesEl = document.getElementById('f_delivery_notes');
    if (notesEl) notesEl.value = sale.delivery_notes || '';

    paintModes();
  };

  // delivery toggle
  const modeExLabel = document.getElementById('modeExLabel');
  const modeDelLabel = document.getElementById('modeDelLabel');
  const fields = document.getElementById('deliveryFields');

  const paintModes = () => {
    const v = document.querySelector('#newSaleModal input[name="delivery_mode"]:checked')?.value || 'ex_depot';

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

  document.querySelectorAll('#newSaleModal .js-delivery').forEach(r => on(r, 'change', paintModes));
  paintModes();

  // Bind buttons normally (no "arm" hacks)
  on(openBtn, 'click', () => { setCreateMode(); open(); });
  on(closeBtn, 'click', close);
  on(cancelBtn, 'click', close);

  // Wire edit button (exists only when draft selected)
  const editBtn = document.getElementById('btnEditSale');
  on(editBtn, 'click', () => {
    try {
      const payload = JSON.parse(editBtn.getAttribute('data-sale') || '{}');
      if (!payload.id) return;
      setEditMode(payload);
      open();
    } catch (e) {
      console.error('Bad edit payload', e);
    }
  });

  // ---- OPEN FROM DEPOT STOCK (FIXED) ----
  const pre = window.salesPrefill || { open:false, depot_id:0, product_id:0 };

  const openFromPrefill = () => {
    if (!pre.open) return;

    // If validation errors exist, that flow wins
    if (hasErrors) return;

    setCreateMode();

    if (pre.depot_id) setVal('f_depot_id', String(pre.depot_id));
    if (pre.product_id) setVal('f_product_id', String(pre.product_id));

    paintModes();
    open();
  };

  // Validation error flow
  const hasErrors = <?php echo e($errors->any() ? 'true' : 'false'); ?>;

  if (hasErrors) {
    if (window.selectedSale) setEditMode(window.selectedSale);
    open();
    paintModes();
    return;
  }

  // Ensure closed by default
  close();

  // Run prefill after the script is fully wired (Vite/HMR safe)
  const bootPrefill = () => setTimeout(openFromPrefill, 0);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPrefill, { once: true });
  } else {
    bootPrefill();
  }
})();
</script><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/sales/partials/sale-modal.blade.php ENDPATH**/ ?>