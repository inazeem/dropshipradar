<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Edit</p>
                <h2 class="font-display text-2xl leading-tight text-white">Listing</h2>
            </div>
            <a href="{{ route('listings.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Back</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="glass-card p-6 md:p-8">
                <form method="POST" action="{{ route('listings.update', $listing) }}">
                    @csrf
                    @method('PATCH')
                    @include('listings._form', ['listing' => $listing, 'submitLabel' => 'Save Changes'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
