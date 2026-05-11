<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Performance</p>
            <h2 class="font-display text-2xl leading-tight text-white">Profit & Loss</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filters --}}
            <form method="GET" action="{{ route('profit-loss.index') }}" class="glass-card p-4 flex flex-wrap gap-3 items-end">
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
                        <a href="{{ route('profit-loss.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm text-slate-400 hover:text-white transition">Clear</a>
                    @endif
                </div>
            </form> grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
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
                    <p class="metric-label">Net P/L</p>
                    <p class="metric-value {{ $totals['profit'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ $currencySymbol }}{{ number_format($totals['profit'], 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Average ROI</p>
                    <p class="metric-value">{{ number_format($totals['avg_roi'], 2) }}%</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Best Order</p>
                    <p class="metric-value text-emerald-300">{{ $currencySymbol }}{{ number_format($totals['max_profit'], 2) }}</p>
                </div>
                <div class="metric-card">
                    <p class="metric-label">Worst Order</p>
                    <p class="metric-value text-rose-300">{{ $currencySymbol }}{{ number_format($totals['max_loss'], 2) }}</p>
                </div>
            </div>

            <div class="glass-card p-6">
                <h3 class="font-display text-xl text-white mb-4">Monthly Profit Trend</h3>

                @php
                    $values = $monthlyProfit->values()->map(fn ($value) => abs((float) $value));
                    $maxValue = max(1, $values->max() ?? 1);
                @endphp

                <div class="space-y-3">
                    @forelse($monthlyProfit as $month => $value)
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
                        <p class="text-slate-400 text-sm">No monthly data yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="glass-card p-5 overflow-x-auto">
                <table class="w-full min-w-[920px] text-sm">
                    <thead class="text-slate-300 border-b border-white/10">
                        <tr>
                            <th class="text-left py-3 pe-3">Date</th>
                            <th class="text-left py-3 pe-3">Buyer</th>
                            <th class="text-left py-3 pe-3">eBay Order</th>
                            <th class="text-left py-3 pe-3">Status</th>
                            <th class="text-right py-3 pe-3">Amazon Cost</th>
                            <th class="text-right py-3 pe-3">eBay Receipts</th>
                            <th class="text-right py-3 pe-3">Profit</th>
                            <th class="text-right py-3">ROI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $statusColour = match($order->status) {
                                    'Delivered'    => 'text-emerald-300',
                                    'Order Placed' => 'text-cyan-300',
                                    'Refunded'     => 'text-rose-300',
                                    default        => 'text-amber-300',
                                };
                            @endphp
                            <tr class="border-b border-white/5">
                                <td class="py-3 pe-3 whitespace-nowrap text-slate-300">{{ $order->order_date->format('d M Y') }}</td>
                                <td class="py-3 pe-3 text-slate-100 max-w-[140px] truncate">{{ $order->buyer_name }}</td>
                                <td class="py-3 pe-3 font-mono text-xs text-slate-400">{{ $order->ebay_order_no ?? '—' }}</td>
                                <td class="py-3 pe-3 {{ $statusColour }}">{{ $order->status }}</td>
                                <td class="py-3 pe-3 text-right text-slate-200">{{ $currencySymbol }}{{ number_format($order->amazon_cost, 2) }}</td>
                                <td class="py-3 pe-3 text-right text-slate-200">{{ $currencySymbol }}{{ number_format($order->ebay_receipts, 2) }}</td>
                                <td class="py-3 pe-3 text-right font-semibold {{ $order->profit >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">{{ $currencySymbol }}{{ number_format($order->profit, 2) }}</td>
                                <td class="py-3 text-right {{ $order->roi >= 0 ? 'text-emerald-300/80' : 'text-rose-300/80' }}">{{ number_format($order->roi, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400">No orders yet. <a href="{{ route('orders.create') }}" class="text-cyan-300 hover:underline">Add your first order.</a></td>
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
