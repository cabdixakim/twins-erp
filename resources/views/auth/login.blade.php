<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign in • Twins</title>
  @vite(['resources/css/app.css'])
  <script>
    (function(){
      var t=localStorage.getItem('tw-theme');
      var dark=t==='dark'||(t!=='light'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
      if(dark) document.documentElement.classList.add('dark');
    })();
  </script>
</head>
<body class="h-full flex items-center justify-center px-4 relative" style="background:#0f172a">
  {{-- Theme toggle — top-right corner --}}
  <button type="button" id="loginThemeToggle"
          class="fixed top-4 right-4 z-10 h-9 w-9 rounded-xl border border-slate-700 bg-slate-800/80 text-slate-300
                 hover:bg-slate-700 hover:text-slate-100 flex items-center justify-center transition"
          aria-label="Toggle theme">
    {{-- Moon (light mode) --}}
    <svg data-icon="moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
    </svg>
    {{-- Sun (dark mode) --}}
    <svg data-icon="sun" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
    </svg>
  </button>

  <div class="w-full max-w-3xl rounded-3xl border border-slate-800 bg-slate-900/80 shadow-2xl shadow-emerald-500/10 overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-5">
      {{-- Left: brand / copy --}}
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
            Sign in to Twins
          </h1>
          <p class="text-xs leading-relaxed text-slate-400 mb-6">
            Access your fuel stock, depot positions, local &amp; international transport,
            and profitability in one clean workspace.
          </p>

          <ul class="space-y-2 text-xs text-slate-300">
            <li class="flex items-center gap-2">
              <span class="h-1.5 w-4 rounded-full bg-emerald-400"></span>
              Live depot stock &amp; batch tracking
            </li>
            <li class="flex items-center gap-2">
              <span class="h-1.5 w-4 rounded-full bg-cyan-400"></span>
              Local &amp; international freight modules
            </li>
            <li class="flex items-center gap-2">
              <span class="h-1.5 w-4 rounded-full bg-emerald-300/80"></span>
              Margin &amp; shortfall analytics
            </li>
          </ul>
        </div>

        <p class="mt-6 text-[11px] text-slate-500">
          Don’t have an account yet? Ask your Twins owner to add you.
        </p>
      </div>

      {{-- Right: form --}}
      <div class="md:col-span-3 px-5 py-6 md:px-7 md:py-7">
        <div class="md:hidden mb-4">
          <div class="inline-flex items-center gap-2 mb-2">
            <div class="h-8 w-8 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center text-slate-950 font-bold text-xs">
              Tw
            </div>
            <div>
              <div class="text-sm font-semibold text-slate-50">Twins</div>
              <div class="text-[11px] text-slate-400">Fuel &amp; Transport ERP</div>
            </div>
          </div>
          <h1 class="text-lg font-semibold text-slate-50">Sign in</h1>
          <p class="text-xs text-slate-400">Use the email and password your owner gave you.</p>
        </div>

        <form method="post" action="{{ route('login.post') }}" class="space-y-4">
          @csrf

          @if($errors->any())
            <div class="rounded-xl border border-rose-500/40 bg-rose-950/40 px-3 py-2 text-xs text-rose-100">
              {{ $errors->first() }}
            </div>
          @endif

          <div class="space-y-2">
            <label class="block text-xs text-slate-300">Email</label>
            <input
              name="email"
              type="email"
              value="{{ old('email') }}"
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
              placeholder="you@example.com"
              required
            >
          </div>

          <div class="space-y-2">
            <label class="block text-xs text-slate-300">Password</label>
            <input
              name="password"
              type="password"
              class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400 transition"
              placeholder="Your password"
              required
            >
          </div>

          <div class="flex items-center justify-between text-[11px] text-slate-400">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                name="remember"
                class="h-3.5 w-3.5 rounded border-slate-600 bg-slate-950 text-emerald-500 focus:ring-emerald-500/60"
              >
              <span>Keep me signed in on this device</span>
            </label>
          </div>

          <div class="pt-2 space-y-2">
            <button
              class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-950 tracking-wide transition shadow-md shadow-emerald-500/20 active:scale-[0.99]"
            >
              Login
            </button>
            <p class="text-[11px] text-slate-500 text-center">
              Having trouble? Confirm your email &amp; password with your Twins owner.
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
    (function(){
      var THEME_KEY = 'tw-theme';
      var root = document.documentElement;
      var btn  = document.getElementById('loginThemeToggle');

      function isDark() { return root.classList.contains('dark'); }

      function applyIcons() {
        if (!btn) return;
        btn.querySelector('[data-icon="moon"]').classList.toggle('hidden', isDark());
        btn.querySelector('[data-icon="sun"]').classList.toggle('hidden', !isDark());
      }

      applyIcons();

      btn && btn.addEventListener('click', function(){
        var dark = !isDark();
        root.classList.toggle('dark', dark);
        localStorage.setItem(THEME_KEY, dark ? 'dark' : 'light');
        applyIcons();
      });
    })();
  </script>
</body>
</html>