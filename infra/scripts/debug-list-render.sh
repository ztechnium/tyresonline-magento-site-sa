#!/bin/bash
set -euo pipefail
python3 /tmp/patch-list-catch-log.py
rm -f /var/www/magento/var/log/list-render-errors.log
chown www-data:www-data /var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
cd /var/www/magento
rm -rf var/view_preprocessed/*
sudo -u www-data php bin/magento cache:flush
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?dbg=3' -o /dev/null
echo "=== errors ==="
head -20 /var/www/magento/var/log/list-render-errors.log 2>/dev/null || echo "(none)"
wc -l /var/www/magento/var/log/list-render-errors.log 2>/dev/null || true
