<!doctype html>
<html lang="en" class="h-full">
<head>

<script>
  (function () {
    const stored = localStorage.getItem('tw-theme'); // 'dark' | 'light' | null
    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')?.matches;
    const theme = stored || (prefersDark ? 'dark' : 'light');

    if (theme === 'dark') document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
  })();
</script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <?php
        $appName = config('app.name', 'Twins');
    ?>

    <title><?php echo e($appName); ?> - <?php echo $__env->yieldContent('title','Dashboard'); ?></title>

    <!-- <script src="https://cdn.tailwindcss.com"></script> -->

    <style>
        .sidebar { transition: all 0.25s ease-in-out; }

        /*
         |==========================================================
         | Twins Tooltip (quiet + tiny + NOT cursor-follow)
         |==========================================================
         | Use: data-tip="Text" + class .tw-tip-b or .tw-tip-r
         | NOTE: We do NOT auto-migrate [title] anymore.
         */
        #twinsTooltip {
            position: fixed;
            z-index: 999999;
            pointer-events: none;
            opacity: 0;
            transform: translateY(-4px) scale(.98);
            transition: opacity .10s ease, transform .10s ease;
        }
        #twinsTooltip.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* ✅ Theme-aware tooltip */
        #twinsTooltip .tip {
            color: rgb(15 23 42);                         /* slate-900 */
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(148,163,184,.45);      /* slate-400 */
            box-shadow: 0 10px 26px rgba(2,6,23,.14);
            padding: 4px 8px;                              /* smaller */
            border-radius: 9px;
            font-size: 9px;                                /* tiny */
            line-height: 1.1;
            white-space: nowrap;
            backdrop-filter: blur(10px);
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        html.dark #twinsTooltip .tip {
            color: rgb(226 232 240);                       /* slate-200 */
            background: rgba(2, 6, 23, 0.85);              /* slate-950 but softer */
            border: 1px solid rgba(51, 65, 85, 0.45);      /* slate-700 subtle */
            box-shadow: 0 10px 26px rgba(0,0,0,.28);
        }

        /*
         |==========================================================
         | Sidebar logout sizing fix (normal + collapsed)
         |==========================================================
         | This forces it to stop being huge/ugly even if markup varies.
         */
        .logout-btn {
            padding: 8px !important;
            border-radius: 14px !important;
        }
        .logout-btn .logout-icon {
            width: 36px !important;
            height: 36px !important;
            border-radius: 12px !important;
        }

        #desktopSidebar.is-collapsed .logout-btn{
            padding: 7px !important;
            border-radius: 14px !important;
            justify-content: center !important;
        }
        #desktopSidebar.is-collapsed .logout-btn .logout-icon{
            width: 34px !important;
            height: 34px !important;
            border-radius: 12px !important;
        }
    </style>

    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>


<body class="bg-white text-slate-900 dark:bg-slate-950 dark:text-slate-100 h-full flex overflow-hidden">
        <!-- <div aria-hidden="true"
        class="fixed inset-0 z-0 pointer-events-none tw-ambient">
    </div> -->

<?php
    $user            = auth()->user();
    $userRole        = $user?->role?->slug;

    // Prefer active company if set, fallback to first company
    $company = null;
    if ($user?->active_company_id) {
        $company = \App\Models\Company::find($user->active_company_id);
    }
    $company = $company ?: \App\Models\Company::query()->orderBy('id')->first();

    $canManageUsers  = in_array($userRole, ['owner','manager'], true);

    $onDashboard     = request()->routeIs('dashboard');
    $onDepotStock    = request()->routeIs('depot-stock.*');
    $onPurchases     = request()->routeIs('purchases.*');
    $onSettingsRoute = request()->routeIs('settings.*') || request()->is('admin/*');
?>

<?php echo $__env->make('layouts.partials.sidebar-desktop', compact(
    'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onPurchases','onSettingsRoute'
), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->make('layouts.partials.sidebar-mobile', compact(
    'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onPurchases','onSettingsRoute'
), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="flex-1 min-w-0 flex flex-col">
    <?php echo $__env->make('layouts.partials.topbar', compact(
        'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onPurchases','onSettingsRoute'
    ), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <main class="flex-1 overflow-y-auto p-6 md:p-8 bg-transparent">
        <?php echo $__env->yieldContent('content'); ?>
    </main>
</div>


<div id="twinsTooltip">
    <div class="tip" id="twinsTooltipText"></div>
</div>

<?php echo $__env->make('layouts.partials.layout-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/app.blade.php ENDPATH**/ ?>