@php $order = $order ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label for="order_date" class="block text-sm text-slate-300 mb-1">Order Date</label>
        <input id="order_date" name="order_date" type="date" required
            value="{{ old('order_date', optional($order?->order_date)->format('Y-m-d')) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('order_date') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="buyer_name" class="block text-sm text-slate-300 mb-1">Buyer Name</label>
        <input id="buyer_name" name="buyer_name" type="text" required
            value="{{ old('buyer_name', $order?->buyer_name) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300"
            placeholder="e.g. David Jenkins">
        @error('buyer_name') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="ebay_order_no" class="block text-sm text-slate-300 mb-1">eBay Order No</label>
        <input id="ebay_order_no" name="ebay_order_no" type="text"
            value="{{ old('ebay_order_no', $order?->ebay_order_no) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300"
            placeholder="e.g. 17-14451-06930">
        @error('ebay_order_no') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="amazon_order_no" class="block text-sm text-slate-300 mb-1">Amazon Order No</label>
        <input id="amazon_order_no" name="amazon_order_no" type="text"
            value="{{ old('amazon_order_no', $order?->amazon_order_no) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300"
            placeholder="e.g. 204-8580113-7605148">
        @error('amazon_order_no') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="amazon_cost" class="block text-sm text-slate-300 mb-1">Amazon Cost (£)</label>
        <input id="amazon_cost" name="amazon_cost" type="number" min="0" step="0.01" required
            value="{{ old('amazon_cost', $order?->amazon_cost) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300"
            placeholder="0.00">
        @error('amazon_cost') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="ebay_receipts" class="block text-sm text-slate-300 mb-1">eBay Receipts (£)</label>
        <input id="ebay_receipts" name="ebay_receipts" type="number" min="0" step="0.01" required
            value="{{ old('ebay_receipts', $order?->ebay_receipts) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300"
            placeholder="0.00">
        @error('ebay_receipts') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="block text-sm text-slate-300 mb-1">Status</label>
        <select id="status" name="status" required
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
            @foreach(\App\Models\Order::STATUSES as $s)
                <option value="{{ $s }}" @selected(old('status', $order?->status ?? 'Order Placed') === $s)>{{ $s }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="note" class="block text-sm text-slate-300 mb-1">Note</label>
        <input id="note" name="note" type="text"
            value="{{ old('note', $order?->note) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300"
            placeholder="Optional note">
        @error('note') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('orders.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</a>
    <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
        {{ $submitLabel ?? 'Save Order' }}
    </button>
</div>
