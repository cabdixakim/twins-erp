<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign in • {{ config('app.name') }}</title>
  @vite(['resources/css/app.css'])

  {{-- Apply saved theme before first paint to avoid flash --}}
  <script>
    (function(){
      var t = localStorage.getItem('tw-theme');
      var dark = t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
      if (dark) document.documentElement.classList.add('dark');
    })();
  </script>

  <style>
    /* ---- login page theming — scoped, overrides app.css body rule ---- */
    .login-page {
      background: #f0f2f5;
      transition: background 0.2s;
    }
    html.dark .login-page {
      background: #0f172a;
    }

    /* Card */
    .login-card {
      background: #ffffff;
      border-color: #d1d5db;
      box-shadow: 0 20px 60px rgba(0,0,0,.10);
    }
    html.dark .login-card {
      background: rgba(15,23,42,.85);
      border-color: #1e293b;
      box-shadow: 0 20px 60px rgba(0,0,0,.50), 0 0 0 1px rgba(52,211,153,.06);
    }

    /* Right panel (form side) */
    .login-right {
      background: #ffffff;
    }
    html.dark .login-right {
      background: transparent;
    }

    /* Labels */
    .login-label {
      color: #374151;
    }
    html.dark .login-label {
      color: #cbd5e1;
    }

    /* Sub-text / muted */
    .login-muted {
      color: #6b7280;
    }
    html.dark .login-muted {
      color: #64748b;
    }

    /* Headings on form side */
    .login-heading {
      color: #111827;
    }
    html.dark .login-heading {
      color: #f8fafc;
    }

    /* Inputs */
    .login-input {
      background: #f9fafb;
      border: 1px solid #d1d5db;
      color: #111827;
      border-radius: 0.5rem;
      width: 100%;
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
      outline: none;
      transition: border-color .15s, box-shadow .15s;
    }
    .login-input::placeholder { color: #9ca3af; }
    .login-input:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16,185,129,.15);
    }
    html.dark .login-input {
      background: #020617;
      border-color: #334155;
      color: #f1f5f9;
    }
    html.dark .login-input::placeholder { color: #475569; }
    html.dark .login-input:focus {
      border-color: #34d399;
      box-shadow: 0 0 0 3px rgba(52,211,153,.18);
    }

    /* Divider between left/right on desktop */
    .login-divider {
      border-right: 1px solid #e5e7eb;
    }
    html.dark .login-divider {
      border-right-color: #1e293b;
    }

    /* Remember label */
    .login-remember {
      color: #6b7280;
    }
    html.dark .login-remember {
      color: #64748b;
    }

    /* Theme toggle button */
    .login-theme-btn {
      background: #f3f4f6;
      border: 1px solid #e5e7eb;
      color: #6b7280;
      border-radius: 0.5rem;
      width: 2rem;
      height: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .15s, color .15s;
      cursor: pointer;
      flex-shrink: 0;
    }
    .login-theme-btn:hover {
      background: #e5e7eb;
      color: #374151;
    }
    html.dark .login-theme-btn {
      background: rgba(255,255,255,.06);
      border-color: #334155;
      color: #94a3b8;
    }
    html.dark .login-theme-btn:hover {
      background: rgba(255,255,255,.10);
      color: #e2e8f0;
    }
  </style>
</head>
<body class="login-page h-full flex items-center justify-center px-4">

  <div class="login-card w-full max-w-3xl rounded-3xl border overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-5">

      {{-- Left panel — always dark brand panel --}}
      <div class="hidden md:flex md:col-span-2 login-divider flex-col justify-between
                  px-6 py-6 relative overflow-hidden"
           style="background:linear-gradient(160deg,#0a1628 0%,#0f1e35 60%,#0a2010 100%)">

        {{-- Subtle grid pattern --}}
        <div class="absolute inset-0 pointer-events-none" style="
          background-image:
            linear-gradient(rgba(52,211,153,.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(52,211,153,.04) 1px, transparent 1px);
          background-size: 28px 28px;
        "></div>

        {{-- Glow orb --}}
        <div class="absolute -bottom-16 -left-16 w-56 h-56 rounded-full pointer-events-none"
             style="background:radial-gradient(circle, rgba(16,185,129,.18) 0%, transparent 70%)"></div>

        <div class="relative">
          {{-- Logo mark --}}
          <div class="inline-flex items-center gap-2.5 mb-8">
            <div class="h-9 w-9 rounded-2xl flex items-center justify-center font-bold text-sm"
                 style="background:linear-gradient(135deg,#34d399,#06b6d4);color:#0a1628;letter-spacing:-.5px">
              {{ mb_strtoupper(mb_substr(config('app.name'), 0, 2)) }}
            </div>
            <div>
              <div class="text-sm font-bold text-slate-50 tracking-wide">{{ config('app.name') }}</div>
              <div class="text-[10px] font-medium tracking-widest uppercase"
                   style="color:rgba(52,211,153,.7)">Fuel &amp; Transport ERP</div>
            </div>
          </div>

          {{-- Main headline --}}
          <div class="mb-1 text-[11px] font-semibold tracking-widest uppercase"
               style="color:rgba(52,211,153,.6)">Fuel Operations Platform</div>
          <h1 class="text-2xl font-bold text-slate-50 leading-tight mb-3"
              style="letter-spacing:-.3px">
            Every litre.<br>Accounted for.
          </h1>
          <p class="text-xs leading-relaxed mb-7" style="color:#64748b">
            Depot stock · import pipelines · transporter ledgers ·
            supplier payables — all in one workspace.
          </p>

          <ul class="space-y-2.5">
            <li class="flex items-center gap-2.5 text-xs" style="color:#94a3b8">
              <span class="flex-shrink-0 h-5 w-5 rounded-lg flex items-center justify-center"
                    style="background:rgba(52,211,153,.15)">
                <svg class="w-3 h-3" style="color:#34d399" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </span>
              Live depot stock &amp; batch tracking
            </li>
            <li class="flex items-center gap-2.5 text-xs" style="color:#94a3b8">
              <span class="flex-shrink-0 h-5 w-5 rounded-lg flex items-center justify-center"
                    style="background:rgba(6,182,212,.15)">
                <svg class="w-3 h-3" style="color:#22d3ee" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </span>
              Import pipeline &amp; truck-level logistics
            </li>
            <li class="flex items-center gap-2.5 text-xs" style="color:#94a3b8">
              <span class="flex-shrink-0 h-5 w-5 rounded-lg flex items-center justify-center"
                    style="background:rgba(52,211,153,.15)">
                <svg class="w-3 h-3" style="color:#34d399" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </span>
              Margin, shortfall &amp; payables analytics
            </li>
          </ul>
        </div>

        <p class="relative text-[10px]" style="color:#334155">
          Contact your administrator to get access.
        </p>
      </div>

      {{-- Right panel — form --}}
      <div class="login-right md:col-span-3 px-5 py-6 md:px-7 md:py-7">

        {{-- Mobile brand header --}}
        <div class="md:hidden mb-4">
          <div class="flex items-center justify-between mb-3">
            <div class="inline-flex items-center gap-2">
              <div class="h-8 w-8 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500
                          flex items-center justify-center text-slate-950 font-bold text-xs">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 2)) }}</div>
              <div>
                <div class="text-sm font-semibold login-heading">{{ config('app.name') }}</div>
                <div class="text-[11px] login-muted">Fuel &amp; Transport ERP</div>
              </div>
            </div>
            {{-- Theme toggle (mobile) --}}
            <button type="button" id="loginThemeToggle" class="login-theme-btn" aria-label="Toggle theme">
              <svg data-icon="moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
              </svg>
              <svg data-icon="sun" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
              </svg>
            </button>
          </div>
          <h1 class="text-lg font-semibold login-heading">Sign in</h1>
          <p class="text-xs login-muted">Use the email and password your owner gave you.</p>
        </div>

        {{-- Desktop top row: heading + toggle --}}
        <div class="hidden md:flex items-center justify-between mb-5">
          <div>
            <h2 class="text-base font-semibold login-heading">Welcome back</h2>
            <p class="text-xs login-muted mt-0.5">Enter your credentials to continue</p>
          </div>
          <button type="button" id="loginThemeToggleDesktop" class="login-theme-btn" aria-label="Toggle theme">
            <svg data-icon="moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
            </svg>
            <svg data-icon="sun" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
            </svg>
          </button>
        </div>

        <form method="post" action="{{ route('login.post') }}" class="space-y-4">
          @csrf

          @if($errors->any())
            <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-700 dark:text-rose-300">
              {{ $errors->first() }}
            </div>
          @endif

          <div class="space-y-1.5">
            <label class="block text-xs font-medium login-label">Email</label>
            <input
              name="email" type="email"
              value="{{ old('email') }}"
              class="login-input"
              placeholder="you@example.com"
              required
            >
          </div>

          <div class="space-y-1.5">
            <label class="block text-xs font-medium login-label">Password</label>
            <input
              name="password" type="password"
              class="login-input"
              placeholder="Your password"
              required
            >
          </div>

          <div class="text-[11px] login-remember">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox" name="remember"
                class="h-3.5 w-3.5 rounded accent-emerald-500"
              >
              <span>Keep me signed in on this device</span>
            </label>
          </div>

          <div class="pt-1 space-y-2">
            <button type="submit"
              class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400
                     text-sm font-semibold text-slate-950 tracking-wide transition
                     shadow-md shadow-emerald-500/20 active:scale-[0.99]">
              Login
            </button>
            <p class="text-[11px] login-muted text-center">
              Having trouble? Confirm your email &amp; password with your administrator.
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
      var btns = [
        document.getElementById('loginThemeToggle'),
        document.getElementById('loginThemeToggleDesktop')
      ].filter(Boolean);

      function isDark() { return root.classList.contains('dark'); }

      function applyIcons() {
        btns.forEach(function(btn) {
          btn.querySelector('[data-icon="moon"]').classList.toggle('hidden', isDark());
          btn.querySelector('[data-icon="sun"]').classList.toggle('hidden', !isDark());
        });
      }

      applyIcons();

      btns.forEach(function(btn) {
        btn.addEventListener('click', function(){
          var dark = !isDark();
          root.classList.toggle('dark', dark);
          localStorage.setItem(THEME_KEY, dark ? 'dark' : 'light');
          applyIcons();
        });
      });
    })();
  </script>
</body>
</html>
