<?php
  $title = 'Roles & permissions';
  $subtitle = 'Define what each role can see and do across depots, trips, sales and finance.';
?>



<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('status')): ?>
  <div class="mb-4 rounded-lg bg-emerald-900/40 border border-emerald-500/60 px-3 py-2 text-xs text-emerald-100">
    <?php echo e(session('status')); ?>

  </div>
<?php endif; ?>
  <div class="grid md:grid-cols-3 gap-6">
    
    <div>
      <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
        <h2 class="text-sm font-semibold mb-3">Roles</h2>
        <ul class="space-y-1 text-xs">
          <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
              <a href="<?php echo e(route('admin.roles.index', ['role' => $role->slug])); ?>"
                 class="flex items-center justify-between px-3 py-2 rounded-xl
                   <?php echo e($currentRole && $currentRole->id === $role->id
                        ? 'bg-slate-800 text-slate-50'
                        : 'bg-slate-950/40 text-slate-300 hover:bg-slate-900'); ?>">
                <div>
                  <div class="font-semibold text-[13px]"><?php echo e($role->name); ?></div>
                  <div class="text-[10px] text-slate-500"><?php echo e($role->description); ?></div>
                </div>
                <?php if($role->is_system): ?>
                  <span class="text-[9px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">system</span>
                <?php endif; ?>
              </a>
            </li>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
      </div>
    </div>

    
    <div class="md:col-span-2">
      <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4 space-y-4">
        <?php if(!$currentRole): ?>
          <p class="text-xs text-slate-400">No role selected.</p>
        <?php else: ?>
          <div class="flex items-center justify-between mb-1">
            <div>
              <h2 class="text-sm font-semibold"><?php echo e($currentRole->name); ?></h2>
              <p class="text-[11px] text-slate-400">
                Toggle what <span class="font-semibold text-slate-100"><?php echo e($currentRole->name); ?></span> can do in Twins.
              </p>
            </div>
          </div>

          <?php if($currentRole->slug === 'owner'): ?>
            <div class="rounded-xl border border-emerald-500/50 bg-emerald-950/40 px-3 py-2 text-[11px] text-emerald-100 mb-3">
              Owner always has full access to all modules. Permissions below are informational only.
            </div>
          <?php endif; ?>

          <form method="post" action="<?php echo e(route('admin.roles.permissions.sync', $currentRole)); ?>" class="space-y-3">
            <?php echo csrf_field(); ?>
            
            <?php
              $byModule = $permissions->groupBy(function($p) {
                  return $p->module ?: 'other';
              });
            ?>

            <?php $__currentLoopData = $byModule; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $perms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                <div class="flex items-center justify-between mb-1">
                  <div class="text-[11px] uppercase tracking-wide text-slate-400">
                    <?php echo e($module === 'other' ? 'System' : ucfirst($module)); ?>

                  </div>
                </div>
                <div class="flex flex-wrap gap-2 mt-1">
                  <?php $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="inline-flex items-center gap-1 text-[11px] text-slate-200">
                      <input
                        type="checkbox"
                        name="permissions[]"
                        value="<?php echo e($perm->id); ?>"
                        class="h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500/60"
                        <?php if(in_array($perm->id, $assignedIds)): echo 'checked'; endif; ?>
                        <?php if($currentRole->slug === 'owner'): echo 'disabled'; endif; ?>
                      >
                      <span><?php echo e($perm->name); ?></span>
                    </label>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if($currentRole->slug !== 'owner'): ?>
              <div class="flex justify-end pt-1">
                <button class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-950">
                  Save permissions
                </button>
              </div>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/admin/roles/index.blade.php ENDPATH**/ ?>