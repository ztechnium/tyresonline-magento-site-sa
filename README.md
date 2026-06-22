# Tyres Online — Saudi Arabia (Magento 2)

Stable codebase for the KSA Magento site (`stg.tyresonline.sa` / production).

## What is in this repo

- Custom modules under `app/code/` (Hdweb, Ecomteck, Hyperpay, etc.)
- Theme: `app/design/frontend/Hditsol/tyresonline`
- KSA infra scripts: `infra/scripts/`
- Composer manifest: `composer.json` / `composer.lock`

## Not included (gitignored)

- `vendor/` — run `composer install` after clone
- `app/etc/env.php`, `app/etc/config.php` — environment-specific
- `var/`, `generated/`, `pub/static/` — runtime / deploy artifacts
- Keys and credentials (`*.pem`, `auth.json`)

## Deploy notes (KSA staging)

After code deploy:

```bash
composer install --no-dev
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy en_US ar_SA -f
php bin/magento cache:flush
sudo systemctl restart apache2
```

Ensure `hdweb/general/car_tyre_category_id` is **1945** (Car Tyres) for KSA.

Sales sequence metadata must exist in `sales_sequence_meta` — see `infra/scripts/fix-sales-sequence.sql`.

## Recent KSA fixes included

- Checkout: Saudi cities/areas from DB, sales sequence, HyperPay flow
- Homepage tyre search: correct category ID + store-scoped AJAX URLs
- Store locator, checkout config providers, Redis-safe helpers
