@php
    $orderStoreErrors = $errors->getBag('orderStore');
    $orderItems = collect($orders->items());
    $editingOrderId = $orderItems->first(fn ($order) => $errors->getBag('orderUpdate'.$order->id)->any())?->id;
    $returnTo = url()->full();
    $ordersTableConfig = [
        'editingId' => $editingOrderId,
        'createOpen' => $orderStoreErrors->any(),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">eBay → Amazon</p>
            <h2 class="font-display text-2xl leading-tight text-white">Orders</h2>
        </div>
    </x-slot>

    <div class="py-8" x-data="ordersTable({{ \Illuminate\Support\Js::from($ordersTableConfig) }})">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="rounded-lg bg-emerald-500/20 border border-emerald-400/30 px-4 py-3 text-emerald-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($canManageOrders)
            {{-- CSV Upload panel --}}
            <div class="glass-card p-5" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                    class="flex items-center gap-2 text-sm font-semibold text-cyan-300 hover:text-cyan-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span x-text="open ? 'Hide CSV Upload' : 'Upload Order Sheet (CSV)'"></span>
                </button>

                <div x-show="open" x-transition class="mt-4">
                    <form method="POST" action="{{ route('orders.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
                        @csrf

                        @if($isAdmin && $clients->isNotEmpty())
                            <div>
                                <label class="block text-xs text-slate-400 mb-1">Import for user</label>
                                <select name="user_id"
                                    class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300 text-sm px-3 py-2">
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" @selected(request('user_id') == $client->id)>{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs text-slate-400 mb-1">CSV File</label>
                            <input name="csv_file" type="file" accept=".csv,.txt"
                                class="block text-sm text-slate-300 file:mr-4 file:rounded-lg file:border-0 file:bg-cyan-400/20 file:text-cyan-300 file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-cyan-400/30 cursor-pointer">
                            @error('csv_file') <p class="mt-1 text-xs text-rose-300">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit"
                            class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
                            Import
                        </button>
                    </form>
                    <p class="mt-3 text-xs text-slate-500">Expects the same format as the tracking spreadsheet: Date, Buyer Name, eBay Order No, Amazon Order No, Note, Status, Amazon Price, eBay Receipts…</p>
                </div>
            </div>
            @endif

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="metric-card">
                    <p class="metric-label">Total Orders</p>
                    <p class="metric-value">{{ number_format($totals['count']) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">eBay Revenue</p>
                    <p class="metric-value">{{ $currencySymbol }}{{ number_format($totals['revenue'], 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Amazon Cost</p>
                    <p class="metric-value">{{ $currencySymbol }}{{ number_format($totals['cost'], 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Net Profit</p>
                    <p class="metric-value {{ $totals['profit'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                        {{ $currencySymbol }}{{ number_format($totals['profit'], 2) }}
                    </p>
                </div>
            </div>

            @if($canManageOrders)
            <div class="glass-card p-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-white">Manage orders inline</p>
                    <p class="text-xs text-slate-400">Click a row to edit it. Add a new order directly at the top of the table.</p>
                </div>
                <button type="button" @click="startCreate()" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
                    + Add Order
                </button>
            </div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap gap-3 items-end">
                @if($isAdmin && $clients->isNotEmpty())
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">User</label>
                        <select name="user_id"
                            class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300 text-sm px-3 py-2">
                            <option value="">All Users</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected(request('user_id') == $client->id)>{{ $client->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Search</label>
                    <input name="search" type="text" value="{{ request('search') }}"
                        placeholder="Buyer / order no…"
                        class="rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300 text-sm px-3 py-2 w-52">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Status</label>
                    <select name="status"
                        class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300 text-sm px-3 py-2">
                        <option value="">All Statuses</option>
                        @foreach(\App\Models\Order::STATUSES as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Order Date From</label>
                    <input name="date_from" type="date" value="{{ request('date_from') }}"
                        class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300 text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Order Date To</label>
                    <input name="date_to" type="date" value="{{ request('date_to') }}"
                        class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300 text-sm px-3 py-2">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-600 transition">Filter</button>
                    @if(request('search') || request('status') || request('user_id') || request('date_from') || request('date_to'))
                        <a href="{{ route('orders.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm text-slate-400 hover:text-white transition">Clear</a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="glass-card p-0 overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="text-slate-300 border-b border-white/10">
                        <tr>
                            <th class="text-left px-4 py-3">Date</th>
                            @if($isAdmin) <th class="text-left px-4 py-3">User</th> @endif
                            <th class="text-left px-4 py-3">Buyer</th>
                            <th class="text-left px-4 py-3">eBay Order</th>
                            <th class="text-left px-4 py-3">Amazon Order</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-right px-4 py-3">Amazon Cost</th>
                            <th class="text-right px-4 py-3">eBay Receipts</th>
                            <th class="text-right px-4 py-3">Profit</th>
                            <th class="text-right px-4 py-3">ROI</th>
                            <th class="text-right px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($canManageOrders)
                        <tr x-cloak x-show="createOpen" class="border-b border-cyan-400/20 bg-cyan-400/5">
                            <td colspan="{{ $isAdmin ? 11 : 10 }}" class="px-4 py-5">
                                <form method="POST" action="{{ route('orders.store') }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                        <div>
                                            <label for="new_order_date" class="block text-xs text-slate-400 mb-1">Order Date</label>
                                            <input id="new_order_date" name="order_date" type="date" required value="{{ old('order_date') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($orderStoreErrors->has('order_date')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('order_date') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_buyer_name" class="block text-xs text-slate-400 mb-1">Buyer Name</label>
                                            <input id="new_buyer_name" name="buyer_name" type="text" required value="{{ old('buyer_name') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($orderStoreErrors->has('buyer_name')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('buyer_name') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_ebay_order_no" class="block text-xs text-slate-400 mb-1">eBay Order No</label>
                                            <input id="new_ebay_order_no" name="ebay_order_no" type="text" value="{{ old('ebay_order_no') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($orderStoreErrors->has('ebay_order_no')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('ebay_order_no') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_amazon_order_no" class="block text-xs text-slate-400 mb-1">Amazon Order No</label>
                                            <input id="new_amazon_order_no" name="amazon_order_no" type="text" value="{{ old('amazon_order_no') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($orderStoreErrors->has('amazon_order_no')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('amazon_order_no') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_status_order" class="block text-xs text-slate-400 mb-1">Status</label>
                                            <select id="new_status_order" name="status" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @foreach(
                                                    \App\Models\Order::STATUSES as $s
                                                )
                                                    <option value="{{ $s }}" @selected(old('status', 'Order Placed') === $s)>{{ $s }}</option>
                                                @endforeach
                                            </select>
                                            @if($orderStoreErrors->has('status')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('status') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_amazon_cost" class="block text-xs text-slate-400 mb-1">Amazon Cost</label>
                                            <input id="new_amazon_cost" name="amazon_cost" type="number" min="0" step="0.01" required value="{{ old('amazon_cost') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($orderStoreErrors->has('amazon_cost')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('amazon_cost') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_ebay_receipts" class="block text-xs text-slate-400 mb-1">eBay Receipts</label>
                                            <input id="new_ebay_receipts" name="ebay_receipts" type="number" min="0" step="0.01" required value="{{ old('ebay_receipts') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($orderStoreErrors->has('ebay_receipts')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('ebay_receipts') }}</p> @endif
                                        </div>
                                        <div class="md:col-span-2 xl:col-span-4">
                                            <label for="new_order_note" class="block text-xs text-slate-400 mb-1">Note</label>
                                            <input id="new_order_note" name="note" type="text" value="{{ old('note') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($orderStoreErrors->has('note')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('note') }}</p> @endif
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" @click="cancelCreate()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</button>
                                        <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">Save Order</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @endif

                        @forelse($orders as $order)
                            @php
                                $statusColour = match($order->status) {
                                    'Delivered'    => 'bg-emerald-400/15 text-emerald-300',
                                    'Order Placed' => 'bg-cyan-400/15 text-cyan-300',
                                    'Refunded'     => 'bg-rose-400/15 text-rose-300',
                                    default        => 'bg-amber-400/15 text-amber-300',
                                };
                                $orderUpdateErrors = $errors->getBag('orderUpdate'.$order->id);
                            @endphp
                            <tr x-show="editingId !== {{ $order->id }}" class="border-b border-white/5 hover:bg-white/[.02] transition {{ $canManageOrders ? 'cursor-pointer' : '' }}" @if($canManageOrders) @click="startEdit({{ $order->id }})" @endif>
                                <td class="px-4 py-3 text-slate-300 whitespace-nowrap">{{ $order->order_date->format('d M Y') }}</td>
                                @if($isAdmin)
                                    <td class="px-4 py-3 text-slate-400 text-xs">{{ $order->user->name ?? '—' }}</td>
                                @endif
                                <td class="px-4 py-3 text-slate-100 max-w-[140px] truncate" title="{{ $order->buyer_name }}">{{ $order->buyer_name }}</td>
                                <td class="px-4 py-3 text-slate-400 whitespace-nowrap font-mono text-xs">{{ $order->ebay_order_no ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-400 whitespace-nowrap font-mono text-xs">
                                    @if($order->amazon_order_no && str_starts_with($order->amazon_order_no, 'http'))
                                        <span class="text-slate-500 italic">URL</span>
                                    @else
                                        {{ $order->amazon_order_no ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColour }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-300">{{ $currencySymbol }}{{ number_format($order->amazon_cost, 2) }}</td>
                                <td class="px-4 py-3 text-right text-slate-300">{{ $currencySymbol }}{{ number_format($order->ebay_receipts, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $order->profit >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                    {{ $currencySymbol }}{{ number_format($order->profit, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right {{ $order->roi >= 0 ? 'text-emerald-300/80' : 'text-rose-300/80' }}">
                                    {{ number_format($order->roi, 1) }}%
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap" @if($canManageOrders) @click.stop @endif>
                                    @if($canManageOrders)
                                    <button type="button" @click="startEdit({{ $order->id }})" class="text-xs text-slate-400 hover:text-cyan-300 transition">Edit</button>
                                    <form method="POST" action="{{ route('orders.destroy', $order) }}" class="inline ms-3"
                                          onsubmit="return confirm('Delete this order?')">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        <button type="submit" class="text-xs text-slate-400 hover:text-rose-300 transition">Delete</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @if($canManageOrders)
                            <tr x-cloak x-show="editingId === {{ $order->id }}" class="border-b border-cyan-400/20 bg-cyan-400/5">
                                <td colspan="{{ $isAdmin ? 11 : 10 }}" class="px-4 py-5">
                                    <form method="POST" action="{{ route('orders.update', $order) }}" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                            <div>
                                                <label for="order_date_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">Order Date</label>
                                                <input id="order_date_{{ $order->id }}" name="order_date" type="date" required value="{{ old('order_date', optional($order->order_date)->format('Y-m-d')) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($orderUpdateErrors->has('order_date')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('order_date') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="buyer_name_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">Buyer Name</label>
                                                <input id="buyer_name_{{ $order->id }}" name="buyer_name" type="text" required value="{{ old('buyer_name', $order->buyer_name) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($orderUpdateErrors->has('buyer_name')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('buyer_name') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="ebay_order_no_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">eBay Order No</label>
                                                <input id="ebay_order_no_{{ $order->id }}" name="ebay_order_no" type="text" value="{{ old('ebay_order_no', $order->ebay_order_no) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($orderUpdateErrors->has('ebay_order_no')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('ebay_order_no') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="amazon_order_no_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">Amazon Order No</label>
                                                <input id="amazon_order_no_{{ $order->id }}" name="amazon_order_no" type="text" value="{{ old('amazon_order_no', $order->amazon_order_no) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($orderUpdateErrors->has('amazon_order_no')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('amazon_order_no') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="status_order_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">Status</label>
                                                <select id="status_order_{{ $order->id }}" name="status" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                    @foreach(\App\Models\Order::STATUSES as $s)
                                                        <option value="{{ $s }}" @selected(old('status', $order->status) === $s)>{{ $s }}</option>
                                                    @endforeach
                                                </select>
                                                @if($orderUpdateErrors->has('status')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('status') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="amazon_cost_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">Amazon Cost</label>
                                                <input id="amazon_cost_{{ $order->id }}" name="amazon_cost" type="number" min="0" step="0.01" required value="{{ old('amazon_cost', $order->amazon_cost) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($orderUpdateErrors->has('amazon_cost')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('amazon_cost') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="ebay_receipts_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">eBay Receipts</label>
                                                <input id="ebay_receipts_{{ $order->id }}" name="ebay_receipts" type="number" min="0" step="0.01" required value="{{ old('ebay_receipts', $order->ebay_receipts) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($orderUpdateErrors->has('ebay_receipts')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('ebay_receipts') }}</p> @endif
                                            </div>
                                            <div class="md:col-span-2 xl:col-span-4">
                                                <label for="order_note_{{ $order->id }}" class="block text-xs text-slate-400 mb-1">Note</label>
                                                <input id="order_note_{{ $order->id }}" name="note" type="text" value="{{ old('note', $order->note) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($orderUpdateErrors->has('note')) <p class="mt-1 text-xs text-rose-300">{{ $orderUpdateErrors->first('note') }}</p> @endif
                                            </div>
                                        </div>
                                        <div class="flex justify-end gap-3">
                                            <button type="button" @click="cancelEdit()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</button>
                                            <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">Save Changes</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 11 : 10 }}" class="px-4 py-10 text-center text-slate-400">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($orders->hasPages())
                <div class="text-slate-400">
                    {{ $orders->links() }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
