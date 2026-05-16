@php
    $listingStoreErrors = $errors->getBag('listingStore');
    $orderStoreErrors = $errors->getBag('orderStore');
    $listingItems = collect($listings->items());
    $editingListingId = $listingItems->first(fn ($listing) => $errors->getBag('listingUpdate'.$listing->id)->any())?->id;
    $visibleListingIds = $listingItems->pluck('id')->all();
    $ebayUrls = $listingItems->mapWithKeys(fn ($listing) => [$listing->id => $listing->ebay_url])->filter()->all();
    $amazonUrls = $listingItems->mapWithKeys(fn ($listing) => [$listing->id => $listing->amazon_url])->filter()->all();
    $priceAdjustments = $listingItems->mapWithKeys(fn ($listing) => [$listing->id => [
        'basePrice' => (float) $listing->ebay_price,
        'amazonPrice' => (float) $listing->amazon_price,
        'ebayFee' => (float) $listing->ebay_fee,
        'currencySymbol' => $listing->marketplaceCurrencySymbol(),
        'percentage' => (string) ($listing->adjustment_percentage ?? 2.1),
        'initialPercentage' => (string) ($listing->adjustment_percentage ?? 2.1),
    ]])->all();
    $clientIds = $clients->pluck('id')->all();
    $clientOptions = $clients->map(fn ($client) => ['id' => (string) $client->id, 'name' => $client->name])->values()->all();
    $returnTo = url()->full();
    $defaultOrderDate = old('order_date', now()->format('Y-m-d'));
    $orderDraft = [
        'user_id' => old('user_id', $clients->first()?->id),
        'order_date' => $defaultOrderDate,
        'buyer_name' => old('buyer_name', ''),
        'ebay_order_no' => old('ebay_order_no', ''),
        'amazon_order_no' => old('amazon_order_no', ''),
        'status' => old('status', 'Order Placed'),
        'amazon_cost' => old('amazon_cost', ''),
        'ebay_receipts' => old('ebay_receipts', ''),
        'note' => old('note', ''),
    ];
    $listingTableConfig = [
        'editingId' => $editingListingId,
        'createOpen' => $listingStoreErrors->any(),
        'orderCreateOpen' => $orderStoreErrors->any(),
        'orderDraft' => $orderDraft,
        'visibleIds' => $visibleListingIds,
        'ebayUrls' => $ebayUrls,
        'amazonUrls' => $amazonUrls,
        'priceAdjustments' => $priceAdjustments,
        'clientIds' => $clientIds,
        'clientOptions' => $clientOptions,
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-300/80">Workspace</p>
            <h2 class="font-display text-2xl leading-tight text-white">Listings</h2>
        </div>
    </x-slot>

    <div class="py-8" x-data="listingsTable({{ \Illuminate\Support\Js::from($listingTableConfig) }})">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-5">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-300/25 bg-emerald-400/15 px-4 py-3 text-emerald-100 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(auth()->user()->isAdmin() && $clients->isNotEmpty())
                <div x-cloak x-show="copyModalOpen" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm" @click="closeCopyModal()"></div>

                <div x-cloak x-show="copyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="glass-card w-full max-w-lg p-6 space-y-5" @click.stop>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-display text-lg text-white">Import Listing to Users</h3>
                                <p class="mt-0.5 max-w-[340px] truncate text-xs text-slate-400" x-text="copyListingUrl"></p>
                            </div>
                            <button type="button" @click="closeCopyModal()" class="shrink-0 text-slate-400 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex gap-3 text-xs">
                            <button type="button" @click="selectAllClients()" class="text-cyan-300 hover:text-cyan-100">Select all</button>
                            <span class="text-slate-600">·</span>
                            <button type="button" @click="clearClientSelection()" class="text-slate-400 hover:text-slate-200">Clear</button>
                            <span class="text-slate-600">·</span>
                            <span class="text-slate-400"><span x-text="copySelectedUsers.length"></span> selected</span>
                        </div>

                        <div class="max-h-60 space-y-1 overflow-y-auto pr-1">
                            @foreach($clients as $client)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition hover:bg-white/5" :class="copySelectedUsers.includes({{ $client->id }}) ? 'border border-cyan-400/20 bg-cyan-400/10' : 'border border-transparent'">
                                    <input type="checkbox" class="rounded border-white/20 bg-slate-900 text-cyan-400 focus:ring-cyan-400" :checked="copySelectedUsers.includes({{ $client->id }})" @change="toggleClientSelection({{ $client->id }})">
                                    <div>
                                        <p class="text-sm text-slate-100">{{ $client->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $client->email }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <form method="POST" :action="`/admin/listings/${copyListingId}/copy`">
                            @csrf
                            <template x-for="uid in copySelectedUsers" :key="`copy-${uid}`">
                                <input type="hidden" name="user_ids[]" :value="uid">
                            </template>
                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="closeCopyModal()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</button>
                                <button type="submit" :disabled="copySelectedUsers.length === 0" class="rounded-lg bg-cyan-400/90 px-5 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition disabled:cursor-not-allowed disabled:opacity-40">
                                    Import to <span x-text="copySelectedUsers.length"></span> user(s)
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="glass-card p-5 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-white">Manage listings inline</p>
                        <p class="text-xs text-slate-400">Click a row to edit it. Add new listings directly at the top of the table.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="startOrderCreate()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">
                            + Add Order
                        </button>
                        <button type="button" @click="startCreate()" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">
                            + Add Listing
                        </button>
                    </div>
                </div>

                <form method="GET" action="{{ route('listings.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search URLs or notes"
                        class="md:col-span-2 rounded-lg border border-white/15 bg-slate-900/70 text-white placeholder-slate-400 focus:border-cyan-300 focus:ring-cyan-300">

                    <select name="status" class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                        <option value="">All statuses</option>
                        @foreach($statuses as $availableStatus)
                            <option value="{{ $availableStatus }}" @selected($status === $availableStatus)>{{ ucfirst($availableStatus) }}</option>
                        @endforeach
                    </select>

                    <select name="per_page" class="rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                        @foreach([25, 50, 75, 100] as $pageSize)
                            <option value="{{ $pageSize }}" @selected($perPage === $pageSize)>{{ $pageSize }} per page</option>
                        @endforeach
                    </select>

                    <button type="submit" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Filter</button>
                </form>

                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Selected listings</label>
                        <p class="rounded-lg border border-white/10 bg-slate-900/50 px-3 py-2 text-sm text-slate-200"><span x-text="selected.length"></span> selected on this page</p>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                    <button type="button" @click="openSelectedEbayUrls()" :disabled="selectedEbayUrls().length === 0" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Open eBay URLs
                    </button>
                    <button type="button" @click="openSelectedAmazonUrls()" :disabled="selectedAmazonUrls().length === 0" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Open Amazon URLs
                    </button>
                    </div>
                    <button type="button" @click="toggleAll()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">
                        <span x-text="allSelected() ? 'Clear Page Selection' : 'Select Page'"></span>
                    </button>
                </div>
            </div>

            <div x-cloak x-show="orderCreateOpen" class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm" @click="cancelOrderCreate()"></div>

            <div x-cloak x-show="orderCreateOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="glass-card w-full max-w-5xl p-6 space-y-5" @click.stop>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-display text-lg text-white">Add Order</h3>
                            <p class="mt-1 text-xs text-slate-400">Create an order for any user without leaving the listings page.</p>
                        </div>
                        <button type="button" @click="cancelOrderCreate()" class="shrink-0 text-slate-400 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('orders.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div>
                                <label for="listing_order_user_search" class="block text-xs text-slate-400 mb-1">Search User</label>
                                <input id="listing_order_user_search" type="text" x-model="userSearch" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300" placeholder="Search client by full name">
                            </div>
                            <div>
                                <label for="listing_order_user_id" class="block text-xs text-slate-400 mb-1">User</label>
                                <select id="listing_order_user_id" name="user_id" x-model="orderDraft.user_id" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                    <template x-for="client in filteredClientOptions()" :key="client.id">
                                        <option :value="client.id" x-text="client.name"></option>
                                    </template>
                                </select>
                                @if($orderStoreErrors->has('user_id')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('user_id') }}</p> @endif
                            </div>
                            <div>
                                <label for="listing_order_date" class="block text-xs text-slate-400 mb-1">Order Date</label>
                                <input id="listing_order_date" name="order_date" type="date" required x-model="orderDraft.order_date" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                @if($orderStoreErrors->has('order_date')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('order_date') }}</p> @endif
                            </div>
                            <div>
                                <label for="listing_buyer_name" class="block text-xs text-slate-400 mb-1">Buyer Name</label>
                                <input id="listing_buyer_name" name="buyer_name" type="text" required x-model="orderDraft.buyer_name" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                @if($orderStoreErrors->has('buyer_name')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('buyer_name') }}</p> @endif
                            </div>
                            <div>
                                <label for="listing_ebay_order_no" class="block text-xs text-slate-400 mb-1">eBay Order No</label>
                                <input id="listing_ebay_order_no" name="ebay_order_no" type="text" x-model="orderDraft.ebay_order_no" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                @if($orderStoreErrors->has('ebay_order_no')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('ebay_order_no') }}</p> @endif
                            </div>
                            <div>
                                <label for="listing_amazon_order_no" class="block text-xs text-slate-400 mb-1">Amazon Order No</label>
                                <input id="listing_amazon_order_no" name="amazon_order_no" type="text" x-model="orderDraft.amazon_order_no" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                @if($orderStoreErrors->has('amazon_order_no')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('amazon_order_no') }}</p> @endif
                            </div>
                            <div>
                                <label for="listing_order_status" class="block text-xs text-slate-400 mb-1">Status</label>
                                <select id="listing_order_status" name="status" x-model="orderDraft.status" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                    @foreach(\App\Models\Order::STATUSES as $s)
                                        <option value="{{ $s }}">{{ $s }}</option>
                                    @endforeach
                                </select>
                                @if($orderStoreErrors->has('status')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('status') }}</p> @endif
                            </div>
                            <div>
                                <label for="listing_amazon_cost" class="block text-xs text-slate-400 mb-1">Amazon Cost</label>
                                <input id="listing_amazon_cost" name="amazon_cost" type="number" min="0" step="0.01" required x-model="orderDraft.amazon_cost" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                @if($orderStoreErrors->has('amazon_cost')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('amazon_cost') }}</p> @endif
                            </div>
                            <div>
                                <label for="listing_ebay_receipts" class="block text-xs text-slate-400 mb-1">eBay Receipts</label>
                                <input id="listing_ebay_receipts" name="ebay_receipts" type="number" min="0" step="0.01" required x-model="orderDraft.ebay_receipts" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                @if($orderStoreErrors->has('ebay_receipts')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('ebay_receipts') }}</p> @endif
                            </div>
                            <div class="md:col-span-2 xl:col-span-4">
                                <label for="listing_order_note" class="block text-xs text-slate-400 mb-1">Note</label>
                                <input id="listing_order_note" name="note" type="text" x-model="orderDraft.note" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                @if($orderStoreErrors->has('note')) <p class="mt-1 text-xs text-rose-300">{{ $orderStoreErrors->first('note') }}</p> @endif
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="cancelOrderCreate()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</button>
                            <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">Save Order</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="glass-card p-5 overflow-x-auto">
                <table class="w-full min-w-[1250px] text-sm">
                    <thead class="text-slate-300 border-b border-white/10">
                        <tr>
                            <th class="py-3 pe-3 w-10 text-center">
                                <input type="checkbox" class="rounded border-white/20 bg-slate-900 text-cyan-400 focus:ring-cyan-400" :checked="allSelected()" @click.stop="toggleAll()">
                            </th>
                            <th class="text-left py-3 pe-3">Image</th>
                            <th class="text-left py-3 pe-3">eBay</th>
                            <th class="text-left py-3 pe-3">Amazon</th>
                            <th class="text-right py-3 pe-3">Sell</th>
                            <th class="text-right py-3 pe-3">Buy</th>
                            <th class="text-right py-3 pe-3">Fee</th>
                            <th class="text-right py-3 pe-3">Profit</th>
                            <th class="text-right py-3 pe-3">ROI</th>
                            <th class="text-left py-3 pe-3">Status</th>
                            <th class="text-right py-3 pe-3">Adj %</th>
                            <th class="text-right py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr x-cloak x-show="createOpen" class="border-b border-cyan-400/20 bg-cyan-400/5">
                            <td colspan="12" class="px-4 py-5">
                                <form method="POST" action="{{ route('listings.store') }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                        <div class="xl:col-span-2">
                                            <label for="new_image_url" class="block text-xs text-slate-400 mb-1">Image URL</label>
                                            <input id="new_image_url" name="image_url" type="url" value="{{ old('image_url') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300" placeholder="https://.../image.jpg">
                                            @if($listingStoreErrors->has('image_url')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('image_url') }}</p> @endif
                                        </div>
                                        <div class="xl:col-span-2">
                                            <label for="new_ebay_url" class="block text-xs text-slate-400 mb-1">eBay URL</label>
                                            <input id="new_ebay_url" name="ebay_url" type="url" required value="{{ old('ebay_url') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300" placeholder="https://www.ebay...">
                                            @if($listingStoreErrors->has('ebay_url')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('ebay_url') }}</p> @endif
                                        </div>
                                        <div class="xl:col-span-2">
                                            <label for="new_amazon_url" class="block text-xs text-slate-400 mb-1">Amazon URL</label>
                                            <input id="new_amazon_url" name="amazon_url" type="url" value="{{ old('amazon_url') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300" placeholder="https://www.amazon...">
                                            @if($listingStoreErrors->has('amazon_url')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('amazon_url') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_ebay_price" class="block text-xs text-slate-400 mb-1">eBay Price</label>
                                            <input id="new_ebay_price" name="ebay_price" type="number" min="0" step="0.01" required value="{{ old('ebay_price') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($listingStoreErrors->has('ebay_price')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('ebay_price') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_amazon_price" class="block text-xs text-slate-400 mb-1">Amazon Price</label>
                                            <input id="new_amazon_price" name="amazon_price" type="number" min="0" step="0.01" required value="{{ old('amazon_price') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($listingStoreErrors->has('amazon_price')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('amazon_price') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_ebay_fee" class="block text-xs text-slate-400 mb-1">eBay Fee</label>
                                            <input id="new_ebay_fee" name="ebay_fee" type="number" min="0" step="0.01" value="{{ old('ebay_fee', 0) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($listingStoreErrors->has('ebay_fee')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('ebay_fee') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_status" class="block text-xs text-slate-400 mb-1">Status</label>
                                            <select id="new_status" name="status" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @foreach(['research', 'listed', 'active', 'sold', 'paused', 'archived'] as $state)
                                                    <option value="{{ $state }}" @selected(old('status', 'research') === $state)>{{ ucfirst($state) }}</option>
                                                @endforeach
                                            </select>
                                            @if($listingStoreErrors->has('status')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('status') }}</p> @endif
                                        </div>
                                        <div>
                                            <label for="new_listed_on" class="block text-xs text-slate-400 mb-1">Listed On</label>
                                            <input id="new_listed_on" name="listed_on" type="date" value="{{ old('listed_on') }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                            @if($listingStoreErrors->has('listed_on')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('listed_on') }}</p> @endif
                                        </div>
                                        <div class="md:col-span-2 xl:col-span-4">
                                            <label for="new_notes" class="block text-xs text-slate-400 mb-1">Notes</label>
                                            <textarea id="new_notes" name="notes" rows="3" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">{{ old('notes') }}</textarea>
                                            @if($listingStoreErrors->has('notes')) <p class="mt-1 text-xs text-rose-300">{{ $listingStoreErrors->first('notes') }}</p> @endif
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-3">
                                        <button type="button" @click="cancelCreate()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</button>
                                        <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">Save Listing</button>
                                    </div>
                                </form>
                            </td>
                        </tr>

                        @forelse($listings as $listing)
                            @php $listingUpdateErrors = $errors->getBag('listingUpdate'.$listing->id); @endphp
                            <tr x-show="editingId !== {{ $listing->id }}" class="border-b border-white/5 align-top cursor-pointer hover:bg-white/[.03] transition" @click="startEdit({{ $listing->id }})">
                                <td class="py-3 pe-3 text-center" @click.stop>
                                    <input type="checkbox" class="rounded border-white/20 bg-slate-900 text-cyan-400 focus:ring-cyan-400" :checked="selected.includes({{ $listing->id }})" @change="toggleSelection({{ $listing->id }})">
                                </td>
                                <td class="py-3 pe-3">
                                    @if($thumbnailUrl = $listing->amazonThumbnailUrl())
                                        <img src="{{ $thumbnailUrl }}" alt="Product image" class="h-12 w-12 rounded-lg bg-slate-900 object-cover border border-white/10">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-white/15 text-[10px] uppercase tracking-[0.2em] text-slate-500">N/A</div>
                                    @endif
                                </td>
                                <td class="py-3 pe-3 max-w-[220px] text-slate-200">
                                    <a href="{{ $listing->ebay_url }}" target="_blank" rel="noreferrer" class="block truncate hover:text-cyan-200" @click.stop>{{ $listing->ebay_url }}</a>
                                    @if($listing->notes)
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $listing->notes }}</p>
                                    @endif
                                </td>
                                <td class="py-3 pe-3 max-w-[220px] text-slate-300">
                                    @if($listing->amazon_url)
                                        <a href="{{ $listing->amazon_url }}" target="_blank" rel="noreferrer" class="block truncate hover:text-cyan-200" @click.stop>{{ $listing->amazon_url }}</a>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="py-3 pe-3 text-right text-slate-200" @click.stop>
                                    <span class="font-semibold" x-text="listingCurrencySymbol({{ $listing->id }}) + previewListingPrice({{ $listing->id }}, {{ number_format($listing->ebay_price, 2, '.', '') }})"></span>
                                </td>
                                <td class="py-3 pe-3 text-right text-slate-200" x-text="listingCurrencySymbol({{ $listing->id }}) + previewListingBuy({{ $listing->id }}, {{ number_format($listing->amazon_price, 2, '.', '') }})"></td>
                                <td class="py-3 pe-3 text-right text-slate-200" x-text="listingCurrencySymbol({{ $listing->id }}) + previewListingFee({{ $listing->id }}, {{ number_format($listing->ebay_fee, 2, '.', '') }})"></td>
                                <td class="py-3 pe-3 text-right" :class="priceAdjustmentProfitClass({{ $listing->id }}, {{ number_format($listing->profit, 2, '.', '') }})" x-text="listingCurrencySymbol({{ $listing->id }}) + previewListingProfit({{ $listing->id }}, {{ number_format($listing->profit, 2, '.', '') }})"></td>
                                <td class="py-3 pe-3 text-right text-slate-200" x-text="previewListingRoi({{ $listing->id }}, {{ number_format($listing->roi, 2, '.', '') }}) + '%' "></td>
                                <td class="py-3 pe-3 text-slate-200">{{ ucfirst($listing->status) }}</td>
                                <td class="py-3 pe-3 text-right" @click.stop>
                                    <form method="POST" action="{{ route('listings.adjust-price', $listing) }}" class="flex items-end justify-end gap-2" @submit.stop>
                                        @csrf
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        <input type="hidden" name="percentage" :value="priceAdjustmentValue({{ $listing->id }})">
                                        <input id="listing_percentage_{{ $listing->id }}" type="number" step="0.01" value="{{ number_format((float) ($listing->adjustment_percentage ?? 2.1), 2, '.', '') }}" @input="updatePriceAdjustment({{ $listing->id }}, $event.target.value)" class="w-20 rounded-lg border border-white/15 bg-slate-900/70 px-2 py-1.5 text-right text-xs text-white focus:border-cyan-300 focus:ring-cyan-300">
                                        <button x-cloak x-show="isPriceAdjustmentDirty({{ $listing->id }})" type="submit" :disabled="!canSavePriceAdjustment({{ $listing->id }})" class="rounded-lg bg-amber-300/90 px-3 py-1.5 text-xs font-semibold text-slate-950 hover:bg-amber-200 transition disabled:opacity-40 disabled:cursor-not-allowed">
                                            Save
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-3">
                                        @if(auth()->user()->isAdmin() && $clients->isNotEmpty())
                                            <button type="button" @click="openCopyModal({{ $listing->id }}, @js($listing->ebay_url))" class="text-amber-300 hover:text-amber-100">Import</button>
                                        @endif
                                        <button type="button" @click="startEdit({{ $listing->id }})" class="text-cyan-300 hover:text-cyan-100">Edit</button>
                                        <form method="POST" action="{{ route('listings.destroy', $listing) }}" onsubmit="return confirm('Delete this listing?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                            <button type="submit" class="text-rose-300 hover:text-rose-200">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-cloak x-show="editingId === {{ $listing->id }}" class="border-b border-cyan-400/20 bg-cyan-400/5">
                                <td colspan="12" class="px-4 py-5">
                                    <form method="POST" action="{{ route('listings.update', $listing) }}" class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                                            <div class="xl:col-span-2">
                                                <label for="image_url_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">Image URL</label>
                                                <input id="image_url_{{ $listing->id }}" name="image_url" type="url" value="{{ old('image_url', $listing->image_url) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300" placeholder="https://.../image.jpg">
                                                @if($listingUpdateErrors->has('image_url')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('image_url') }}</p> @endif
                                            </div>
                                            <div class="xl:col-span-2">
                                                <label for="ebay_url_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">eBay URL</label>
                                                <input id="ebay_url_{{ $listing->id }}" name="ebay_url" type="url" required value="{{ old('ebay_url', $listing->ebay_url) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($listingUpdateErrors->has('ebay_url')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('ebay_url') }}</p> @endif
                                            </div>
                                            <div class="xl:col-span-2">
                                                <label for="amazon_url_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">Amazon URL</label>
                                                <input id="amazon_url_{{ $listing->id }}" name="amazon_url" type="url" value="{{ old('amazon_url', $listing->amazon_url) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($listingUpdateErrors->has('amazon_url')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('amazon_url') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="ebay_price_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">eBay Price</label>
                                                <input id="ebay_price_{{ $listing->id }}" name="ebay_price" type="number" min="0" step="0.01" required value="{{ old('ebay_price', $listing->ebay_price) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($listingUpdateErrors->has('ebay_price')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('ebay_price') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="amazon_price_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">Amazon Price</label>
                                                <input id="amazon_price_{{ $listing->id }}" name="amazon_price" type="number" min="0" step="0.01" required value="{{ old('amazon_price', $listing->amazon_price) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($listingUpdateErrors->has('amazon_price')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('amazon_price') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="ebay_fee_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">eBay Fee</label>
                                                <input id="ebay_fee_{{ $listing->id }}" name="ebay_fee" type="number" min="0" step="0.01" value="{{ old('ebay_fee', $listing->ebay_fee) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($listingUpdateErrors->has('ebay_fee')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('ebay_fee') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="status_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">Status</label>
                                                <select id="status_{{ $listing->id }}" name="status" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                    @foreach(['research', 'listed', 'active', 'sold', 'paused', 'archived'] as $state)
                                                        <option value="{{ $state }}" @selected(old('status', $listing->status) === $state)>{{ ucfirst($state) }}</option>
                                                    @endforeach
                                                </select>
                                                @if($listingUpdateErrors->has('status')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('status') }}</p> @endif
                                            </div>
                                            <div>
                                                <label for="listed_on_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">Listed On</label>
                                                <input id="listed_on_{{ $listing->id }}" name="listed_on" type="date" value="{{ old('listed_on', optional($listing->listed_on)->format('Y-m-d')) }}" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                @if($listingUpdateErrors->has('listed_on')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('listed_on') }}</p> @endif
                                            </div>
                                            <div class="md:col-span-2 xl:col-span-4">
                                                <label for="notes_{{ $listing->id }}" class="block text-xs text-slate-400 mb-1">Notes</label>
                                                <textarea id="notes_{{ $listing->id }}" name="notes" rows="3" class="w-full rounded-lg border border-white/15 bg-slate-900/70 text-white focus:border-cyan-300 focus:ring-cyan-300">{{ old('notes', $listing->notes) }}</textarea>
                                                @if($listingUpdateErrors->has('notes')) <p class="mt-1 text-xs text-rose-300">{{ $listingUpdateErrors->first('notes') }}</p> @endif
                                            </div>
                                        </div>
                                        <div class="flex justify-end gap-3">
                                            <button type="button" @click="cancelEdit()" class="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10 transition">Cancel</button>
                                            <button type="submit" class="rounded-lg bg-cyan-400/90 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-300 transition">Save Changes</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="py-10 text-center text-slate-400">No listings found.</td>
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
</x-app-layout>
