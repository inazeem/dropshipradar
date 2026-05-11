<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Create</p>
            <h2 class="font-display text-2xl leading-tight text-white">New Listing</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-card p-6 md:p-8">
                <form method="POST" action="{{ route('listings.store') }}">
                    @csrf
                    @include('listings._form', ['submitLabel' => 'Create Listing'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
