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
<div class="max-w-xl space-y-5">

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

    {{-- Identity card --}}
    <div class="rounded-2xl border {{ $border }} {{ $surface }} p-5">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl flex items-center justify-center text-lg font-bold text-white"
                 style="background:rgba(16,185,129,.7)">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <div class="text-sm font-bold {{ $fg }}">{{ $user->name }}</div>
                <div class="text-xs {{ $muted }}">{{ $user->email }}</div>
                <div class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide
                            {{ $isOwner ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-sky-500/10 text-sky-400 border border-sky-500/20' }}">
                    {{ $user->role?->name ?? 'No role' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Change password --}}
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
                <label class="text-xs font-semibold {{ $muted }}">New password <span class="{{ $muted }}">(min 8 chars)</span></label>
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

    @if($isOwner)
    {{-- Owner: recovery token --}}
    <div class="rounded-2xl border border-amber-500/25 {{ $surface }} overflow-hidden">
        <div class="px-5 py-4 border-b border-amber-500/20" style="background:rgba(245,158,11,.06)">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                <h2 class="text-sm font-bold {{ $fg }}">Account Recovery Token</h2>
            </div>
            <p class="mt-1 text-xs {{ $muted }}">
                Generate a one-time recovery code. If you're ever locked out, go to
                <code class="px-1 rounded bg-[color:var(--tw-surface-2)] text-amber-400">/account-recovery</code>
                and enter your email + this code to regain access.
                <strong class="{{ $fg }}">The code is shown once — save it somewhere safe.</strong>
            </p>
        </div>

        <div class="p-5 space-y-4">

            {{-- Show the plain token immediately after generation --}}
            @if(session('recovery_plain'))
            <div class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-4">
                <div class="text-xs font-bold text-amber-400 mb-2">
                    ⚠ Copy this code now — it won't be shown again
                </div>
                <div class="font-mono text-lg font-bold tracking-widest text-amber-300 select-all">
                    {{ session('recovery_plain') }}
                </div>
                <p class="mt-2 text-xs {{ $muted }}">Store this in a password manager or safe location. Once you navigate away, it's gone.</p>
            </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-semibold {{ $fg }}">Status</div>
                    <div class="mt-0.5 text-xs {{ $muted }}">
                        @if($user->recovery_token)
                            <span class="text-emerald-400 font-semibold">Token set</span> — a recovery code has been generated. Generate a new one to invalidate the old.
                        @else
                            <span class="{{ $muted }}">No token</span> — no active recovery code.
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex gap-2 flex-wrap">
                <form action="{{ route('profile.recovery-token') }}" method="POST">
                    @csrf
                    <button type="submit" class="{{ $btnPrimary }} text-xs"
                        onclick="return confirm('Generate a new recovery token? This will invalidate any existing code.')">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ $user->recovery_token ? 'Regenerate' : 'Generate' }} token
                    </button>
                </form>

                @if($user->recovery_token)
                <form action="{{ route('profile.recovery-token.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="{{ $btnDanger }} text-xs"
                        onclick="return confirm('Clear the recovery token? You will not be able to use it to recover your account.')">
                        Clear token
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
