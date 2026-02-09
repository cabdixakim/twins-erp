<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Twins - <?php echo $__env->yieldContent('title', config('twins.brand.name')); ?></title>

    
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->


    
    <script>
      (function () {
        try {
          const saved = localStorage.getItem('tw-theme');
          const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
          const useDark = saved ? (saved === 'dark') : prefersDark;
          document.documentElement.classList.toggle('dark', useDark);
        } catch (e) {}
      })();
    </script>

    
    <style>
      :root{
        --tw-bg:#f8fafc; --tw-fg:#0f172a; --tw-muted:#475569;
        --tw-surface:#ffffff; --tw-surface-2:#f1f5f9; --tw-border:#e2e8f0;
        --tw-shadow:0 20px 50px rgba(2,6,23,.08);
      }
      html.dark{
        --tw-bg:#060b16; --tw-fg:#e5e7eb; --tw-muted:#94a3b8;
        --tw-surface:#0b1220; --tw-surface-2:#0f1a2b; --tw-border:rgba(148,163,184,.16);
        --tw-shadow:0 24px 70px rgba(0,0,0,.45);
      }

      /* Smooth “lights on/off” feel */
      html.theme-anim, html.theme-anim *{
        transition: background-color .25s ease, color .25s ease, border-color .25s ease, box-shadow .25s ease;
      }
    </style>

              <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

</head>

<body class="min-h-screen text-[color:var(--tw-fg)] bg-(--tw-bg)">
    
    <div aria-hidden="true"
        class="fixed inset-0 z-0 pointer-events-none tw-ambient">
    </div>

    
    <main class="w-full">
        <div class="mx-auto w-full max-w-[980px] px-4 sm:px-6 pt-8 sm:pt-10 pb-12">

            
            <div class="mb-6 flex items-center justify-between">
                <div class="min-w-0">
                    <div class="text-[12px] uppercase tracking-[0.18em] text-[color:var(--tw-muted)]">
                        Twins ERP
                    </div>
                    <div class="text-[18px] font-semibold tracking-tight">
                        <?php echo $__env->yieldContent('title', 'Twins ERP'); ?>
                    </div>
                </div>

                <button type="button"
                        id="twStandaloneTheme"
                        class="h-9 w-9 grid place-items-center rounded-2xl
                               border border-[color:var(--tw-border)]
                               bg-[color:var(--tw-surface)]
                               shadow-[0_10px_30px_rgba(2,6,23,.08)]
                               text-[color:var(--tw-fg)]
                               hover:bg-[color:var(--tw-surface-2)]
                               transition"
                        aria-label="Toggle theme">
                    <svg data-icon="moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
                    </svg>
                    <svg data-icon="sun" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
                    </svg>
                </button>
            </div>

            
            <div class="rounded-3xl border border-[color:var(--tw-border)]
                        bg-[color:var(--tw-surface)]
                        shadow-[var(--tw-shadow)]
                        p-5 sm:p-7">
                <?php echo $__env->yieldContent('content'); ?>
            </div>

            <div class="mt-6 text-[11px] text-[color:var(--tw-muted)]">
                © <?php echo e(date('Y')); ?> <?php echo e(config('twins.brand.name')); ?>. All rights reserved.
            </div>
        </div>
    </main>

    <script>
      (function () {
        const btn = document.getElementById('twStandaloneTheme');
        if (!btn) return;

        const moon = btn.querySelector('[data-icon="moon"]');
        const sun  = btn.querySelector('[data-icon="sun"]');

        function sync() {
          const isDark = document.documentElement.classList.contains('dark');
          moon && moon.classList.toggle('hidden', isDark);
          sun  && sun.classList.toggle('hidden', !isDark);
        }
        sync();

        btn.addEventListener('click', () => {
          const root = document.documentElement;
          const isDark = root.classList.toggle('dark');
          localStorage.setItem('tw-theme', isDark ? 'dark' : 'light');

          root.classList.add('theme-anim');
          setTimeout(() => root.classList.remove('theme-anim'), 500);

          sync();
        });
      })();
    </script>
</body>
</html><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/layouts/standalone.blade.php ENDPATH**/ ?>