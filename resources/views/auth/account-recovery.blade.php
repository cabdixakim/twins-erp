<!doctype html>
<html lang="en" class="h-full">
<head>
<script>
  (function(){
    const s=localStorage.getItem('tw-theme');
    const d=window.matchMedia?.('(prefers-color-scheme:dark)')?.matches;
    if(s==='dark'||(s!=='light'&&d))document.documentElement.classList.add('dark');
  })();
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Account Recovery — {{ config('app.name') }}</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4" style="background:var(--tw-bg)">

<div class="w-full max-w-sm">

    {{-- Logo / brand --}}
    <div class="mb-8 text-center">
        <div class="inline-flex h-12 w-12 rounded-2xl items-center justify-center text-white font-bold text-lg mb-3"
             style="background:rgba(16,185,129,.8)">Tw</div>
        <h1 class="text-base font-bold" style="color:var(--tw-fg)">Owner Account Recovery</h1>
        <p class="mt-1 text-xs" style="color:var(--tw-muted)">
            Enter your email and the one-time recovery code you generated from your profile.
        </p>
    </div>

    {{-- Errors --}}
    @if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-600/10 p-3 text-sm text-rose-400">
        @foreach($errors->all() as $err)
            <div>{{ $err }}</div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('account-recovery.recover') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">Owner email address</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500/30"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                placeholder="you@company.com">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">Recovery code</label>
            <input type="text" name="token" required
                class="w-full rounded-xl border px-3 py-2.5 text-sm font-mono tracking-widest outline-none focus:ring-2 focus:ring-emerald-500/30 uppercase"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                placeholder="XXXX-XXXX-XXXX-XXXX"
                autocomplete="off">
            <p class="mt-1 text-[11px]" style="color:var(--tw-muted)">
                This is the one-time code generated from Profile → Account Recovery Token.
            </p>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">New password <span style="color:var(--tw-muted)">(min 8 chars)</span></label>
            <input type="password" name="password" required minlength="8"
                class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500/30"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                autocomplete="new-password">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">Confirm new password</label>
            <input type="password" name="password_confirmation" required minlength="8"
                class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-emerald-500/30"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                autocomplete="new-password">
        </div>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="logout_all_sessions" value="1"
                   class="mt-0.5 h-4 w-4 shrink-0 rounded accent-emerald-500 cursor-pointer"
                   style="border-color:var(--tw-border);background:var(--tw-surface-2)">
            <span class="text-xs leading-relaxed" style="color:var(--tw-muted)">
                Sign out all other active sessions after recovery
                <span class="block mt-0.5 opacity-70">Recommended if you suspect unauthorised access</span>
            </span>
        </label>

        <button type="submit"
            class="w-full h-11 rounded-xl border border-emerald-600 bg-emerald-500 text-white text-sm font-semibold hover:bg-emerald-600 hover:border-emerald-700 transition">
            Recover account
        </button>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('login') }}" class="text-xs hover:underline" style="color:var(--tw-muted)">
            ← Back to login
        </a>
    </div>

</div>
</body>
</html>
