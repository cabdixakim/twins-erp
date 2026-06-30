<!doctype html>
<html class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Setup • {{ config('app.name') }}</title>
  @vite(['resources/css/app.css'])

  <script>
    (function(){
      var t = localStorage.getItem('tw-theme');
      var dark = t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
      if (dark) document.documentElement.classList.add('dark');
    })();
  </script>

  <style>
    .ob-page {
      background: #f0f2f5;
      transition: background 0.2s;
    }
    html.dark .ob-page {
      background: #0f172a;
    }

    .ob-card {
      background: #ffffff;
      border-color: #d1d5db;
      box-shadow: 0 20px 60px rgba(0,0,0,.10);
    }
    html.dark .ob-card {
      background: rgba(15,23,42,.85);
      border-color: #1e293b;
      box-shadow: 0 20px 60px rgba(0,0,0,.50), 0 0 0 1px rgba(52,211,153,.06);
    }

    .ob-right {
      background: #ffffff;
    }
    html.dark .ob-right {
      background: transparent;
    }

    .ob-divider {
      border-right: 1px solid #e5e7eb;
    }
    html.dark .ob-divider {
      border-right-color: #1e293b;
    }

    .ob-label { color: #374151; }
    html.dark .ob-label { color: #cbd5e1; }

    .ob-muted { color: #6b7280; }
    html.dark .ob-muted { color: #64748b; }

    .ob-heading { color: #111827; }
    html.dark .ob-heading { color: #f8fafc; }

    .ob-section-title { color: #6b7280; }
    html.dark .ob-section-title { color: #475569; }

    .ob-divider-line { border-color: #e5e7eb; }
    html.dark .ob-divider-line { border-color: #1e293b; }

    .ob-input {
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
    .ob-input::placeholder { color: #9ca3af; }
    .ob-input:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16,185,129,.15);
    }
    .ob-input.error {
      border-color: #f43f5e;
    }
    html.dark .ob-input {
      background: #020617;
      border-color: #334155;
      color: #f1f5f9;
    }
    html.dark .ob-input::placeholder { color: #475569; }
    html.dark .ob-input:focus {
      border-color: #34d399;
      box-shadow: 0 0 0 3px rgba(52,211,153,.18);
    }

    .ob-theme-btn {
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
    .ob-theme-btn:hover { background: #e5e7eb; color: #374151; }
    html.dark .ob-theme-btn {
      background: rgba(255,255,255,.06);
      border-color: #334155;
      color: #94a3b8;
    }
    html.dark .ob-theme-btn:hover {
      background: rgba(255,255,255,.10);
      color: #e2e8f0;
    }

    /* Radio pill selection */
    .ob-radio-opt {
      border-color: #d1d5db;
      color: #374151;
    }
    html.dark .ob-radio-opt {
      border-color: #334155;
      color: #cbd5e1;
    }
    .ob-radio-opt.selected {
      border-color: #10b981;
      background: rgba(16,185,129,.08);
      color: #059669;
    }
    html.dark .ob-radio-opt.selected {
      border-color: #34d399;
      background: rgba(52,211,153,.10);
      color: #34d399;
    }
    .ob-radio-dot {
      border-color: #d1d5db;
      background: transparent;
      transition: border-color .15s, background .15s;
    }
    html.dark .ob-radio-dot { border-color: #475569; }
    .ob-radio-opt.selected .ob-radio-dot {
      border-color: #10b981;
      background: #10b981;
      box-shadow: inset 0 0 0 2px #fff;
    }
    html.dark .ob-radio-opt.selected .ob-radio-dot {
      border-color: #34d399;
      background: #34d399;
      box-shadow: inset 0 0 0 2px #020617;
    }
  </style>
</head>
<body class="ob-page min-h-full flex items-center justify-center px-4 py-8">

  <div class="ob-card w-full max-w-3xl rounded-3xl border overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-5">

      {{-- Left panel — always dark brand panel --}}
      <div class="hidden md:flex md:col-span-2 ob-divider flex-col justify-between
                  bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950 px-6 py-6">
        <div>
          <div class="inline-flex items-center gap-2 mb-6">
            <div class="h-9 w-9 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500
                        flex items-center justify-center text-slate-950 font-bold text-sm">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 2)) }}</div>
            <div>
              <div class="text-sm font-semibold text-slate-50 tracking-wide">{{ config('app.name') }}</div>
              <div class="text-[11px] text-slate-400">Fuel &amp; Transport ERP</div>
            </div>
          </div>

          <h1 class="text-xl font-semibold text-slate-50 mb-2">First-time setup</h1>
          <p class="text-xs leading-relaxed text-slate-400 mb-6">
            Create your company and owner account. Everything else — depots, suppliers, products — can be configured inside the app.
          </p>

          <div class="space-y-3 text-xs text-slate-300">
            <div class="flex items-center gap-2">
              <span class="h-5 w-5 rounded-full bg-emerald-500/15 border border-emerald-500/50
                           flex items-center justify-center text-[10px] text-emerald-300 font-semibold">1</span>
              <span>Company &amp; owner details</span>
            </div>
            <div class="flex items-center gap-2 opacity-50">
              <span class="h-5 w-5 rounded-full border border-slate-700
                           flex items-center justify-center text-[10px] text-slate-500 font-semibold">2</span>
              <span>Configure depots, products &amp; suppliers</span>
            </div>
            <div class="flex items-center gap-2 opacity-50">
              <span class="h-5 w-5 rounded-full border border-slate-700
                           flex items-center justify-center text-[10px] text-slate-500 font-semibold">3</span>
              <span>Start recording purchases &amp; sales</span>
            </div>
          </div>
        </div>

        <p class="mt-6 text-[11px] text-slate-500">Takes less than a minute.</p>
      </div>

      {{-- Right panel — form --}}
      <div class="ob-right md:col-span-3 px-5 py-6 md:px-7 md:py-7">

        {{-- Mobile header --}}
        <div class="md:hidden mb-4">
          <div class="flex items-center justify-between mb-3">
            <div class="inline-flex items-center gap-2">
              <div class="h-8 w-8 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500
                          flex items-center justify-center text-slate-950 font-bold text-xs">{{ mb_strtoupper(mb_substr(config('app.name'), 0, 2)) }}</div>
              <div>
                <div class="text-sm font-semibold ob-heading">{{ config('app.name') }}</div>
                <div class="text-[11px] ob-muted">Fuel &amp; Transport ERP</div>
              </div>
            </div>
            <button type="button" id="obThemeToggle" class="ob-theme-btn" aria-label="Toggle theme">
              <svg data-icon="moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
              </svg>
              <svg data-icon="sun" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
              </svg>
            </button>
          </div>
          <h1 class="text-lg font-semibold ob-heading">First-time setup</h1>
          <p class="text-xs ob-muted">Create your company and owner account.</p>
        </div>

        {{-- Desktop header --}}
        <div class="hidden md:flex items-center justify-between mb-5">
          <div>
            <h2 class="text-base font-semibold ob-heading">Create your workspace</h2>
            <p class="text-xs ob-muted mt-0.5">This runs once — you can't undo it, so double-check your details.</p>
          </div>
          <button type="button" id="obThemeToggleDesktop" class="ob-theme-btn" aria-label="Toggle theme">
            <svg data-icon="moon" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1111.2 3a7 7 0 009.8 9.8z"/>
            </svg>
            <svg data-icon="sun" class="w-4 h-4 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a6 6 0 100-12 6 6 0 000 12z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4 12H2m20 0h-2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19"/>
            </svg>
          </button>
        </div>

        @if($errors->any())
          <div class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-xs text-rose-700 dark:text-rose-300 mb-4">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="post" action="{{ route('company.store') }}" class="space-y-5">
          @csrf

          {{-- Company section --}}
          <div class="space-y-3">
            <p class="text-[10px] uppercase tracking-widest font-semibold ob-section-title">Company</p>

            <div>
              <label class="block text-xs font-medium ob-label mb-1">Company name</label>
              <input name="company_name" value="{{ old('company_name') }}"
                     class="ob-input {{ $errors->has('company_name') ? 'error' : '' }}"
                     placeholder="e.g. Optima Diesel Congo SARL" required>
              @error('company_name')
                <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs font-medium ob-label mb-1">
                  Code
                  <span class="ob-muted font-normal">— short unique ID</span>
                </label>
                <input name="code" value="{{ old('code') }}"
                       class="ob-input {{ $errors->has('code') ? 'error' : '' }}"
                       placeholder="e.g. TWE" required>
                @error('code')
                  <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div>
                <label class="block text-xs font-medium ob-label mb-1">Base currency</label>
                <input name="base_currency" value="{{ old('base_currency','USD') }}"
                       class="ob-input"
                       placeholder="USD" required>
              </div>
              <div>
                <label class="block text-xs font-medium ob-label mb-1">Timezone</label>
                <input name="timezone" value="{{ old('timezone','Africa/Lubumbashi') }}"
                       class="ob-input"
                       placeholder="Africa/Lubumbashi">
              </div>
            </div>
          </div>

          {{-- Divider --}}
          <div class="border-t ob-divider-line"></div>

          {{-- Operational defaults --}}
          <div class="space-y-3">
            <p class="text-[10px] uppercase tracking-widest font-semibold ob-section-title">Operational defaults</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

              {{-- Volume unit --}}
              <div>
                <label class="block text-xs font-medium ob-label mb-2">Volume unit</label>
                <div class="flex gap-2">
                  @foreach(['L' => 'Litres', 'USG' => 'US Gallons', 'IMG' => 'Imp. Gallons'] as $val => $label)
                  <label class="flex-1 flex items-center gap-1.5 cursor-pointer rounded-lg border px-2.5 py-2 text-xs font-medium transition
                                ob-radio-opt {{ old('volume_unit','L') === $val ? 'selected' : '' }}"
                         style="border-color:#d1d5db;color:#374151">
                    <input type="radio" name="volume_unit" value="{{ $val }}"
                           {{ old('volume_unit','L') === $val ? 'checked' : '' }}
                           class="sr-only ob-radio">
                    <span class="ob-radio-dot h-3 w-3 rounded-full border-2 flex-shrink-0" style="border-color:#d1d5db"></span>
                    {{ $label }}
                  </label>
                  @endforeach
                </div>
              </div>

              {{-- Costing method --}}
              <div>
                <label class="block text-xs font-medium ob-label mb-2">Inventory costing</label>
                <div class="flex gap-2 flex-col">
                  @foreach(['weighted_average' => ['Weighted Average','Blended cost across all stock'], 'specific_lot' => ['Specific Lot','Exact cost per batch (FIFO)']] as $val => [$label, $desc])
                  <label class="flex items-start gap-2 cursor-pointer rounded-lg border px-2.5 py-2 text-xs font-medium transition
                                ob-radio-opt {{ old('costing_method','weighted_average') === $val ? 'selected' : '' }}"
                         style="border-color:#d1d5db;color:#374151">
                    <input type="radio" name="costing_method" value="{{ $val }}"
                           {{ old('costing_method','weighted_average') === $val ? 'checked' : '' }}
                           class="sr-only ob-radio mt-0.5">
                    <span class="ob-radio-dot h-3 w-3 rounded-full border-2 flex-shrink-0 mt-0.5" style="border-color:#d1d5db"></span>
                    <span>{{ $label }}<span class="block font-normal ob-muted text-[10px]">{{ $desc }}</span></span>
                  </label>
                  @endforeach
                </div>
              </div>

            </div>
          </div>

          {{-- Divider --}}
          <div class="border-t ob-divider-line"></div>

          {{-- Owner section --}}
          <div class="space-y-3">
            <p class="text-[10px] uppercase tracking-widest font-semibold ob-section-title">Owner account</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium ob-label mb-1">Full name</label>
                <input name="owner_name" value="{{ old('owner_name') }}"
                       class="ob-input {{ $errors->has('owner_name') ? 'error' : '' }}"
                       placeholder="Your full name" required>
              </div>
              <div>
                <label class="block text-xs font-medium ob-label mb-1">Email</label>
                <input name="owner_email" type="email" value="{{ old('owner_email') }}"
                       class="ob-input {{ $errors->has('owner_email') ? 'error' : '' }}"
                       placeholder="you@example.com" required>
                @error('owner_email')
                  <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
                @enderror
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium ob-label mb-1">Password</label>
              <input name="owner_password" type="password"
                     class="ob-input {{ $errors->has('owner_password') ? 'error' : '' }}"
                     placeholder="Min 8 chars, uppercase, number & symbol" required>
              @error('owner_password')
                <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p>
              @enderror
            </div>
          </div>

          {{-- Submit --}}
          <div class="pt-1 space-y-2">
            <button type="submit"
                    class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400
                           text-sm font-semibold text-slate-950 tracking-wide transition
                           shadow-md shadow-emerald-500/20 active:scale-[0.99]">
              Create workspace
            </button>
            <p class="text-[11px] ob-muted text-center">
              This sets up {{ config('app.name') }} for your company. Additional users can be added afterwards.
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
        document.getElementById('obThemeToggle'),
        document.getElementById('obThemeToggleDesktop')
      ].filter(Boolean);

      function isDark(){ return root.classList.contains('dark'); }

      function applyIcons(){
        btns.forEach(function(btn){
          btn.querySelector('[data-icon="moon"]').classList.toggle('hidden', isDark());
          btn.querySelector('[data-icon="sun"]').classList.toggle('hidden', !isDark());
        });
      }

      applyIcons();

      btns.forEach(function(btn){
        btn.addEventListener('click', function(){
          var dark = !isDark();
          root.classList.toggle('dark', dark);
          localStorage.setItem(THEME_KEY, dark ? 'dark' : 'light');
          applyIcons();
        });
      });
    })();

    // Radio pill interactivity
    document.querySelectorAll('.ob-radio').forEach(function(radio) {
      radio.addEventListener('change', function() {
        var name = this.name;
        document.querySelectorAll('input[name="' + name + '"]').forEach(function(r) {
          r.closest('.ob-radio-opt').classList.toggle('selected', r.checked);
        });
      });
    });
  </script>

</body>
</html>
