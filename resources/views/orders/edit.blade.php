<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Orders</p>
            <h2 class="font-display text-2xl leading-tight text-white">Edit Order</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="glass-card p-6">
                <form method="POST" action="{{ route('orders.update', $order) }}">
                    @csrf
                    @method('PUT')
                    @include('orders._form', ['order' => $order, 'submitLabel' => 'Update Order'])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
