<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-rose-300/80">Admin</p>
            <h2 class="font-display text-2xl leading-tight text-white">New User</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-card p-6 md:p-8">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    @include('admin.users._form', ['submitLabel' => 'Create User'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
