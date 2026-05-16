<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Control Center</p>
                <h2 class="font-display text-2xl leading-tight text-white">Dashboard</h2>
            </div>
            @if(auth()->user()->canManageOrders())
            <a href="{{ route('orders.create') }}" class="inline-flex items-center rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
                + New Order
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            <form method="GET" action="{{ route('dashboard') }}" class="glass-card p-4 flex flex-wrap gap-3 items-end">
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
                    <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-600 transition">Apply</button>
                    @if(request('date_from') || request('date_to') || request('user_id'))
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm text-slate-400 hover:text-white transition">Clear</a>
                    @endif
                </div>
            </form>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="metric-card">
                    <p class="metric-label">Total Orders</p>
                    <p class="metric-value">{{ number_format($totalOrders) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">eBay Revenue</p>
                    <p class="metric-value">{{ $currencySymbol }}{{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Amazon Cost</p>
                    <p class="metric-value">{{ $currencySymbol }}{{ number_format($totalCost, 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Net Profit</p>
                    <p class="metric-value {{ $totalProfit >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ $currencySymbol }}{{ number_format($totalProfit, 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Average ROI</p>
                    <p class="metric-value">{{ number_format($averageRoi, 2) }}%</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Best Order</p>
                    <p class="metric-value text-emerald-300">{{ $currencySymbol }}{{ number_format($bestOrderProfit, 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Worst Order</p>
                    <p class="metric-value text-rose-300">{{ $currencySymbol }}{{ number_format($worstOrderProfit, 2) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Monthly Trend --}}
                <div class="glass-card p-6 lg:col-span-2">
                    <div class="flex items-end justify-between mb-5">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Trend</p>
                            <h3 class="font-display text-xl text-white">Monthly Profit</h3>
                        </div>
                        <p class="text-sm text-slate-300">Avg ROI: {{ number_format($averageRoi, 2) }}%</p>
                    </div>

                    @php
                        $trendValues = $trend->values()->map(fn ($v) => abs((float) $v));
                        $maxValue    = max(1, $trendValues->max() ?? 1);
                    @endphp

                    <div class="space-y-3">
                        @forelse($trend as $month => $value)
                            @php $percentage = min(100, (abs($value) / $maxValue) * 100); @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1 text-slate-300">
                                    <span>{{ $month }}</span>
                                    <span class="{{ $value >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ $currencySymbol }}{{ number_format($value, 2) }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-800/90 overflow-hidden">
                                    <div class="h-full rounded-full {{ $value >= 0 ? 'bg-emerald-400' : 'bg-rose-400' }}" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-400 text-sm">No trend data yet. Import or add orders to start tracking.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Wins vs Losses --}}
                <div class="glass-card p-6">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Result Split</p>
                    <h3 class="font-display text-xl text-white mb-4">Wins vs Losses</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center rounded-lg bg-emerald-400/15 border border-emerald-300/20 px-3 py-2">
                            <span class="text-emerald-200">Profitable Orders</span>
                            <span class="font-semibold text-emerald-100">{{ number_format($winningOrders) }}</span>
                        </div>
                        <div class="flex justify-between items-center rounded-lg bg-rose-400/15 border border-rose-300/20 px-3 py-2">
                            <span class="text-rose-200">Loss Orders</span>
                            <span class="font-semibold text-rose-100">{{ number_format($losingOrders) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card p-5 overflow-x-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display text-xl text-white">Orders</h3>
                    <a href="{{ route('orders.index') }}" class="text-sm text-cyan-300 hover:text-cyan-100">View all orders</a>
                </div>

                <table class="w-full min-w-[920px] text-sm text-left">
                    <thead class="text-slate-300 border-b border-white/10">
                        <tr>
                            <th class="py-3 pe-3">Date</th>
                            @if($isAdmin) <th class="py-3 pe-3">User</th> @endif
                            <th class="py-3 pe-3">Buyer</th>
                            <th class="py-3 pe-3">eBay Order</th>
                            <th class="py-3 pe-3">Status</th>
                            <th class="py-3 pe-3 text-right">Amazon Cost</th>
                            <th class="py-3 pe-3 text-right">eBay Receipts</th>
                            <th class="py-3 pe-3 text-right">Profit</th>
                            <th class="py-3 text-right">ROI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $statusColour = match($order->status) {
                                    'Delivered'    => 'bg-emerald-400/15 text-emerald-300',
                                    'Order Placed' => 'bg-cyan-400/15 text-cyan-300',
                                    'Refunded'     => 'bg-rose-400/15 text-rose-300',
                                    default        => 'bg-amber-400/15 text-amber-300',
                                };
                            @endphp
                            <tr class="border-b border-white/5">
                                <td class="py-3 pe-3 text-slate-300 whitespace-nowrap">{{ $order->order_date->format('d M Y') }}</td>
                                @if($isAdmin)
                                    <td class="py-3 pe-3 text-slate-400 text-xs">{{ $order->user->name ?? '—' }}</td>
                                @endif
                                <td class="py-3 pe-3 text-slate-200 max-w-[160px] truncate">{{ $order->buyer_name }}</td>
                                <td class="py-3 pe-3 text-slate-400 font-mono text-xs">{{ $order->ebay_order_no ?? '—' }}</td>
                                <td class="py-3 pe-3">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColour }}">{{ $order->status }}</span>
                                </td>
                                <td class="py-3 pe-3 text-right text-slate-200">{{ $currencySymbol }}{{ number_format($order->amazon_cost, 2) }}</td>
                                <td class="py-3 pe-3 text-right text-slate-200">{{ $currencySymbol }}{{ number_format($order->ebay_receipts, 2) }}</td>
                                <td class="py-3 pe-3 text-right {{ $order->profit >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ $currencySymbol }}{{ number_format($order->profit, 2) }}</td>
                                <td class="py-3 text-right text-slate-300">{{ number_format($order->roi, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 9 : 8 }}" class="py-8 text-center text-slate-400">No orders yet.@if(auth()->user()->canManageOrders()) <a href="{{ route('orders.create') }}" class="text-cyan-300 hover:underline"> Add your first order.</a>@endif</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $orders->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
