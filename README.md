# Dropship Radar

Dropship Radar is a Laravel-based internal tool for tracking listings, orders, profit, and bulk Amazon workflows for dropshipping operations.

## Main Features

- Inline editing for listings and orders directly from the table view.
- Add new listings and orders without leaving the index page.
- Bulk selection for listings.
- Bulk eBay price adjustments by percentage.
- Bulk opening of selected Amazon product URLs.
- Admin tools for importing listings to users and managing users.

## Local Development

Common commands:

```bash
composer install
corepack pnpm install
php artisan migrate
php artisan serve
corepack pnpm dev
```

Frontend production build:

```bash
corepack pnpm build
```

Blade cache refresh:

```bash
php artisan view:cache
```

## Bulk Amazon Tabs

The Listings page supports selecting multiple rows and opening their Amazon URLs in separate browser tabs.

How it works:

1. Select one or more listings using the checkboxes.
2. Click `Open Amazon URLs`.
3. The app attempts to open each selected Amazon URL in a separate tab.

If only one tab opens, the browser is blocking additional popup tabs.

## Popup Blocker Settings

To allow bulk Amazon tabs to open correctly, allow popups for the local app domain.

### Chrome

Quick method:

1. Open the Listings page.
2. Click the popup-blocked icon on the right side of the address bar.
3. Choose `Always allow pop-ups and redirects from ...`.
4. Reload the page.

Manual method:

1. Open `chrome://settings/content/popups`.
2. Under `Allowed to send pop-ups and use redirects`, click `Add`.
3. Add the local site URL.

Examples:

- `http://localhost`
- `http://dropshipaccounts.test`

### Edge

Quick method:

1. Open the Listings page.
2. Click the popup-blocked icon in the address bar.
3. Allow popups for the site.
4. Reload the page.

Manual method:

1. Open `edge://settings/content/popups`.
2. Under `Allow`, click `Add`.
3. Add the local site URL.

Examples:

- `http://localhost`
- `http://dropshipaccounts.test`

## Notes

- If you are using Herd, allow the exact Herd local domain, not just `localhost`.
- Browser extensions such as popup blockers, privacy tools, or ad blockers can still prevent multiple tabs from opening even after browser settings are updated.
- If bulk tab opening is still blocked, disable those extensions for the local site and try again.
