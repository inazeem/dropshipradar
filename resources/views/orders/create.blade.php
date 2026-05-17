@php
    $orderCreateConfig = [
        'clientOptions' => $clients->map(fn ($client) => ['id' => (string) $client->id, 'name' => $client->name])->values()->all(),
        'selectedUserId' => old('user_id', $clients->first()?->id),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Orders</p>
            <h2 class="font-display text-2xl leading-tight text-white">New Order</h2>
        </div>
    </x-slot>

    <div class="py-8" x-data="orderUserPicker({{ \Illuminate\Support\Js::from($orderCreateConfig) }})">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="glass-card p-6">
                <form method="POST" action="{{ route('orders.store') }}">
                    @csrf
                    @include('orders._form', ['submitLabel' => 'Add Order', 'isAdmin' => $isAdmin, 'clients' => $clients, 'userFieldIdPrefix' => 'create_order'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
