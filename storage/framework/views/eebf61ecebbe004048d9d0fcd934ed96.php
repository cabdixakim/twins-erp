<?php
  $u = auth()->user();
  $companies = method_exists($u,'companies')
      ? $u->companies()->orderBy('name')->get()
      : collect();

  $activeId = (int) ($u->active_company_id ?? 0);
  $active = $companies->firstWhere('id', $activeId) ?? $companies->first();
?>

<a href="<?php echo e(route('companies.switcher')); ?>"
   class="group flex items-center gap-2 h-9 px-3 rounded-lg border border-slate-800
          bg-slate-900/50 hover:bg-slate-800 transition min-w-[180px] max-w-[280px]"
   title="Switch company">
    <span class="h-6 w-6 grid place-items-center rounded-md bg-slate-800/60 text-xs font-semibold">
        <?php echo e(strtoupper(mb_substr($active?->name ?? '—', 0, 1))); ?>

    </span>

    <span class="min-w-0 flex-1">
        <span class="block text-sm font-medium truncate">
            <?php echo e($active?->name ?? 'Select company'); ?>

        </span>
        <span class="block text-[11px] text-slate-400 truncate">
            <?php echo e($companies->count()); ?> <?php echo e($companies->count() === 1 ? 'company' : 'companies'); ?>

        </span>
    </span>

    <svg class="w-4 h-4 opacity-70 group-hover:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
    </svg>
</a><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/partials/company-switcher.blade.php ENDPATH**/ ?>