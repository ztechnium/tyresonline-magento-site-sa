#!/usr/bin/env bash
set -euo pipefail
MAGENTO=/var/www/magento
STATIC="$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css"

echo "Removing stale product-details static files..."
rm -f "$STATIC/product-details.min.css" "$STATIC/product-details.css"
rm -rf "$MAGENTO/pub/static/_cache/merged/"*

cd "$MAGENTO"
sudo -u www-data php bin/magento setup:static-content:deploy ar_SA -f --area frontend --theme Hditsol/tyresonline-ar 2>&1 | tail -5

echo "Deployed sizes:"
stat -c '%s %n' "$STATIC/product-details.css" 2>/dev/null || true
stat -c '%s %n' "$STATIC/product-details.min.css" 2>/dev/null || true

sudo -u www-data php bin/magento cache:flush | tail -2
