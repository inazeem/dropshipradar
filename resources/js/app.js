import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
	Alpine.data('listingsTable', (config = {}) => ({
		editingId: config.editingId ?? null,
		createOpen: config.createOpen ?? false,
		orderCreateOpen: config.orderCreateOpen ?? false,
		orderDraft: { ...(config.orderDraft ?? {}) },
		userSearch: '',
		selected: [],
		percentage: '',
		visibleIds: config.visibleIds ?? [],
		ebayUrls: config.ebayUrls ?? {},
		amazonUrls: config.amazonUrls ?? {},
		priceAdjustments: config.priceAdjustments ?? {},
		clientOptions: config.clientOptions ?? [],
		copyModalOpen: false,
		copyListingId: null,
		copyListingUrl: '',
		copySelectedUsers: [],
		clientIds: config.clientIds ?? [],

		buildOrderDraft(prefill = {}) {
			return {
				...(config.orderDraft ?? {}),
				...prefill,
			};
		},

		startCreate() {
			this.createOpen = true;
			this.editingId = null;
			this.orderCreateOpen = false;
		},

		cancelCreate() {
			this.createOpen = false;
		},

		startOrderCreate(prefill = {}) {
			this.orderCreateOpen = true;
			this.createOpen = false;
			this.editingId = null;
			this.orderDraft = this.buildOrderDraft(prefill);
			this.userSearch = '';
		},

		cancelOrderCreate() {
			this.orderCreateOpen = false;
			this.orderDraft = this.buildOrderDraft();
			this.userSearch = '';
		},

		filteredClientOptions() {
			const search = this.userSearch.trim().toLowerCase();

			if (!search) {
				return this.clientOptions;
			}

			return this.clientOptions.filter((client) => client.name.toLowerCase().includes(search));
		},

		startEdit(id) {
			this.editingId = id;
			this.createOpen = false;
			this.orderCreateOpen = false;
		},

		cancelEdit() {
			this.editingId = null;
		},

		toggleSelection(id) {
			if (this.selected.includes(id)) {
				this.selected = this.selected.filter((value) => value !== id);
				return;
			}

			this.selected = [...this.selected, id];
		},

		allSelected() {
			return this.visibleIds.length > 0 && this.visibleIds.every((id) => this.selected.includes(id));
		},

		toggleAll() {
			this.selected = this.allSelected() ? [] : [...this.visibleIds];
		},

		selectedAmazonUrls() {
			return [...new Set(this.selected
				.map((id) => this.amazonUrls[id])
				.filter((value) => Boolean(value)))];
		},

		selectedEbayUrls() {
			return [...new Set(this.selected
				.map((id) => this.ebayUrls[id])
				.filter((value) => Boolean(value)))];
		},

		updatePriceAdjustment(id, value) {
			if (!this.priceAdjustments[id]) {
				return;
			}

			this.priceAdjustments[id].percentage = value;
		},

		priceAdjustmentValue(id) {
			return this.priceAdjustments[id]?.percentage ?? '2.1';
		},

		listingCurrencySymbol(id) {
			return this.priceAdjustments[id]?.currencySymbol ?? '$';
		},

		isPriceAdjustmentDirty(id) {
			const current = String(this.priceAdjustmentValue(id)).trim();
			const initial = String(this.priceAdjustments[id]?.initialPercentage ?? '2.1').trim();

			return current !== initial;
		},

		previewListingPrice(id, fallback) {
			const basePrice = Number(this.priceAdjustments[id]?.basePrice ?? fallback);
			const percentage = Number.parseFloat(this.priceAdjustmentValue(id));

			if (!Number.isFinite(basePrice) || !Number.isFinite(percentage)) {
				return Number(fallback).toFixed(2);
			}

			if (!this.isPriceAdjustmentDirty(id)) {
				return Number(fallback).toFixed(2);
			}

			return (basePrice * (1 + (percentage / 100))).toFixed(2);
		},

		previewListingBuy(id, fallback) {
			const amazonPrice = Number(this.priceAdjustments[id]?.amazonPrice ?? fallback);

			return Number.isFinite(amazonPrice) ? amazonPrice.toFixed(2) : Number(fallback).toFixed(2);
		},

		previewListingFee(id, fallback) {
			const ebayFee = Number(this.priceAdjustments[id]?.ebayFee ?? fallback);

			return Number.isFinite(ebayFee) ? ebayFee.toFixed(2) : Number(fallback).toFixed(2);
		},

		previewListingProfit(id, fallback) {
			const sellPrice = Number.parseFloat(this.previewListingPrice(id, this.priceAdjustments[id]?.basePrice ?? 0));
			const amazonPrice = Number(this.priceAdjustments[id]?.amazonPrice ?? 0);
			const ebayFee = Number(this.priceAdjustments[id]?.ebayFee ?? 0);

			if (!Number.isFinite(sellPrice) || !Number.isFinite(amazonPrice) || !Number.isFinite(ebayFee)) {
				return Number(fallback).toFixed(2);
			}

			return (sellPrice - amazonPrice - ebayFee).toFixed(2);
		},

		previewListingRoi(id, fallback) {
			const profit = Number.parseFloat(this.previewListingProfit(id, 0));
			const amazonPrice = Number(this.priceAdjustments[id]?.amazonPrice ?? 0);

			if (!Number.isFinite(profit) || !Number.isFinite(amazonPrice) || amazonPrice <= 0) {
				return Number(fallback).toFixed(2);
			}

			return ((profit / amazonPrice) * 100).toFixed(2);
		},

		priceAdjustmentProfitClass(id, fallback) {
			return Number.parseFloat(this.previewListingProfit(id, fallback)) >= 0 ? 'text-emerald-300' : 'text-rose-300';
		},

		canSavePriceAdjustment(id) {
			return Number.isFinite(Number.parseFloat(this.priceAdjustmentValue(id)));
		},

		openUrlsWithDelay(urls, title) {
			if (urls.length === 0) {
				return;
			}

			const delayMs = 1200;

			const helper = window.open('', '_blank');

			if (!helper) {
				return;
			}

			helper.opener = null;
			helper.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>${title}</title>
</head>
<body style="font-family: Arial, sans-serif; background: #020617; color: #e2e8f0; padding: 24px;">
<p id="status">Preparing to open ${urls.length} URL(s)...</p>
<script>
const urls = ${JSON.stringify(urls)};
const delayMs = ${delayMs};
const status = document.getElementById('status');
let index = 0;

const openNext = () => {
	if (index >= urls.length) {
		status.textContent = 'Finished opening ' + urls.length + ' URL(s).';
		window.setTimeout(() => window.close(), 900);
		return;
	}

	const url = urls[index];
	const opened = window.open(url, '_blank', 'noopener,noreferrer');
	const current = index + 1;

	status.textContent = opened
		? 'Opening ' + current + ' of ' + urls.length + '...'
		: 'Browser blocked URL ' + current + ' of ' + urls.length + '.';

	index += 1;
	window.setTimeout(openNext, delayMs);
};

openNext();
</script>
</body>
</html>`);
			helper.document.close();
		},

		openSelectedAmazonUrls() {
			this.openUrlsWithDelay(this.selectedAmazonUrls(), 'Opening Amazon URLs');
		},

		openSelectedEbayUrls() {
			this.openUrlsWithDelay(this.selectedEbayUrls(), 'Opening eBay URLs');
		},

		openCopyModal(id, url) {
			this.copyModalOpen = true;
			this.copyListingId = id;
			this.copyListingUrl = url;
			this.copySelectedUsers = [];
		},

		closeCopyModal() {
			this.copyModalOpen = false;
			this.copyListingId = null;
			this.copyListingUrl = '';
			this.copySelectedUsers = [];
		},

		toggleClientSelection(id) {
			if (this.copySelectedUsers.includes(id)) {
				this.copySelectedUsers = this.copySelectedUsers.filter((value) => value !== id);
				return;
			}

			this.copySelectedUsers = [...this.copySelectedUsers, id];
		},

		selectAllClients() {
			this.copySelectedUsers = [...this.clientIds];
		},

		clearClientSelection() {
			this.copySelectedUsers = [];
		},
	}));

	Alpine.data('ordersTable', (config = {}) => ({
		editingId: config.editingId ?? null,
		createOpen: config.createOpen ?? false,

		startCreate() {
			this.createOpen = true;
			this.editingId = null;
		},

		cancelCreate() {
			this.createOpen = false;
		},

		startEdit(id) {
			this.editingId = id;
			this.createOpen = false;
		},

		cancelEdit() {
			this.editingId = null;
		},
	}));
});

Alpine.start();
