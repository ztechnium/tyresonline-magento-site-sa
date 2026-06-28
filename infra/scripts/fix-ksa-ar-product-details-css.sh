#!/usr/bin/env bash
set -euo pipefail
MAGENTO=/var/www/magento
EN_CSS="$MAGENTO/app/design/frontend/Hditsol/tyresonline/web/css/product-details.css"
AR_CSS="$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/css/product-details.css"
AR_MIN="$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/product-details.min.css"

echo "Before: en=$(stat -c%s "$EN_CSS") ar=$(stat -c%s "$AR_CSS") ar_min=$(stat -c%s "$AR_MIN" 2>/dev/null || echo 0)"

cp "$EN_CSS" "$AR_CSS"

STATIC="$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css"
rm -f "$STATIC/product-details.min.css" "$STATIC/product-details.css"
rm -rf "$MAGENTO/pub/static/_cache/merged/"*

cd "$MAGENTO"
sudo -u www-data php bin/magento setup:static-content:deploy ar_SA -f --area frontend --theme Hditsol/tyresonline-ar 2>&1 | tail -5
sudo -u www-data php bin/magento cache:flush | tail -3

echo "After: ar=$(stat -c%s "$AR_CSS") ar_min=$(stat -c%s "$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/product-details.min.css")"
