<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-rose-300/80">Admin · Edit</p>
                <h2 class="font-display text-2xl leading-tight text-white">{{ $user->name }}</h2>
            </div>
            <div class="flex items-center gap-2">
                @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-amber-300/40 px-4 py-2 text-sm font-semibold text-amber-200 hover:bg-amber-300/15 transition">
                            Impersonate
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-emerald-300/25 bg-emerald-400/15 px-4 py-3 text-emerald-100 text-sm">{{ session('success') }}</div>
            @endif

            <div class="glass-card p-6 md:p-8">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PATCH')
                    @include('admin.users._form', ['user' => $user, 'submitLabel' => 'Save Changes'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
