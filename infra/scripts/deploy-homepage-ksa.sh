#!/usr/bin/env bash
set -euo pipefail
ROOT="/var/www/magento"
THEME="$ROOT/app/design/frontend/Hditsol"

cp /tmp/home-page.phtml "$THEME/tyresonline/Magento_Theme/templates/home-page.phtml"
cp /tmp/customheader.phtml "$THEME/tyresonline/Magento_Theme/templates/html/customheader.phtml"
cp /tmp/home-main-banner.phtml "$THEME/tyresonline/Magento_Theme/templates/home/home-main-banner.phtml"
cp /tmp/footer.phtml "$THEME/tyresonline-ar/Magento_Theme/templates/html/footer.phtml"
cp /tmp/ar_SA.csv "$THEME/tyresonline-ar/i18n/ar_SA.csv"

cd "$ROOT"
rm -rf var/page_cache/* var/cache/*
sudo rm -rf /var/cache/apache2/mod_cache_disk/*
sudo -u www-data php bin/magento cache:flush
echo "Deployed homepage KSA templates + purged Apache/Magento cache"
