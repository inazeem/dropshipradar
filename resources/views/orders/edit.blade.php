@php
    $orderEditConfig = [
        'clientOptions' => $clients->map(fn ($client) => ['id' => (string) $client->id, 'name' => $client->name])->values()->all(),
        'selectedUserId' => old('user_id', $order->user_id),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Orders</p>
            <h2 class="font-display text-2xl leading-tight text-white">Edit Order</h2>
        </div>
    </x-slot>

    <div class="py-8" x-data="orderUserPicker({{ \Illuminate\Support\Js::from($orderEditConfig) }})">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="glass-card p-6">
                <form method="POST" action="{{ route('orders.update', $order) }}">
                    @csrf
                    @method('PUT')
                    @include('orders._form', ['order' => $order, 'submitLabel' => 'Update Order', 'isAdmin' => $isAdmin, 'clients' => $clients, 'userFieldIdPrefix' => 'edit_order'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
