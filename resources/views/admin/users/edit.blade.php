@php
  $title = 'Edit user';
  $subtitle = 'Update user details, role, and status.';
@endphp

@extends('layouts.app')

@section('title', $title)
@section('subtitle', $subtitle)

@section('content')
  <div class="max-w-lg">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
      <form method="post" action="{{ route('admin.users.update', $user) }}" class="space-y-3">
        @csrf
        @method('PATCH')

        <div>
          <label class="block text-xs text-slate-300 mb-1">Name</label>
          <input name="name" value="{{ old('name', $user->name) }}"
                 class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500" required>
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Email</label>
          <input name="email" type="email" value="{{ old('email', $user->email) }}"
                 class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500" required>
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Password</label>
          <input name="password" type="password"
                 class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 placeholder:text-slate-500"
                 placeholder="Leave blank to keep current">
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Role</label>
          <select name="role_id"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
            @foreach($roles as $role)
              <option value="{{ $role->id }}" @selected($user->role_id === $role->id)>
                {{ $role->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs text-slate-300 mb-1">Status</label>
          <select name="status"
                  class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100">
            <option value="active" @selected($user->status === 'active')>Active</option>
            <option value="inactive" @selected($user->status === 'inactive')>Inactive</option>
          </select>
        </div>

        <div class="flex items-center justify-between pt-3">
          <a href="{{ route('admin.users.index') }}" class="text-[11px] text-slate-400 hover:text-slate-200">
            ← Back to users
          </a>
          <button class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-sm font-semibold text-slate-950">
            Save changes
          </button>
        </div>
      </form>
    </div>
  </div>
@endsection