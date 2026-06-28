#!/usr/bin/env bash
set -euo pipefail
ROOT="/var/www/magento"
THEME="$ROOT/app/design/frontend/Hditsol/tyresonline"
MEDIA="$ROOT/pub/media/images/icon"

mkdir -p "$MEDIA"
cp /tmp/ksa-map.png "$MEDIA/ksa-map.png"
cp /tmp/ar_SA.svg "$THEME/web/images/icon/ar_SA.svg"
cp /tmp/en_US.svg "$THEME/web/images/icon/en_US.svg"
cp /tmp/languages.phtml "$THEME/Magento_Store/templates/switch/languages.phtml"
cp /tmp/home-page.phtml "$THEME/Magento_Theme/templates/home-page.phtml"

cd "$ROOT"
sudo -u www-data php bin/magento setup:static-content:deploy -f ar_SA en_US --theme Hditsol/tyresonline --theme Hditsol/tyresonline-ar 2>/dev/null || true
rm -rf var/view_preprocessed/* var/page_cache/* var/cache/*
sudo rm -rf /var/cache/apache2/mod_cache_disk/*
sudo -u www-data php bin/magento cache:flush
echo "Deployed KSA map + Saudi flag"
