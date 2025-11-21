<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Twins Admin'); ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Smooth sidebar animation */
        .sidebar {
            transition: all 0.25s ease-in-out;
        }
    </style>
</head>

<body class="h-full bg-slate-950 text-slate-200 flex overflow-hidden">

    
    <aside class="sidebar w-64 bg-slate-900 border-r border-slate-800 hidden md:flex flex-col">
        <div class="p-5 border-b border-slate-800">
            <h1 class="text-xl font-bold text-emerald-400">TWINS</h1>
            <p class="text-xs text-slate-500">Fuel & Transport ERP</p>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

            <a href="<?php echo e(route('dashboard')); ?>"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-800 <?php echo e(request()->is('dashboard') ? 'bg-slate-800' : ''); ?>">
               📊 Dashboard
            </a>

            <a href="<?php echo e(route('admin.users.index')); ?>"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-800 <?php echo e(request()->is('admin/users*') ? 'bg-slate-800' : ''); ?>">
               👤 Users
            </a>

            <a href="<?php echo e(route('admin.roles.index')); ?>"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-800 <?php echo e(request()->is('admin/roles*') ? 'bg-slate-800' : ''); ?>">
               🛡️ Roles
            </a>

            
            
        </nav>

        <div class="p-4 border-t border-slate-800">
            <form action="<?php echo e(route('logout')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button class="w-full py-2 text-sm rounded-lg bg-rose-600 hover:bg-rose-500">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    
    <button id="openMenu"
            class="md:hidden fixed top-4 left-4 bg-slate-900 text-slate-200 px-3 py-2 rounded-lg border border-slate-700">
        ☰
    </button>

    
    <aside id="mobileSidebar"
           class="sidebar fixed top-0 left-0 h-full w-64 bg-slate-900 border-r border-slate-800 z-50 transform -translate-x-full md:hidden flex-col">
        <div class="p-5 border-b border-slate-800 flex justify-between items-center">
            <h1 class="text-xl font-bold text-emerald-400">TWINS</h1>
            <button id="closeMenu" class="text-xl">✖</button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

            <a href="<?php echo e(route('dashboard')); ?>"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-800">
               📊 Dashboard
            </a>

            <a href="<?php echo e(route('admin.users.index')); ?>"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-800">
               👤 Users
            </a>

            <a href="<?php echo e(route('admin.roles.index')); ?>"
               class="block px-3 py-2 rounded-lg text-sm hover:bg-slate-800">
               🛡️ Roles
            </a>

        </nav>
    </aside>

    
    <main class="flex-1 overflow-y-auto p-6 md:p-10">

        
        <div class="mb-6">
            <h1 class="text-2xl font-semibold"><?php echo $__env->yieldContent('title'); ?></h1>
            <p class="text-sm text-slate-400"><?php echo $__env->yieldContent('subtitle'); ?></p>
        </div>

        
        <?php echo $__env->yieldContent('content'); ?>

    </main>

    <script>
        const openMenu = document.getElementById('openMenu');
        const closeMenu = document.getElementById('closeMenu');
        const mobileSidebar = document.getElementById('mobileSidebar');

        openMenu?.addEventListener('click', () => {
            mobileSidebar.classList.remove('-translate-x-full');
        });

        closeMenu?.addEventListener('click', () => {
            mobileSidebar.classList.add('-translate-x-full');
        });
    </script>

</body>
</html><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/admin.blade.php ENDPATH**/ ?>