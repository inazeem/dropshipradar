<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-rose-300/80">Admin</p>
                <h2 class="font-display text-2xl leading-tight text-white">Users</h2>
            </div>
            <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
                New User
            </a>
            <a href="{{ route('admin.import.create') }}" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">
                Import Listings
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-300/25 bg-emerald-400/15 px-4 py-3 text-emerald-100 text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-rose-300/25 bg-rose-400/15 px-4 py-3 text-rose-100 text-sm">{{ session('error') }}</div>
            @endif

            <div class="glass-card p-5">
                <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search name or email"
                        class="md:col-span-3 rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
                    <button type="submit" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Search</button>
                </form>
            </div>

            <div class="glass-card overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm">
                    <thead class="text-slate-300 border-b border-white/10">
                        <tr>
                            <th class="text-left px-5 py-3">Name</th>
                            <th class="text-left px-5 py-3">Email</th>
                            <th class="text-left px-5 py-3">Role</th>
                            <th class="text-left px-5 py-3">Verified</th>
                            <th class="text-left px-5 py-3">Joined</th>
                            <th class="text-right px-5 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr class="border-b border-white/5">
                                <td class="px-5 py-3 text-slate-100">{{ $user->name }}</td>
                                <td class="px-5 py-3 text-slate-300">{{ $user->email }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->isAdmin() ? 'bg-amber-400/20 text-amber-200 border border-amber-300/20' : 'bg-cyan-400/15 text-cyan-200 border border-cyan-300/20' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @if($user->email_verified_at)
                                        <span class="text-emerald-300 text-xs">✔ Verified</span>
                                    @else
                                        <span class="text-rose-300 text-xs">✖ Unverified</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-400 text-xs">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-cyan-300 hover:text-cyan-100 text-xs">Edit</a>

                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                                                @csrf
                                                <button type="submit" class="text-amber-300 hover:text-amber-100 text-xs">Impersonate</button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete {{ addslashes($user->name) }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-300 hover:text-rose-200 text-xs">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
