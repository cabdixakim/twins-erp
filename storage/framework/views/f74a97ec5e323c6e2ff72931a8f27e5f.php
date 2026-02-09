<?php
    /** @var \App\Models\User|null $u */
    $u = auth()->user();

    $companies = $companies ?? collect();
    $activeId  = (int) ($activeId ?? ($u?->active_company_id ?? 0));

    $isOwner  = (bool) ($isOwner ?? false);

    $companyCount = (int) ($companyCount ?? $companies->count());
    $appCount     = (int) ($appCount ?? 0);

    $maxPerUser = (int) ($maxPerUser ?? 1); // 0 = unlimited
    $maxInApp   = (int) ($maxInApp ?? 0);   // 0 = unlimited

    $underUserCap = (bool) ($underUserCap ?? true);
    $underAppCap  = (bool) ($underAppCap ?? true);

    $canCreateCompany = (bool) ($canCreateCompany ?? false);

    $atUserCap = ($maxPerUser !== 0) ? ($companyCount >= $maxPerUser) : false;
    $atAppCap  = ($maxInApp !== 0)   ? ($appCount >= $maxInApp)       : false;

    $title = 'Switch company';

    // Token-ish helpers (theme aware via CSS vars)
    $card   = "tw-surface border border-[color:var(--tw-border)]";
    $muted  = "text-[color:var(--tw-muted)]";
    $fg     = "text-[color:var(--tw-fg)]";
    $btn    = "bg-[color:var(--tw-btn)] border border-[color:var(--tw-border)] hover:bg-[color:var(--tw-btn-hover)]";
?>



<?php $__env->startSection('title', $title); ?>

<?php $__env->startSection('content'); ?>
<div class="w-full">

    
    <div class="mb-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-[16px] sm:text-[18px] font-semibold tracking-tight <?php echo e($fg); ?>">
                    Switch company
                </h1>
                <p class="mt-1 text-[12px] <?php echo e($muted); ?>">
                    Choose a workspace to continue.
                </p>
            </div>

            <?php if($isOwner): ?>
                <div class="flex items-center gap-2 shrink-0">
                    
                    <div class="text-[11px] rounded-2xl px-2.5 py-1 <?php echo e($card); ?>">
                        <span class="font-semibold <?php echo e($fg); ?>"><?php echo e($companyCount); ?></span>
                        <span class="<?php echo e($muted); ?>">/</span>
                        <span class="font-semibold <?php echo e($fg); ?>"><?php echo e($maxPerUser === 0 ? '∞' : $maxPerUser); ?></span>
                        <span class="ml-1 <?php echo e($muted); ?>">companies</span>
                    </div>

                    <button type="button"
                            id="btnOpenCreateCompany"
                            class="h-9 px-3 rounded-2xl text-[12px] font-semibold transition
                                   <?php echo e($btn); ?>

                                   <?php echo e($canCreateCompany ? '' : 'opacity-50 cursor-not-allowed'); ?>"
                            <?php echo e($canCreateCompany ? '' : 'disabled'); ?>>
                        New
                    </button>
                </div>
            <?php endif; ?>
        </div>

        
        <?php if($isOwner && !$underUserCap): ?>
            <div class="mt-3 text-[12px] rounded-2xl px-3 py-2
                        bg-amber-500/10 border border-amber-500/20 text-amber-200/90">
                Limit reached. Your plan allows a maximum of
                <span class="font-semibold"><?php echo e($maxPerUser === 0 ? 'unlimited' : $maxPerUser); ?></span>
                companies.
            </div>
        <?php elseif($isOwner && !$underAppCap): ?>
            <div class="mt-3 text-[12px] rounded-2xl px-3 py-2
                        bg-amber-500/10 border border-amber-500/20 text-amber-200/90">
                App limit reached. This system allows a maximum of
                <span class="font-semibold"><?php echo e($maxInApp); ?></span>
                companies in total.
            </div>
        <?php endif; ?>

        <?php if($isOwner && $maxInApp !== 0): ?>
            <div class="mt-2 text-[11px] <?php echo e($muted); ?>">
                App capacity:
                <span class="font-semibold <?php echo e($fg); ?>"><?php echo e($appCount); ?></span>/<span class="font-semibold <?php echo e($fg); ?>"><?php echo e($maxInApp); ?></span>
                <?php if($atAppCap): ?>
                    <span class="ml-2 text-amber-200">• app limit reached</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="mb-3">
        <div class="relative max-w-[520px]">
            <div class="absolute inset-y-0 left-3 grid place-items-center <?php echo e($muted); ?>">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 20l-3.5-3.5"/>
                </svg>
            </div>

            <input id="twCompanySearch"
                   class="w-full h-10 pl-9 pr-3 rounded-2xl text-[13px]
                          bg-[color:var(--tw-bg)] border border-[color:var(--tw-border)]
                          text-[color:var(--tw-fg)] placeholder:text-[color:var(--tw-muted)]
                          focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                   placeholder="Search companies…"
                   autocomplete="off">
        </div>
    </div>

    
    <div class="rounded-2xl overflow-hidden <?php echo e($card); ?>">
        <div class="px-3 py-2 border-b border-[color:var(--tw-border)] flex items-center justify-between">
            <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">Companies</div>
            <div class="text-[11px] <?php echo e($muted); ?>"><?php echo e($companyCount); ?> total</div>
        </div>

        <div id="twCompanyList" class="divide-y divide-[color:var(--tw-border)]">
            <?php $__empty_1 = true; $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $isActive = ((int) $c->id === $activeId); ?>

                <div class="px-3 py-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 flex items-center gap-3">
                            <span class="h-2 w-2 rounded-full shrink-0
                                         <?php echo e($isActive ? 'bg-emerald-400' : 'bg-[color:var(--tw-border)]'); ?>"></span>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="tw-company-name text-[13px] font-semibold truncate <?php echo e($fg); ?>">
                                        <?php echo e($c->name); ?>

                                    </div>

                                        <?php if($isActive): ?>
                                            <span
                                                class="text-[11px] px-2 py-0.5 rounded-xl border"
                                                style="
                                                    background: var(--tw-accent-soft);
                                                    border-color: var(--tw-accent-soft-border, var(--tw-border));
                                                    color: var(--tw-accent);
                                                "
                                            >
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[11px] <?php echo e($muted); ?>">Switch</span>
                                        <?php endif; ?>
                                </div>

                                <div class="text-[11px] <?php echo e($muted); ?> truncate">
                                    Updated recently • Settings, stock, users
                                </div>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <?php if($isActive): ?>
                                <a href="<?php echo e(route('dashboard')); ?>"
                                   class="h-9 inline-flex items-center px-3 rounded-2xl text-[12px] font-semibold transition <?php echo e($btn); ?>">
                                    Open
                                </a>
                            <?php else: ?>
                                <a href="<?php echo e(route('companies.switch', $c)); ?>"
                                   class="h-9 inline-flex items-center px-3 rounded-2xl text-[12px] font-semibold transition <?php echo e($btn); ?>">
                                    Switch
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="p-4">
                    <div class="text-[13px] font-semibold <?php echo e($fg); ?>">No companies</div>
                    <div class="text-[12px] <?php echo e($muted); ?> mt-1">
                        If this is a fresh system, run the initial setup wizard.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php if($isOwner): ?>
 <div id="twCreateCompanyOverlay"
         class="hidden fixed inset-0 z-[80] bg-black/55 backdrop-blur-sm"></div>

    
    <div id="twCreateCompanyModal"
         class="hidden fixed z-[90] left-1/2 top-[14%] -translate-x-1/2
                w-[92vw] max-w-[520px] rounded-2xl overflow-hidden
                ring-1 shadow-[0_30px_90px_rgba(0,0,0,.55)]
                isolate"
         style="
            background: var(--tw-surface);
            color: var(--tw-fg);
            border-color: var(--tw-border);
         ">

        
        <div class="px-4 py-3 border-b border-[color:var(--tw-border)] flex items-center justify-between">
            <div>
                <div class="text-[13px] font-semibold <?php echo e($fg); ?>">Create company</div>
                <div class="text-[11px] <?php echo e($muted); ?>">Owner only</div>
            </div>

            <button type="button"
                    id="btnCloseCreateCompany"
                    class="h-9 w-9 grid place-items-center rounded-2xl transition <?php echo e($btn); ?>"
                    aria-label="Close">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>
        </div>

        
        <div class="overflow-auto">
            <form method="post" action="<?php echo e(route('companies.store')); ?>" class="p-4">
                <?php echo csrf_field(); ?>

                <div class="space-y-3">
                    <div class="rounded-2xl p-3 bg-[color:var(--tw-surface-2)] border border-[color:var(--tw-border)]">
                        <div class="text-[12px] font-semibold <?php echo e($fg); ?>">New workspace</div>
                        <div class="text-[11px] <?php echo e($muted); ?> mt-0.5">
                            Keep names short & recognisable (e.g. “Twins Lubumbashi”).
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] <?php echo e($muted); ?> mb-1">Company name</label>
                        <input name="name" required
                               class="w-full h-10 px-3 rounded-2xl text-[13px]
                                      bg-[color:var(--tw-bg)] border border-[color:var(--tw-border)]
                                      text-[color:var(--tw-fg)] placeholder:text-[color:var(--tw-muted)]
                                      focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                               placeholder="e.g. Twins Lubumbashi">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] <?php echo e($muted); ?> mb-1">Country</label>
                            <input name="country"
                                   class="w-full h-10 px-3 rounded-2xl text-[13px]
                                          bg-[color:var(--tw-bg)] border border-[color:var(--tw-border)]
                                          text-[color:var(--tw-fg)] placeholder:text-[color:var(--tw-muted)]
                                          focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                                   placeholder="e.g. DRC">
                        </div>

                        <div>
                            <label class="block text-[11px] <?php echo e($muted); ?> mb-1">Currency</label>
                            <input name="default_currency"
                                   class="w-full h-10 px-3 rounded-2xl text-[13px]
                                          bg-[color:var(--tw-bg)] border border-[color:var(--tw-border)]
                                          text-[color:var(--tw-fg)] placeholder:text-[color:var(--tw-muted)]
                                          focus:outline-none focus:ring-2 focus:ring-[color:var(--tw-accent-soft)]"
                                   placeholder="USD">
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button"
                                id="btnCancelCreateCompany"
                                class="h-9 px-3 rounded-2xl text-[12px] font-semibold transition <?php echo e($btn); ?>">
                            Cancel
                        </button>

                        <button type="submit"
                                class="h-9 px-3 rounded-2xl text-[12px] font-semibold transition
                                    disabled:opacity-50 disabled:cursor-not-allowed"
                                style="
                                    background: var(--tw-surface);
                                    color: var(--tw-accent-fg);
                                    border: 1px solid var(--tw-border);
                                "
                                onmouseover="
                                    this.style.background='var(--tw-accent-soft)';
                                    this.style.borderColor='var(--tw-accent-border)';
                                "
                                onmouseout="
                                    this.style.background='var(--tw-surface)';
                                    this.style.borderColor='var(--tw-border)';
                                "
                                <?php echo e($canCreateCompany ? '' : 'disabled'); ?>>
                            Create
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
(function(){
    // Search filter
    const input = document.getElementById('twCompanySearch');
    const list  = document.getElementById('twCompanyList');

    if (input && list) {
        input.addEventListener('input', () => {
            const q = (input.value || '').toLowerCase().trim();
            list.querySelectorAll('.tw-company-name').forEach(nameEl => {
                const row = nameEl.closest('.px-3.py-2');
                const txt = (nameEl.textContent || '').toLowerCase();
                row.style.display = (!q || txt.includes(q)) ? '' : 'none';
            });
        });
    }

    // Modal
    const openBtn = document.getElementById('btnOpenCreateCompany');
    const overlay = document.getElementById('twCreateCompanyOverlay');
    const modal   = document.getElementById('twCreateCompanyModal');

    const closeBtn  = document.getElementById('btnCloseCreateCompany');
    const cancelBtn = document.getElementById('btnCancelCreateCompany');

    function open(){
        if (!overlay || !modal) return;
        if (openBtn && openBtn.hasAttribute('disabled')) return;

        overlay.classList.remove('hidden');
        modal.classList.remove('hidden');

        // lock page scroll (prevents “full page stretch” feeling)
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';

        setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
    }

    function close(){
        if (!overlay || !modal) return;

        overlay.classList.add('hidden');
        modal.classList.add('hidden');

        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
    }

    openBtn?.addEventListener('click', open);
    overlay?.addEventListener('click', close);
    closeBtn?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
})();
</script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.standalone', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/companies/switcher.blade.php ENDPATH**/ ?>