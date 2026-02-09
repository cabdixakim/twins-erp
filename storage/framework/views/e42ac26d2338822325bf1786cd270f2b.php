<?php
    $title = 'Roles - permissions';
    $subtitle = 'Define what each role can see and do across depots, trips, sales and finance.';

    /** @var \Illuminate\Support\Collection|\App\Models\Role[] $roles */
    $currentRole = $roles->firstWhere('slug', request('role')) ?? $roles->first();

    $assignedIds = $currentRole
        ? $currentRole->permissions->pluck('id')->all()
        : [];

    // Theme tokens (premium + theme aware)
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $bg       = 'bg-[color:var(--tw-bg)]';

    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';

    $btnGhost   = "inline-flex items-center justify-center cursor-pointer rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition font-semibold";
    $btnPrimary = "inline-flex items-center justify-center cursor-pointer rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold";
    $btnDanger  = "inline-flex items-center justify-center cursor-pointer rounded-xl border border-rose-500/50 bg-rose-600 text-white hover:bg-rose-500 transition font-semibold";

    $label = "block text-[11px] $muted mb-1";
    $input = "w-full rounded-xl border $border $bg px-3 py-2 text-sm $fg placeholder:opacity-70 focus:outline-none focus:ring-2 focus:ring-emerald-500/30";
?>



<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('subtitle', $subtitle); ?>

<?php $__env->startSection('content'); ?>

    
    <?php if(session('status')): ?>
        <div class="mb-4 rounded-2xl border border-emerald-500/35 bg-emerald-600 text-white px-4 py-3 text-[12px] font-semibold">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    
    <div class="grid gap-5 md:grid-cols-[minmax(0,280px)_minmax(0,1fr)] xl:grid-cols-[minmax(0,320px)_minmax(0,1.2fr)]">

        
        <div>
            <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-3 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold <?php echo e($fg); ?> truncate">Roles</h2>
                        <p class="text-[11px] <?php echo e($muted); ?> truncate">
                            Pick a role to see or tweak its access.
                        </p>
                    </div>

                    
                    <button
                        type="button"
                        id="openCreateRoleModal"
                        class="<?php echo e($btnPrimary); ?> h-8 px-3 text-[11px]"
                        title="Create new role"
                    >
                        <span class="text-base leading-none mr-1">+</span>
                        <span class="hidden sm:inline">New</span>
                    </button>
                </div>

                <?php if($roles->isEmpty()): ?>
                    <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 py-2 text-[12px] <?php echo e($muted); ?>">
                        No roles yet. Click <span class="font-semibold <?php echo e($fg); ?>">New</span> to create one.
                    </div>
                <?php else: ?>
                    <ul class="space-y-1.5 text-xs max-h-[60vh] overflow-y-auto pr-1">
                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $isActive = $currentRole && $currentRole->id === $role->id;
                            ?>

                            <li>
                                <a
                                    href="<?php echo e(route('admin.roles.index', ['role' => $role->slug])); ?>"
                                    class="group flex items-center justify-between rounded-2xl px-3 py-2 transition border <?php echo e($border); ?>

                                        <?php echo e($isActive
                                            ? 'bg-emerald-600 text-white shadow-[0_12px_40px_rgba(16,185,129,.22)]'
                                            : $surface2.' '.$fg.' hover:bg-[color:var(--tw-btn-hover)]'); ?>"
                                >
                                    <div class="min-w-0">
                                        <div class="font-semibold text-[13px] truncate">
                                            <?php echo e($role->name); ?>

                                        </div>

                                        <?php if($role->description): ?>
                                            <div class="text-[10px] opacity-80 truncate">
                                                <?php echo e($role->description); ?>

                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex flex-col items-end gap-1 ml-2 shrink-0">
                                        <?php if($role->is_system): ?>
                                            <span class="text-[9px] px-2 py-0.5 rounded-full border <?php echo e($border); ?>

                                                <?php echo e($isActive ? 'bg-white/10 text-white' : $surface.' '.$muted); ?>">
                                                system
                                            </span>
                                        <?php endif; ?>

                                        <?php if($isActive): ?>
                                            <span class="text-[9px] text-white/90">active</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface); ?> p-4 space-y-4">
            <?php if(!$currentRole): ?>
                <div class="rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 py-2 text-[12px] <?php echo e($muted); ?>">
                    No role selected. Create or pick a role on the left to edit its permissions.
                </div>
            <?php else: ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-1">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold flex items-center gap-2 <?php echo e($fg); ?>">
                            <span class="truncate"><?php echo e($currentRole->name); ?></span>

                            <?php if($currentRole->is_system): ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full border <?php echo e($border); ?> <?php echo e($surface2); ?> <?php echo e($fg); ?> shrink-0">
                                    system
                                </span>
                            <?php endif; ?>
                        </h2>

                        <p class="text-[11px] <?php echo e($muted); ?> truncate">
                            What can <span class="font-semibold <?php echo e($fg); ?>"><?php echo e($currentRole->name); ?></span> do in Twins?
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <?php if($currentRole->slug === 'owner'): ?>
                            <span class="inline-flex items-center text-[11px] font-semibold text-white bg-emerald-600 border border-emerald-500/50 px-2 py-1 rounded-xl">
                                Full access
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center text-[11px] <?php echo e($muted); ?> border <?php echo e($border); ?> <?php echo e($surface2); ?> px-2 py-1 rounded-xl">
                                <?php echo e(count($assignedIds)); ?> selected
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if($currentRole->slug === 'owner'): ?>
                    <div class="rounded-2xl border border-emerald-500/35 bg-emerald-600 text-white px-4 py-3 text-[12px] font-semibold">
                        Owner always has full access to all modules. Permissions below are informational only.
                    </div>
                <?php endif; ?>

                <form
                    method="post"
                    action="<?php echo e(route('admin.roles.permissions.sync', $currentRole)); ?>"
                    class="space-y-3"
                >
                    <?php echo csrf_field(); ?>

                    
                    <?php $__currentLoopData = $permissionsByModule; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $perms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-2xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 py-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-[11px] uppercase tracking-wide <?php echo e($muted); ?>">
                                    <?php echo e($module === 'other' ? 'System' : ucfirst($module)); ?>

                                </div>

                                <div class="text-[11px] <?php echo e($muted); ?>">
                                    <?php echo e($perms->count()); ?> perms
                                </div>
                            </div>

                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <?php $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $checked = in_array($perm->id, $assignedIds);
                                        $disabled = ($currentRole->slug === 'owner');
                                    ?>

                                    <label class="group flex items-center gap-2 rounded-xl border <?php echo e($border); ?> <?php echo e($bg); ?>

                                                  px-3 py-2 text-[12px] <?php echo e($fg); ?>

                                                  hover:bg-[color:var(--tw-btn-hover)] transition
                                                  <?php echo e($checked ? 'ring-1 ring-emerald-500/25' : ''); ?>

                                                  <?php echo e($disabled ? 'opacity-70 cursor-not-allowed' : 'cursor-pointer'); ?>">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="<?php echo e($perm->id); ?>"
                                            class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-500 focus:ring-emerald-500/60"
                                            <?php if($checked): echo 'checked'; endif; ?>
                                            <?php if($disabled): echo 'disabled'; endif; ?>
                                        >
                                        <span class="truncate"><?php echo e($perm->name); ?></span>
                                    </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <?php if($currentRole->slug !== 'owner'): ?>
                        <div class="flex justify-end pt-1">
                            <button
                                type="submit"
                                class="<?php echo e($btnPrimary); ?> px-4 py-2 text-[13px]"
                            >
                                Save permissions
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>

    
<div
  id="createRoleModal"
  class="fixed inset-0 z-40 hidden bg-black/60 flex items-end sm:items-center justify-center p-0 sm:p-6"
  aria-hidden="true"
  role="dialog"
  aria-modal="true"
  aria-labelledby="createRoleTitle"
>
  
  <button
    type="button"
    id="createRoleBackdrop"
    class="fixed inset-0 z-40 w-full h-full"
    aria-label="Close modal"
  ></button>

  
  <div
    class="relative z-50 w-full
           sm:max-w-lg
           rounded-t-3xl sm:rounded-2xl
           border <?php echo e($border); ?> <?php echo e($surface); ?>

           shadow-[0_35px_120px_rgba(0,0,0,.55)]
           overflow-hidden flex flex-col
           max-h-[85vh] sm:max-h-[80vh]
           mx-auto"
  >
    
    <div class="sm:hidden pt-3 pb-1 flex justify-center">
      <div class="h-1.5 w-12 rounded-full bg-white/20"></div>
    </div>

    
    <div class="px-5 py-4 border-b <?php echo e($border); ?> flex items-start justify-between gap-3 shrink-0">
      <div class="min-w-0">
        <h2 id="createRoleTitle" class="text-base font-semibold <?php echo e($fg); ?> leading-tight">New role</h2>
        <p class="text-[12px] <?php echo e($muted); ?> mt-0.5">
          Give the role a clear name and optional description.
        </p>
      </div>

      <button
        type="button"
        id="closeCreateRoleModal"
        class="<?php echo e($btnGhost); ?> h-10 w-10 text-lg leading-none shrink-0"
        aria-label="Close"
      >
        ×
      </button>
    </div>

    
    <div class="flex-1 min-h-0 overflow-y-auto" style="-webkit-overflow-scrolling: touch;">
      <form method="post" action="<?php echo e(route('admin.roles.store')); ?>" class="p-5 space-y-4">
        <?php echo csrf_field(); ?>

        <div>
          <label class="<?php echo e($label); ?>">Role name</label>
          <input type="text" name="name" required class="<?php echo e($input); ?>" placeholder="e.g. Depot manager">
        </div>

        <div>
          <label class="<?php echo e($label); ?>">Slug (optional)</label>
          <input type="text" name="slug" class="<?php echo e($input); ?>" placeholder="manager, accountant, transport...">
          <p class="mt-1 text-[11px] <?php echo e($muted); ?>">Leave blank to generate from the name.</p>
        </div>

        <div>
          <label class="<?php echo e($label); ?>">Description (optional)</label>
          <textarea name="description" rows="3" class="<?php echo e($input); ?>" placeholder="What this role is responsible for..."></textarea>
        </div>

        <label class="flex items-center gap-2 rounded-xl border <?php echo e($border); ?> <?php echo e($surface2); ?> px-3 py-2 cursor-pointer">
          <input id="is_system" type="checkbox" name="is_system" value="1"
                 class="h-4 w-4 rounded border-[color:var(--tw-border)] bg-[color:var(--tw-bg)] text-emerald-500 focus:ring-emerald-500/60">
          <span class="text-[13px] <?php echo e($fg); ?>">Mark as system role (protected)</span>
        </label>

        <div class="flex justify-end gap-2 pt-1">
          <button type="button" id="cancelCreateRole" class="<?php echo e($btnGhost); ?> h-10 px-4 text-[13px]">Cancel</button>
          <button type="submit" class="<?php echo e($btnPrimary); ?> h-10 px-5 text-[13px]">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

    <script>
        (function () {
            const modal = document.getElementById('createRoleModal');
            const backdrop = document.getElementById('createRoleBackdrop');

            const openBtn = document.getElementById('openCreateRoleModal');
            const closeBtn = document.getElementById('closeCreateRoleModal');
            const cancelBtn = document.getElementById('cancelCreateRole');

            if (!modal || !openBtn) return;

            function open() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            modal.setAttribute('aria-hidden', 'false');

            document.body.classList.add('overflow-hidden');

            setTimeout(() => modal.querySelector('input[name="name"]')?.focus(), 40);
            }

            function close() {
            // IMPORTANT: remove focus from anything inside before hiding
            if (document.activeElement && modal.contains(document.activeElement)) {
                document.activeElement.blur();
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');

            modal.setAttribute('aria-hidden', 'true');

            document.body.classList.remove('overflow-hidden');
            }

            openBtn.addEventListener('click', open);
            closeBtn?.addEventListener('click', close);
            cancelBtn?.addEventListener('click', close);
            backdrop?.addEventListener('click', close);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
            });
        })();
    </script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/admin/roles/index.blade.php ENDPATH**/ ?>