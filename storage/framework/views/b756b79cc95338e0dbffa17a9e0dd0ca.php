

<?php $__env->startSection('title', 'Users'); ?>
<?php $__env->startSection('subtitle', 'Manage who can sign in to Twins and what they can do.'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $authUser      = auth()->user();
    $authRoleName  = $authUser?->role?->name;
    // Treat these role names as "admin-level". Adjust to fit your roles table.
    $isAdmin       = in_array($authRoleName, ['Admin', 'Owner']);
?>

<div class="max-w-6xl mx-auto space-y-6">

    
    <?php if(session('status')): ?>
        <div class="rounded-lg border border-emerald-500/70 bg-emerald-900/40 px-3 py-2 text-xs text-emerald-100">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-200">Team members</h2>
        <?php if($isAdmin): ?>
            <button
                class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-xs font-semibold text-slate-950"
                onclick="openCreateUserModal()">
                + New user
            </button>
        <?php endif; ?>
    </div>

    
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 overflow-hidden hidden md:block">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-900/90 border-b border-slate-800 text-slate-400 uppercase tracking-wide">
                <tr>
                    <th class="px-3 py-2 text-left w-1/4">Name</th>
                    <th class="px-3 py-2 text-left w-1/4">Email</th>
                    <th class="px-3 py-2 text-left w-1/6">Role</th>
                    <th class="px-3 py-2 text-left w-1/12">Status</th>
                    <th class="px-3 py-2 text-right w-1/3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        // Protect the seeded owner account – adjust logic if you mark owner differently
                        $isOwnerAccount = $user->id === 1;
                    ?>
                    <tr class="border-b border-slate-800/60 hover:bg-slate-900/80">
                        <td class="px-3 py-2 text-slate-100"><?php echo e($user->name); ?></td>
                        <td class="px-3 py-2 text-slate-300"><?php echo e($user->email); ?></td>
                        <td class="px-3 py-2 text-slate-300"><?php echo e($user->role?->name ?? '—'); ?></td>
                        <td class="px-3 py-2 text-[11px]">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px]
                                <?php echo e($user->status === 'active'
                                    ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/40'
                                    : 'bg-slate-700/40 text-slate-300 border border-slate-600/50'); ?>">
                                <?php echo e(ucfirst($user->status)); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <?php if($isAdmin): ?>
                                <div class="inline-flex flex-wrap items-center justify-end gap-1">

                                    
                                    <button
                                        class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-100"
                                        onclick="openEditUserModal(<?php echo e(json_encode([
                                                'id'     => $user->id,
                                                'name'   => $user->name,
                                                'email'  => $user->email,
                                                'role_id'=> $user->role_id,
                                                'status' => $user->status,
                                            ])); ?>)">
                                        Edit
                                    </button>

                                    
                                    <button
                                        class="px-2 py-1 rounded-lg bg-sky-500/20 text-sky-200 hover:bg-sky-500/30 text-[11px]"
                                        onclick="openResetPasswordModal(<?php echo e(json_encode([
                                                'id'   => $user->id,
                                                'name' => $user->name,
                                            ])); ?>)">
                                        Reset
                                    </button>

                                    
                                    <?php if(!$isOwnerAccount): ?>
                                        <form method="post" action="<?php echo e(route('admin.users.toggle-status', $user)); ?>" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <button class="px-2 py-1 rounded-lg text-[11px]
                                                <?php echo e($user->status === 'active'
                                                    ? 'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30'
                                                    : 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30'); ?>">
                                                <?php echo e($user->status === 'active' ? 'Deactivate' : 'Activate'); ?>

                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    
                                    <?php if(!$isOwnerAccount): ?>
                                        <button
                                            class="px-2 py-1 rounded-lg bg-rose-500/20 text-rose-200 hover:bg-rose-500/30 text-[11px]"
                                            onclick="openDeleteUserModal(<?php echo e(json_encode([
                                                    'id'   => $user->id,
                                                    'name' => $user->name,
                                                ])); ?>)">
                                            Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-[11px] text-slate-500 italic">No permission</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="px-3 py-4 text-center text-slate-500">
                            No users yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    
    <div class="md:hidden space-y-2">
        <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $isOwnerAccount = $user->id === 1;
            ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-3 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="text-sm text-slate-100 font-medium"><?php echo e($user->name); ?></div>
                        <div class="text-[11px] text-slate-400"><?php echo e($user->email); ?></div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px]
                        <?php echo e($user->status === 'active'
                            ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/40'
                            : 'bg-slate-700/40 text-slate-300 border border-slate-600/50'); ?>">
                        <?php echo e($user->role?->name ?? 'No role'); ?>

                    </span>
                </div>

                <?php if($isAdmin): ?>
                    <div class="flex flex-wrap gap-2 pt-1">
                        
                        <button
                            class="flex-1 min-w-[45%] px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-100"
                            onclick="openEditUserModal(<?php echo e(json_encode([
                                    'id'     => $user->id,
                                    'name'   => $user->name,
                                    'email'  => $user->email,
                                    'role_id'=> $user->role_id,
                                    'status' => $user->status,
                                ])); ?>)">
                            Edit
                        </button>

                        
                        <button
                            class="flex-1 min-w-[45%] px-2 py-1 rounded-lg bg-sky-500/20 text-sky-200 hover:bg-sky-500/30 text-[11px]"
                            onclick="openResetPasswordModal(<?php echo e(json_encode([
                                    'id'   => $user->id,
                                    'name' => $user->name,
                                ])); ?>)">
                            Reset password
                        </button>

                        
                        <?php if(!$isOwnerAccount): ?>
                            <form method="post" action="<?php echo e(route('admin.users.toggle-status', $user)); ?>" class="flex-1 min-w-[45%]">
                                <?php echo csrf_field(); ?>
                                <button class="w-full px-2 py-1 rounded-lg text-[11px]
                                    <?php echo e($user->status === 'active'
                                        ? 'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30'
                                        : 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30'); ?>">
                                    <?php echo e($user->status === 'active' ? 'Deactivate' : 'Activate'); ?>

                                </button>
                            </form>
                        <?php endif; ?>

                        
                        <?php if(!$isOwnerAccount): ?>
                            <button
                                class="flex-1 min-w-[45%] px-2 py-1 rounded-lg bg-rose-500/20 text-rose-200 hover:bg-rose-500/30 text-[11px]"
                                onclick="openDeleteUserModal(<?php echo e(json_encode([
                                        'id'   => $user->id,
                                        'name' => $user->name,
                                    ])); ?>)">
                                Delete
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="pt-1 text-[11px] text-slate-500 italic">
                        You don't have permission to manage users.
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center text-slate-500 text-xs">
                No users yet.
            </div>
        <?php endif; ?>
    </div>
</div>



<div id="createUserModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-40">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-4 mx-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-100">New user</h2>
            <button class="text-slate-400 text-lg" onclick="closeCreateUserModal()">×</button>
        </div>

        <p class="text-[11px] text-slate-400">
            You can type a password or let Twins generate one for you.
            The final password will be shown after you save the user.
        </p>

        <form method="post" action="<?php echo e(route('admin.users.store')); ?>" class="space-y-3" id="createUserForm">
            <?php echo csrf_field(); ?>

            <div>
                <label class="block text-xs text-slate-300 mb-1">Name</label>
                <input name="name"
                       class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100"
                       required>
            </div>

            <div>
                <label class="block text-xs text-slate-300 mb-1">Email</label>
                <input name="email" type="email"
                       class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100"
                       required>
            </div>

            <div>
                <label class="block text-xs text-slate-300 mb-1">Role</label>
                <select name="role_id"
                        class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($role->id); ?>"><?php echo e($role->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs text-slate-300">Password</label>
                    <button type="button"
                            class="text-[11px] text-emerald-400 hover:text-emerald-300"
                            onclick="generateUserPassword()">
                        Generate random
                    </button>
                </div>
                <input id="createUserPassword"
                       name="password"
                       type="text"
                       placeholder="Leave empty to auto-generate"
                       class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
                <p class="mt-1 text-[10px] text-slate-500">
                    If left blank, Twins will generate a strong password.
                </p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                        class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs"
                        onclick="closeCreateUserModal()">
                    Cancel
                </button>
                <button
                    class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-xs font-semibold text-slate-950">
                    Save user
                </button>
            </div>
        </form>
    </div>
</div>


<div id="editUserModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-40">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-4 mx-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-100">Edit user</h2>
            <button class="text-slate-400 text-lg" onclick="closeEditUserModal()">×</button>
        </div>

        <form id="editUserForm" method="post" class="space-y-3">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <div>
                <label class="block text-xs text-slate-300 mb-1">Name</label>
                <input id="editUserName" name="name" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100" required>
            </div>
            <div>
                <label class="block text-xs text-slate-300 mb-1">Email</label>
                <input id="editUserEmail" name="email" type="email" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100" required>
            </div>
            <div>
                <label class="block text-xs text-slate-300 mb-1">Role</label>
                <select id="editUserRole" name="role_id" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
                    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($role->id); ?>"><?php echo e($role->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-300 mb-1">Status</label>
                <select id="editUserStatus" name="status" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs" onclick="closeEditUserModal()">Cancel</button>
                <button class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-xs font-semibold text-slate-950">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>


<div id="resetPasswordModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-40">
    <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-5 space-y-4 mx-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-100">Reset password</h2>
            <button class="text-slate-400 text-lg" onclick="closeResetPasswordModal()">×</button>
        </div>

        <p class="text-[11px] text-slate-400">
            You can type a new password or let Twins generate a secure one.
            The final password will be shown after saving.
        </p>

        <form id="resetPasswordForm" method="post" class="space-y-3">
            <?php echo csrf_field(); ?>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs text-slate-300">New password</label>
                    <button type="button"
                            class="text-[11px] text-emerald-400 hover:text-emerald-300"
                            onclick="generateResetPassword()">
                        Generate random
                    </button>
                </div>
                <input id="resetPasswordInput"
                       name="password"
                       type="text"
                       placeholder="Leave empty to auto-generate"
                       class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs"
                        onclick="closeResetPasswordModal()">
                    Cancel
                </button>
                <button class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-xs font-semibold text-slate-950">
                    Reset password
                </button>
            </div>
        </form>
    </div>
</div>


<div id="deleteUserModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-40">
    <div class="w-full max-w-sm rounded-2xl border border-rose-700 bg-slate-900 p-5 space-y-4 mx-3">
        <h2 class="text-sm font-semibold text-rose-200">Delete user</h2>
        <p class="text-xs text-slate-300">
            Are you sure you want to delete <span id="deleteUserName" class="font-semibold text-slate-100"></span>?
            This cannot be undone.
        </p>
        <form id="deleteUserForm" method="post" class="flex justify-end gap-2">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="button" class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs" onclick="closeDeleteUserModal()">Cancel</button>
            <button class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-xs font-semibold text-slate-50">
                Delete
            </button>
        </form>
    </div>
</div>


<?php if(session('generated_password')): ?>
<div id="generatedPasswordModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-40">
    <div class="w-full max-w-sm rounded-2xl border border-emerald-700 bg-slate-900 p-5 space-y-4 mx-3">
        <h2 class="text-sm font-semibold text-emerald-200">Password generated</h2>
        <p class="text-xs text-slate-300">
            Share this password with the user (<span class="text-slate-100"><?php echo e(session('generated_user_email')); ?></span>)
            and ask them to change it after first login.
        </p>
        <div class="flex items-center gap-2">
            <input id="generatedPasswordValue" readonly
                   class="flex-1 px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-emerald-200"
                   value="<?php echo e(session('generated_password')); ?>">
            <button type="button"
                    class="px-3 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-xs font-semibold text-slate-950"
                    onclick="copyGeneratedPassword()">
                Copy
            </button>
        </div>
        <div class="flex justify-end pt-2">
            <button type="button" class="px-3 py-1.5 rounded-lg bg-slate-800 text-xs" onclick="closeGeneratedPasswordModal()">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    const byId = id => document.getElementById(id);

    // ---------- OPEN/CLOSE HELPERS ----------
    function openCreateUserModal() {
        const m = byId('createUserModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeCreateUserModal() {
        const m = byId('createUserModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    function openEditUserModal(user) {
        const modal = byId('editUserModal');
        const form  = byId('editUserForm');

        form.action = `/admin/users/${user.id}`;

        byId('editUserName').value   = user.name;
        byId('editUserEmail').value  = user.email;
        byId('editUserRole').value   = user.role_id ?? '';
        byId('editUserStatus').value = user.status ?? 'active';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeEditUserModal() {
        const modal = byId('editUserModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openResetPasswordModal(user) {
        const modal = byId('resetPasswordModal');
        const form  = byId('resetPasswordForm');
        const input = byId('resetPasswordInput');

        input.value = '';
        input.placeholder = 'Leave empty to auto-generate';

        form.action = `/admin/users/${user.id}/reset-password`;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeResetPasswordModal() {
        const modal = byId('resetPasswordModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openDeleteUserModal(user) {
        const modal = byId('deleteUserModal');
        const form  = byId('deleteUserForm');
        byId('deleteUserName').innerText = user.name;
        form.action = `/admin/users/${user.id}`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeDeleteUserModal() {
        const modal = byId('deleteUserModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ---------- PASSWORD GENERATORS ----------
    function generateUserPassword() {
        const field = byId('createUserPassword');
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
        let pass = '';
        for (let i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        field.value = pass;
    }

    function generateResetPassword() {
        const field = byId('resetPasswordInput');
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
        let pass = '';
        for (let i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        field.value = pass;
    }

    // ---------- GENERATED PASSWORD MODAL ----------
    function copyGeneratedPassword() {
        const input = byId('generatedPasswordValue');
        if (!input) return;
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard?.writeText(input.value);
    }
    function closeGeneratedPasswordModal() {
        const modal = byId('generatedPasswordModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ---------- CLICK OUTSIDE TO CLOSE (ALL MODALS) ----------
    ['createUserModal', 'editUserModal', 'resetPasswordModal', 'deleteUserModal', 'generatedPasswordModal']
        .forEach(id => {
            const modal = byId(id);
            if (!modal) return;
            modal.addEventListener('click', (e) => {
                // only close if clicking on the dark backdrop, not inside the card
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/admin/users/index.blade.php ENDPATH**/ ?>