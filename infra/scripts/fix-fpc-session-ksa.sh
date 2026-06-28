#!/bin/bash
set -euo pipefail
M=/var/www/magento
cd "$M"

echo "=== Baseline headers ==="
curl -sI https://stg.tyresonline.sa/all-tyres/car-tyres.html | grep -iE 'cache-control|set-cookie|x-magento' || true

echo "=== FPC diagnostic ==="
sudo -u www-data php /tmp/diag-fpc-ksa.php 2>&1 || true

echo "=== PHP session.auto_start ==="
php -i 2>/dev/null | grep session.auto_start || true

echo "=== Apply built-in FPC (disable Mgt Varnish) ==="
sudo -u www-data php /tmp/fix-fpc-config-ksa.php 2>&1 || true
sudo -u www-data php bin/magento cache:clean config full_page 2>&1 | tail -3

echo "=== Disable DeveloperToolbar if enabled ==="
if sudo -u www-data php bin/magento module:status Mgt_DeveloperToolbar 2>/dev/null | grep -q enabled; then
  sudo -u www-data php bin/magento module:disable Mgt_DeveloperToolbar --clear-static-content 2>&1 | tail -3 || true
  sudo -u www-data php bin/magento setup:di:compile 2>&1 | tail -3 || true
fi

sudo -u www-data php bin/magento cache:flush 2>&1 | tail -2
rm -rf var/page_cache/* 2>/dev/null || true

echo "=== After fix headers ==="
curl -sI https://stg.tyresonline.sa/all-tyres/car-tyres.html | grep -iE 'cache-control|set-cookie|x-magento' || true
echo "=== Second request (warm cache) ==="
curl -sI https://stg.tyresonline.sa/all-tyres/car-tyres.html | grep -iE 'cache-control|set-cookie|x-magento|age:' || true
