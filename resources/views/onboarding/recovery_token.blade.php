<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Save your recovery code — Twins ERP</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4">
  <style>
    body { background: #0d1117; font-family: ui-sans-serif, system-ui, sans-serif; }
  </style>
</head>
<body class="min-h-full flex items-center justify-center p-4">

<div class="w-full max-w-lg" x-data="tokenPage()" x-init="init()">

  {{-- Card --}}
  <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm overflow-hidden">

    {{-- Header --}}
    <div class="bg-amber-500/10 border-b border-amber-500/20 px-6 py-5 flex items-start gap-4">
      <div class="shrink-0 mt-0.5 flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/20 border border-amber-500/30">
        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
      </div>
      <div>
        <h1 class="text-base font-bold text-amber-400">Save your owner recovery code</h1>
        <p class="mt-1 text-sm text-amber-300/70">This code is shown <strong class="text-amber-300">once only</strong> and cannot be retrieved again. If you lose your password and this code, your account cannot be recovered.</p>
      </div>
    </div>

    {{-- Token --}}
    <div class="px-6 py-6 space-y-5">

      <div class="rounded-xl border border-white/10 bg-black/30 p-4 text-center">
        <p class="text-xs text-slate-500 mb-2 uppercase tracking-wider font-semibold">Recovery Code</p>
        <code class="font-mono text-2xl font-bold tracking-[0.2em] text-white select-all" id="tokenCode">{{ $token }}</code>
      </div>

      {{-- Copy button --}}
      <button @click="copyToken()" type="button"
              class="w-full h-10 rounded-xl border text-sm font-semibold transition"
              :class="copied
                ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-400'
                : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'">
        <span x-show="!copied">📋 Copy to clipboard</span>
        <span x-show="copied">✓ Copied!</span>
      </button>

      {{-- Recovery URL info --}}
      <div class="rounded-xl border border-white/10 bg-white/5 p-4 text-sm text-slate-400 space-y-1">
        <p class="font-semibold text-slate-300 text-xs uppercase tracking-wider mb-2">How to use this code</p>
        <p>If you ever lose access, go to:</p>
        <code class="block mt-1 text-xs text-sky-400 break-all">{{ url('/account-recovery') }}</code>
        <p class="mt-2">Enter your email + this code to reset your password.</p>
      </div>

      {{-- Checkbox acknowledgement --}}
      <label class="flex items-start gap-3 cursor-pointer group">
        <input type="checkbox" x-model="acknowledged" id="ackCheck"
               class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-600 bg-slate-800 accent-amber-500 cursor-pointer">
        <span class="text-sm text-slate-400 group-hover:text-slate-300 transition">
          I have saved my recovery code in a secure location and understand it will not be shown again.
        </span>
      </label>

      {{-- Proceed button --}}
      <form method="POST" action="{{ route('onboarding.token.dismiss') }}">
        @csrf
        <button type="submit"
                :disabled="!acknowledged"
                class="w-full h-11 rounded-xl text-sm font-bold transition"
                :class="acknowledged
                  ? 'bg-amber-500 hover:bg-amber-400 text-black cursor-pointer'
                  : 'bg-white/5 text-slate-600 border border-white/10 cursor-not-allowed'">
          I've saved it — take me to the dashboard
        </button>
      </form>

    </div>
  </div>

  <p class="mt-4 text-center text-xs text-slate-600">Twins ERP · Owner setup complete</p>
</div>

<script>
function tokenPage() {
  return {
    copied: false,
    acknowledged: false,
    init() {},
    copyToken() {
      navigator.clipboard.writeText('{{ $token }}').then(() => {
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
      });
    }
  }
}
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
