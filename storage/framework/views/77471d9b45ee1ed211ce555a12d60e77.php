<!doctype html>
<html lang="en" class="h-full">
<head>
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
        #twinsTooltip .tip {
            /* background: rgba(2, 6, 23, 0.85);           slate-950 but softer */
            color: rgb(226 232 240);                    /* slate-200 */
            border: 1px solid rgba(51, 65, 85, 0.45);   /* slate-700 subtle */
            box-shadow: 0 10px 26px rgba(0,0,0,.28);    /* quieter */
            padding: 4px 8px;                           /* smaller */
            border-radius: 9px;
            font-size: 9px;                            /* tiny */
            line-height: 1.1;
            white-space: nowrap;
            backdrop-filter: blur(10px);
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
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
</head>

<body class="bg-slate-950 text-slate-100 h-full flex overflow-hidden">

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
    $onSettingsRoute = request()->routeIs('settings.*') || request()->is('admin/*');
?>

<?php echo $__env->make('layouts.partials.sidebar-desktop', compact(
    'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onSettingsRoute'
), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php echo $__env->make('layouts.partials.sidebar-mobile', compact(
    'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onSettingsRoute'
), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="flex-1 min-w-0 flex flex-col">
    <?php echo $__env->make('layouts.partials.topbar', compact(
        'user','userRole','company','canManageUsers','onDashboard','onDepotStock','onSettingsRoute'
    ), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="flex-1 overflow-y-auto p-6 md:p-8">
        <?php echo $__env->yieldContent('content'); ?>
    </main>
</div>


<div id="twinsTooltip">
    <div class="tip" id="twinsTooltipText"></div>
</div>

<?php echo $__env->make('layouts.partials.layout-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


</body>
</html><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/app.blade.php ENDPATH**/ ?>