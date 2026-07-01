@extends('layouts.app')
@section('title', 'My Profile')
@section('subtitle', 'Change your password and manage account security.')

@php
    $border   = 'border-[color:var(--tw-border)]';
    $surface  = 'bg-[color:var(--tw-surface)]';
    $surface2 = 'bg-[color:var(--tw-surface-2)]';
    $fg       = 'text-[color:var(--tw-fg)]';
    $muted    = 'text-[color:var(--tw-muted)]';
    $fieldBase = "mt-1 w-full rounded-xl border $border $surface2 px-3 py-2 text-sm $fg outline-none focus:ring-2 focus:ring-emerald-500/30";
    $btnPrimary = "inline-flex items-center gap-2 rounded-xl border border-emerald-500/50 bg-emerald-600 text-white hover:bg-emerald-500 transition font-semibold text-sm px-4 py-2";
    $btnGhost   = "inline-flex items-center gap-2 rounded-xl border $border bg-[color:var(--tw-btn)] $fg hover:bg-[color:var(--tw-btn-hover)] transition text-sm font-medium px-4 py-2";
    $btnDanger  = "inline-flex items-center gap-2 rounded-xl border border-rose-500/40 bg-rose-600/10 text-rose-400 hover:bg-rose-600 hover:text-white transition text-sm font-medium px-4 py-2";
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Flash messages --}}
    @if(session('status'))
    <div class="rounded-xl border border-emerald-500/30 bg-emerald-600/10 text-emerald-400 px-4 py-3 text-sm font-semibold">
        {{ session('status') }}
    </div>
    @endif

    @if(session('error'))
    <div class="rounded-xl border border-rose-500/30 bg-rose-600/10 text-rose-400 px-4 py-3 text-sm font-semibold">
        {{ session('error') }}
    </div>
    @endif

    {{-- Identity card — full width --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
        <div class="flex items-center gap-4">
            <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-xl font-bold text-white shrink-0"
                 style="background:rgba(16,185,129,.7)">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-base font-bold {{ $fg }}">{{ $user->name }}</div>
                <div class="text-xs {{ $muted }} mt-0.5">{{ $user->email }}</div>
                <div class="mt-1.5 inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide
                            {{ $isOwner ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-sky-500/10 text-sky-400 border border-sky-500/20' }}">
                    {{ $user->role?->name ?? 'No role' }}
                </div>
            </div>
            {{-- Company badge --}}
            @if(auth()->user()->activeCompany)
            <div class="hidden sm:block text-right shrink-0">
                <div class="text-xs {{ $muted }}">Active company</div>
                <div class="text-sm font-semibold {{ $fg }} mt-0.5">{{ auth()->user()->activeCompany->name }}</div>
                <div class="text-[10px] {{ $muted }}">{{ auth()->user()->activeCompany->code }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Two-column on desktop --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- LEFT — Change password --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
                <h2 class="text-sm font-bold {{ $fg }}">Change Password</h2>
                <p class="mt-0.5 text-xs {{ $muted }}">You can only change your own password here.</p>
            </div>
            <form action="{{ route('profile.password') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Current password</label>
                    <input type="password" name="current_password" required
                        class="{{ $fieldBase }} @error('current_password') border-rose-500/40 ring-2 ring-rose-500/20 @enderror"
                        autocomplete="current-password">
                    @error('current_password') <p class="mt-1 text-[11px] text-rose-500 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">New password <span class="{{ $muted }}">(min 8 chars, uppercase, number &amp; symbol)</span></label>
                    <input type="password" name="password" required minlength="8"
                        class="{{ $fieldBase }} @error('password') border-rose-500/40 ring-2 ring-rose-500/20 @enderror"
                        autocomplete="new-password">
                    @error('password') <p class="mt-1 text-[11px] text-rose-500 font-semibold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold {{ $muted }}">Confirm new password</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                        class="{{ $fieldBase }}" autocomplete="new-password">
                </div>
                <div class="pt-1">
                    <button type="submit" class="{{ $btnPrimary }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Update password
                    </button>
                </div>
            </form>
        </div>

        {{-- RIGHT — Recovery token (owner only) or placeholder for non-owners --}}
        @if($isOwner)
        <div class="rounded-2xl border border-amber-500/25 {{ $surface }} overflow-hidden">
            <div class="px-5 py-4 border-b border-amber-500/20" style="background:rgba(245,158,11,.06)">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <h2 class="text-sm font-bold {{ $fg }}">Account Recovery Token</h2>
                </div>
                <p class="mt-1 text-xs {{ $muted }}">
                    Generate a one-time recovery code. If you're ever locked out, use the recovery page to regain access.
                    <strong class="{{ $fg }}">The code is shown once — save it somewhere safe.</strong>
                </p>
            </div>

            <div class="p-5 space-y-4">

                {{-- Show the plain token immediately after generation --}}
                @if(session('recovery_plain'))
                @php $plainToken = session('recovery_plain'); @endphp
                <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 space-y-3">
                    <div class="text-xs font-bold text-amber-400">
                        ⚠ Copy this code now — it won't be shown again
                    </div>
                    <div class="flex items-center gap-3">
                        <code class="font-mono text-base font-bold tracking-widest text-amber-300 select-all break-all flex-1">{{ $plainToken }}</code>
                        <button type="button" id="profileCopyBtn"
                                onclick="profileCopyToken(this, '{{ $plainToken }}')"
                                class="shrink-0 h-8 px-3 rounded-lg border border-amber-500/40 bg-amber-500/15 text-xs font-semibold text-amber-300 hover:bg-amber-500/25 transition whitespace-nowrap">
                            📋 Copy
                        </button>
                    </div>
                    <p class="text-xs {{ $muted }}">Store this in a password manager or safe location.</p>
                </div>
                @endif

                {{-- Status --}}
                <div class="rounded-xl border {{ $border }} {{ $surface2 }} px-4 py-3 flex items-center gap-3">
                    @if($user->recovery_token)
                        <div class="h-2 w-2 rounded-full bg-emerald-400 shrink-0"></div>
                        <div>
                            <div class="text-xs font-semibold text-emerald-400">Token active</div>
                            <div class="text-[11px] {{ $muted }} mt-0.5">Regenerate to invalidate the existing code.</div>
                        </div>
                    @else
                        <div class="h-2 w-2 rounded-full bg-slate-500 shrink-0"></div>
                        <div>
                            <div class="text-xs font-semibold {{ $fg }}">No token set</div>
                            <div class="text-[11px] {{ $muted }} mt-0.5">Generate a code to enable account recovery.</div>
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 flex-wrap">
                    <a href="{{ route('account-recovery') }}" target="_blank"
                       class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border text-xs font-semibold transition"
                       style="border-color:var(--tw-border);color:var(--tw-muted)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                        Recovery page
                    </a>

                    <form action="{{ route('profile.recovery-token') }}" method="POST" id="frmGenToken">
                        @csrf
                        <button type="button" id="btnGenToken"
                                onclick="armAndFire('btnGenToken','frmGenToken','{{ $user->recovery_token ? 'Regenerate — click again to confirm' : 'Generate — click again to confirm' }}')"
                                class="{{ $btnPrimary }} text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ $user->recovery_token ? 'Regenerate' : 'Generate' }} token
                        </button>
                    </form>

                    @if($user->recovery_token)
                    <form action="{{ route('profile.recovery-token.clear') }}" method="POST" id="frmClearToken">
                        @csrf
                        <button type="button" id="btnClearToken"
                                onclick="armAndFire('btnClearToken','frmClearToken','Click again to confirm clear')"
                                class="{{ $btnDanger }} text-xs">
                            Clear token
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @else
        {{-- Non-owner: session info placeholder --}}
        <div class="rounded-2xl border {{ $border }} {{ $surface }} overflow-hidden">
            <div class="px-5 py-4 border-b {{ $border }} {{ $surface2 }}">
                <h2 class="text-sm font-bold {{ $fg }}">Account Security</h2>
            </div>
            <div class="p-5 space-y-2">
                <div class="text-xs {{ $muted }}">You are signed in as <strong class="{{ $fg }}">{{ $user->email }}</strong>.</div>
                <div class="text-xs {{ $muted }}">Contact your company owner if you need account recovery assistance.</div>
            </div>
        </div>
        @endif

    </div>{{-- end two-column --}}

</div>

<script>
function profileCopyToken(btn, token) {
  navigator.clipboard.writeText(token).then(function() {
    var orig = btn.textContent;
    btn.textContent = '✓ Copied!';
    btn.classList.add('border-emerald-500/40','bg-emerald-500/15','text-emerald-400');
    btn.classList.remove('border-amber-500/40','bg-amber-500/15','text-amber-300');
    setTimeout(function() {
      btn.textContent = orig;
      btn.classList.remove('border-emerald-500/40','bg-emerald-500/15','text-emerald-400');
      btn.classList.add('border-amber-500/40','bg-amber-500/15','text-amber-300');
    }, 2000);
  });
}

var _armed = {};
function armAndFire(btnId, formId, armedLabel) {
  var btn = document.getElementById(btnId);
  if (_armed[btnId]) {
    document.getElementById(formId).submit();
    return;
  }
  _armed[btnId] = true;
  var orig = btn.innerHTML;
  btn.textContent = armedLabel;
  btn.style.opacity = '0.75';
  setTimeout(function () {
    if (_armed[btnId]) {
      _armed[btnId] = false;
      btn.innerHTML = orig;
      btn.style.opacity = '';
    }
  }, 3000);
}
</script>
@endsection
