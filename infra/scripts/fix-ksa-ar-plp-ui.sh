#!/bin/bash
set -eu
MAGENTO=/var/www/magento
BASE="https://stg.tyresonline.sa"
cd "$MAGENTO"

echo "=== 1. Enable store codes in URLs (EN/AR theme switching) ==="
sudo -u www-data php bin/magento config:set web/url/use_store 1
sudo -u www-data php bin/magento config:set --scope=stores --scope-code=en web/unsecure/base_url "${BASE}/"
sudo -u www-data php bin/magento config:set --scope=stores --scope-code=en web/secure/base_url "${BASE}/"
sudo -u www-data php bin/magento config:set --scope=stores --scope-code=ar web/unsecure/base_url "${BASE}/"
sudo -u www-data php bin/magento config:set --scope=stores --scope-code=ar web/secure/base_url "${BASE}/"

echo "=== 2. Deploy static content for EN + AR themes ==="
sudo -u www-data php bin/magento setup:static-content:deploy -f en_US ar_SA \
  --theme Hditsol/tyresonline \
  --theme Hditsol/tyresonline-ar 2>&1 | tail -8

echo "=== 3. Flush caches ==="
sudo -u www-data php bin/magento cache:flush

echo "=== 4. Verify theme resolution ==="
sudo -u www-data php /tmp/check-theme-staging.php 2>/dev/null || true

echo "=== 5. Spot-check static assets ==="
ls "$MAGENTO/pub/static/frontend/Hditsol/tyresonline/en_US/Smile_ElasticsuiteCatalog/css/priceslider.css" 2>&1 || true
ls "$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/Smile_ElasticsuiteCatalog/css/priceslider.css" 2>&1 || true
ls "$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/custom-style.css" 2>&1 || true

echo "FIX_KSA_AR_PLP_UI_DONE"
