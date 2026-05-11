<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Workspace</p>
                <h2 class="font-display text-2xl leading-tight text-white">Listings</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('listings.create') }}" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">New Listing</a>
            </div>
        </div>
    </x-slot>

    {{-- Copy-to-users modal (admin only) --}}
    <div
        @if(auth()->user()->isAdmin() && $clients->isNotEmpty())
        x-data="{
            open: false,
            listingId: null,
            listingUrl: '',
            selected: [],
            toggle(id) {
                if (this.selected.includes(id)) {
                    this.selected = this.selected.filter(i => i !== id);
                } else {
                    this.selected.push(id);
                }
            },
            selectAll(ids) { this.selected = [...ids]; },
            clearAll() { this.selected = []; },
            openFor(id, url) {
                this.listingId = id;
                this.listingUrl = url;
                this.selected = [];
                this.open = true;
            }
        }"
        @open-copy-modal.window="openFor($event.detail.id, $event.detail.url)"
        @endif
    >
        <!-- Backdrop -->
        <div x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm" @click="open = false" style="display:none;"></div>

        <!-- Modal panel -->
        <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
            <div class="glass-card w-full max-w-lg p-6 space-y-5" @click.stop>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-display text-lg text-white">Import Listing to Users</h3>
                        <p class="text-xs text-slate-400 mt-0.5 truncate max-w-[340px]" x-text="listingUrl"></p>
                    </div>
                    <button @click="open = false" class="text-slate-400 hover:text-white shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <!-- Select all / clear -->
                <div class="flex gap-3 text-xs">
                    <button type="button" @click="selectAll([{{ $clients->pluck('id')->join(',') }}])" class="text-cyan-300 hover:text-cyan-100">Select all</button>
                    <span class="text-slate-600">·</span>
                    <button type="button" @click="clearAll()" class="text-slate-400 hover:text-slate-200">Clear</button>
                    <span class="text-slate-600">·</span>
                    <span class="text-slate-400"><span x-text="selected.length"></span> selected</span>
                </div>

                <!-- User checkboxes -->
                <div class="max-h-60 overflow-y-auto space-y-1 pr-1">
                    @foreach($clients as $client)
                    <label class="flex items-center gap-3 rounded-lg px-3 py-2 cursor-pointer hover:bg-white/5 transition"
                           :class="selected.includes({{ $client->id }}) ? 'bg-cyan-400/10 border border-cyan-400/20' : 'border border-transparent'">
                        <input type="checkbox"
                               :checked="selected.includes({{ $client->id }})"
                               @change="toggle({{ $client->id }})"
                               class="rounded border-white/20 bg-slate-900 text-cyan-400 focus:ring-cyan-400">
                        <div>
                            <p class="text-sm text-slate-100">{{ $client->name }}</p>
                            <p class="text-xs text-slate-400">{{ $client->email }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>

                <!-- Submit -->
                <form method="POST" :action="`/admin/listings/${listingId}/copy`">
                    @csrf
                    <template x-for="uid in selected" :key="uid">
                        <input type="hidden" name="user_ids[]" :value="uid">
                    </template>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</button>
                        <button type="submit" :disabled="selected.length === 0"
                            class="rounded-lg bg-cyan-400/90 px-5 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition disabled:opacity-40 disabled:cursor-not-allowed">
                            Import to <span x-text="selected.length"></span> user(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>

    {{-- Table + pagination (inside same x-data scope as modal) --}}
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-300/25 bg-emerald-400/15 px-4 py-3 text-emerald-100 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="glass-card p-5">
                <form method="GET" action="{{ route('listings.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search URLs or notes"
                        class="md:col-span-2 rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">

                    <select name="status" class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                        <option value="">All statuses</option>
                        @foreach($statuses as $availableStatus)
                            <option value="{{ $availableStatus }}" @selected($status === $availableStatus)>{{ ucfirst($availableStatus) }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Filter</button>
                </form>
            </div>

            <div class="glass-card p-5 overflow-x-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="text-slate-300 border-b border-white/10">
                        <tr>
                            <th class="text-left py-3 pe-3">eBay</th>
                            <th class="text-left py-3 pe-3">Amazon</th>
                            <th class="text-right py-3 pe-3">Sell</th>
                            <th class="text-right py-3 pe-3">Buy</th>
                            <th class="text-right py-3 pe-3">Fee</th>
                            <th class="text-right py-3 pe-3">Profit</th>
                            <th class="text-right py-3 pe-3">ROI</th>
                            <th class="text-left py-3 pe-3">Status</th>
                            <th class="text-right py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($listings as $listing)
                            <tr class="border-b border-white/5 align-top">
                                <td class="py-3 pe-3 max-w-[200px] truncate text-slate-200">
                                    <a href="{{ $listing->ebay_url }}" target="_blank" rel="noreferrer" class="hover:text-cyan-200">{{ $listing->ebay_url }}</a>
                                </td>
                                <td class="py-3 pe-3 max-w-[200px] truncate text-slate-300">
                                    @if($listing->amazon_url)
                                        <a href="{{ $listing->amazon_url }}" target="_blank" rel="noreferrer" class="hover:text-cyan-200">{{ $listing->amazon_url }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-3 pe-3 text-right text-slate-200">${{ number_format($listing->ebay_price, 2) }}</td>
                                <td class="py-3 pe-3 text-right text-slate-200">${{ number_format($listing->amazon_price, 2) }}</td>
                                <td class="py-3 pe-3 text-right text-slate-200">${{ number_format($listing->ebay_fee, 2) }}</td>
                                <td class="py-3 pe-3 text-right {{ $listing->profit >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">${{ number_format($listing->profit, 2) }}</td>
                                <td class="py-3 pe-3 text-right text-slate-200">{{ number_format($listing->roi, 2) }}%</td>
                                <td class="py-3 pe-3 text-slate-200">{{ ucfirst($listing->status) }}</td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        @if(auth()->user()->isAdmin() && $clients->isNotEmpty())
                                        <button type="button"
                                            @click="$dispatch('open-copy-modal', { id: {{ $listing->id }}, url: '{{ addslashes($listing->ebay_url) }}' })"
                                            class="text-amber-300 hover:text-amber-100">Import</button>
                                        @endif
                                        <a href="{{ route('listings.edit', $listing) }}" class="text-cyan-300 hover:text-cyan-100">Edit</a>
                                        <form method="POST" action="{{ route('listings.destroy', $listing) }}" onsubmit="return confirm('Delete this listing?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-300 hover:text-rose-200">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-10 text-center text-slate-400">No listings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $listings->links() }}
            </div>
        </div>
    </div>
</div>
</x-app-layout>
