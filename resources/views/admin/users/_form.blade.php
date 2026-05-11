{{-- Reusable form partial for admin user create / edit --}}
@php $user = $user ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label for="name" class="block text-sm text-slate-300 mb-1">Full Name</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $user?->name) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('name') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm text-slate-300 mb-1">Email</label>
        <input id="email" name="email" type="email" required value="{{ old('email', $user?->email) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('email') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="block text-sm text-slate-300 mb-1">
            Password {{ $user ? '(leave blank to keep current)' : '' }}
        </label>
        <input id="password" name="password" type="password" {{ $user ? '' : 'required' }}
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('password') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm text-slate-300 mb-1">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
    </div>

    <div>
        <label for="role" class="block text-sm text-slate-300 mb-1">Role</label>
        <select id="role" name="role" required
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
            <option value="client" @selected(old('role', $user?->role) === 'client')>Client</option>
            <option value="admin" @selected(old('role', $user?->role) === 'admin')>Admin</option>
        </select>
        @error('role') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col gap-3 justify-end">
        @if($user)
            <label class="inline-flex items-center gap-2 text-sm text-slate-300 cursor-pointer select-none">
                <input type="checkbox" name="verify_email" value="1" @checked($user->email_verified_at)
                    class="rounded border-white/20 bg-slate-900/70 text-cyan-400 focus:ring-cyan-400">
                Mark email as verified
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-300 cursor-pointer select-none">
                <input type="checkbox" name="unverify_email" value="1" @checked(!$user->email_verified_at)
                    class="rounded border-white/20 bg-slate-900/70 text-rose-400 focus:ring-rose-400">
                Revoke email verification
            </label>
        @else
            <label class="inline-flex items-center gap-2 text-sm text-slate-300 cursor-pointer select-none">
                <input type="checkbox" name="verified" value="1"
                    class="rounded border-white/20 bg-slate-900/70 text-cyan-400 focus:ring-cyan-400">
                Mark email as verified immediately
            </label>
        @endif
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</a>
    <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
        {{ $submitLabel ?? 'Save User' }}
    </button>
</div>
