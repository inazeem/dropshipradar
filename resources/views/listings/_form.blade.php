@php
    $listing = $listing ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="md:col-span-2">
        <label for="ebay_url" class="block text-sm text-slate-300 mb-1">eBay URL</label>
        <input id="ebay_url" name="ebay_url" type="url" required value="{{ old('ebay_url', $listing?->ebay_url) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300" placeholder="https://www.ebay...">
        @error('ebay_url') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="amazon_url" class="block text-sm text-slate-300 mb-1">Amazon URL</label>
        <input id="amazon_url" name="amazon_url" type="url" value="{{ old('amazon_url', $listing?->amazon_url) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300" placeholder="https://www.amazon...">
        @error('amazon_url') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="ebay_price" class="block text-sm text-slate-300 mb-1">eBay Price ($)</label>
        <input id="ebay_price" name="ebay_price" type="number" min="0" step="0.01" required value="{{ old('ebay_price', $listing?->ebay_price) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('ebay_price') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="amazon_price" class="block text-sm text-slate-300 mb-1">Amazon Price ($)</label>
        <input id="amazon_price" name="amazon_price" type="number" min="0" step="0.01" required value="{{ old('amazon_price', $listing?->amazon_price) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('amazon_price') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="ebay_fee" class="block text-sm text-slate-300 mb-1">eBay Fee ($)</label>
        <input id="ebay_fee" name="ebay_fee" type="number" min="0" step="0.01" value="{{ old('ebay_fee', $listing?->ebay_fee ?? 0) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('ebay_fee') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="status" class="block text-sm text-slate-300 mb-1">Status</label>
        <select id="status" name="status" required
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
            @foreach(['research', 'listed', 'active', 'sold', 'paused', 'archived'] as $state)
                <option value="{{ $state }}" @selected(old('status', $listing?->status ?? 'research') === $state)>{{ ucfirst($state) }}</option>
            @endforeach
        </select>
        @error('status') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="listed_on" class="block text-sm text-slate-300 mb-1">Listed On</label>
        <input id="listed_on" name="listed_on" type="date" value="{{ old('listed_on', optional($listing?->listed_on)->format('Y-m-d')) }}"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">
        @error('listed_on') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2">
        <label for="notes" class="block text-sm text-slate-300 mb-1">Notes</label>
        <textarea id="notes" name="notes" rows="4"
            class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300" placeholder="Any details or strategy note...">{{ old('notes', $listing?->notes) }}</textarea>
        @error('notes') <p class="mt-1 text-sm text-rose-300">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('listings.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</a>
    <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
        {{ $submitLabel ?? 'Save Listing' }}
    </button>
</div>
