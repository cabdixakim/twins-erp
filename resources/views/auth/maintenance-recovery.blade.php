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
<title>Maintainer Recovery — {{ config('app.name') }}</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4" style="background:var(--tw-bg)">

<div class="w-full max-w-sm">

    <div class="mb-8 text-center">
        <div class="inline-flex h-12 w-12 rounded-2xl items-center justify-center text-white font-bold text-lg mb-3"
             style="background:rgba(244,63,94,.8)">Tw</div>
        <h1 class="text-base font-bold" style="color:var(--tw-fg)">Maintainer Recovery</h1>
        <p class="mt-1 text-xs" style="color:var(--tw-muted)">
            For maintainer use only, when an owner has lost both their password and
            their self-service recovery code. Requires the maintenance recovery key.
        </p>
    </div>

    @if (session('status'))
    <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-600/10 p-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 rounded-xl border border-rose-500/40 bg-rose-600/10 p-3 text-sm text-rose-400">
        @foreach($errors->all() as $err)
            <div>{{ $err }}</div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('maintenance-recovery.recover') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">Maintenance recovery key</label>
            <input type="password" name="key" required autofocus
                class="w-full rounded-xl border px-3 py-2.5 text-sm font-mono outline-none focus:ring-2 focus:ring-rose-500/30"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                autocomplete="off">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">Owner email address</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-500/30"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                placeholder="owner@company.com">
            <p class="mt-1 text-[11px]" style="color:var(--tw-muted)">
                Must be an existing owner account. This tool cannot create new accounts.
            </p>
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">New password <span style="color:var(--tw-muted)">(min 8 chars)</span></label>
            <input type="password" name="password" required minlength="8"
                class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-500/30"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                autocomplete="new-password">
        </div>

        <div>
            <label class="block text-xs font-semibold mb-1" style="color:var(--tw-muted)">Confirm new password</label>
            <input type="password" name="password_confirmation" required minlength="8"
                class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-rose-500/30"
                style="border-color:var(--tw-border);background:var(--tw-surface-2);color:var(--tw-fg)"
                autocomplete="new-password">
        </div>

        <button type="submit"
            class="w-full h-11 rounded-xl border border-rose-600 bg-rose-500 text-white text-sm font-semibold hover:bg-rose-600 hover:border-rose-700 transition">
            Reset owner password
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
