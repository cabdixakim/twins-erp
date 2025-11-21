<?php
  $title = 'Edit user';
  $subtitle = 'Update user details, role, and status.';
?>



<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startSection('content'); ?>
  <div class="max-w-lg">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
      <form method="post" action="<?php echo e(route('admin.users.update', $user)); ?>" class="space-y-3">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PATCH'); ?>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Name</label>
          <input name="name" value="<?php echo e(old('name', $user->name)); ?>"
                 class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500" required>
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Email</label>
          <input name="email" type="email" value="<?php echo e(old('email', $user->email)); ?>"
                 class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500" required>
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Password</label>
          <input name="password" type="password"
                 class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500"
                 placeholder="Leave blank to keep current">
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Role</label>
          <select name="role_id"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($role->id); ?>" <?php if($user->role_id === $role->id): echo 'selected'; endif; ?>>
                <?php echo e($role->name); ?>

              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Status</label>
          <select name="status"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
            <option value="active" <?php if($user->status === 'active'): echo 'selected'; endif; ?>>Active</option>
            <option value="inactive" <?php if($user->status === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
          </select>
        </div>

        <div class="flex items-center justify-between pt-3">
          <a href="<?php echo e(route('admin.users.index')); ?>" class="text-[11px] text-slate-400 hover:text-slate-200">
            ← Back to users
          </a>
          <button class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-950">
            Save changes
          </button>
        </div>
      </form>
    </div>
  </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/admin/users/edit.blade.php ENDPATH**/ ?>