<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Setup Twins</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-slate-950 flex items-center justify-center px-4">
  <div class="w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-900/80 shadow-2xl shadow-emerald-500/10 overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-5">
      
      <div class="hidden md:flex md:col-span-2 flex-col justify-between bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950/90 px-6 py-6 border-r border-slate-800">
        <div>
          <div class="inline-flex items-center gap-2 mb-6">
            <div class="h-9 w-9 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center text-slate-950 font-bold text-sm">
              Tw
            </div>
            <div>
              <div class="text-sm font-semibold text-slate-50 tracking-wide">Twins</div>
              <div class="text-[11px] text-slate-400">Fuel &amp; Transport ERP</div>
            </div>
          </div>

          <h1 class="text-xl font-semibold text-slate-50 mb-2">
            Welcome to Twins
          </h1>
          <p class="text-xs leading-relaxed text-slate-400 mb-6">
            We’ll start by creating your <span class="text-emerald-400 font-medium">company</span> and
            your <span class="text-emerald-400 font-medium">owner account</span>. You can add more users
            and details later inside the app.
          </p>

          <div class="space-y-3 text-xs text-slate-300">
            <div class="flex items-center gap-2">
              <span class="h-5 w-5 rounded-full bg-emerald-500/10 border border-emerald-500/60 flex items-center justify-center text-[10px] text-emerald-300">1</span>
              <span>Company &amp; owner details</span>
            </div>
            <div class="flex items-center gap-2 opacity-60">
              <span class="h-5 w-5 rounded-full border border-slate-700 flex items-center justify-center text-[10px] text-slate-500">2</span>
              <span>Configure depots, clients &amp; transport (inside Twins)</span>
            </div>
          </div>
        </div>

        <p class="mt-6 text-[11px] text-slate-500">
          Step 1 of 1 • Setup takes less than a minute.
        </p>
      </div>

      
      <div class="md:col-span-3 px-5 py-6 md:px-7 md:py-7">
        <form method="post" action="<?php echo e(route('company.store')); ?>" class="space-y-5">
          <?php echo csrf_field(); ?>

          <div class="md:hidden mb-2">
            <h1 class="text-lg font-semibold text-slate-50">Welcome to Twins</h1>
            <p class="text-xs text-slate-400">Create your company and owner account.</p>
          </div>

          <?php if($errors->any()): ?>
            <div class="rounded-xl border border-rose-500/40 bg-rose-950/40 px-3 py-2 text-xs text-rose-100">
              <?php echo e($errors->first()); ?>

            </div>
          <?php endif; ?>

          <div class="space-y-3">
            <p class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">
              Company
            </p>
            <div>
              <label class="block text-xs text-slate-300 mb-1">Company name</label>
              <input
                name="company_name"
                value="<?php echo e(old('company_name')); ?>"
                class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
                placeholder="e.g. Optima Diesel Congo SARL"
                required
              >
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-slate-300 mb-1">Base currency</label>
                <input
                  name="base_currency"
                  value="<?php echo e(old('base_currency','USD')); ?>"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
                  placeholder="USD, EUR, CDF…"
                  required
                >
              </div>
              <div>
                <label class="block text-xs text-slate-300 mb-1">Timezone</label>
                <input
                  name="timezone"
                  value="<?php echo e(old('timezone','Africa/Lubumbashi')); ?>"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
                  placeholder="Africa/Lubumbashi"
                >
              </div>
            </div>
          </div>

          <div class="border-t border-slate-800 pt-4 space-y-3">
            <p class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">
              Owner account
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-slate-300 mb-1">Owner name</label>
                <input
                  name="owner_name"
                  value="<?php echo e(old('owner_name')); ?>"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
                  placeholder="Your full name"
                  required
                >
              </div>
              <div>
                <label class="block text-xs text-slate-300 mb-1">Owner email</label>
                <input
                  name="owner_email"
                  type="email"
                  value="<?php echo e(old('owner_email')); ?>"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
                  placeholder="you@example.com"
                  required
                >
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-slate-300 mb-1">Owner password</label>
                <input
                  name="owner_password"
                  type="password"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
                  placeholder="Choose a strong password"
                  required
                >
              </div>
              <div class="flex items-end">
                <p class="text-[11px] text-slate-500">
                  This will be your <span class="text-slate-300 font-medium">owner login</span>.
                  You can add managers and staff later with limited roles.
                </p>
              </div>
            </div>
          </div>

          <div class="pt-2 space-y-2">
            <button
              class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-950 tracking-wide transition shadow-md shadow-emerald-500/20 active:scale-[0.99]"
            >
              Start Twins
            </button>
            <p class="text-[11px] text-slate-500 text-center">
              By continuing you confirm you’re setting up Twins for your own company.
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html><?php /**PATH C:\xampp\htdocs\twins-erp\resources\views/onboarding/company_create.blade.php ENDPATH**/ ?>