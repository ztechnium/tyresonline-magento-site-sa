#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
LOG=$MAGENTO/var/log/exception.log
SZ=$(stat -c%s "$LOG")

cd "$MAGENTO"
rm -rf var/page_cache/* var/view_preprocessed/* var/cache/mage--*
sudo -u www-data php bin/magento cache:clean block_html full_page layout
sudo -u www-data php bin/magento cache:flush
systemctl restart apache2
sleep 2
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?bust=1' -o /tmp/car-bust.html
echo "size: $(wc -c < /tmp/car-bust.html)"
echo "prices: $(grep -o 'SAR [0-9][0-9.,]*' /tmp/car-bust.html | wc -l)"
echo "grid: $(grep 'products-grid' /tmp/car-bust.html | head -1)"
echo "log_growth: $(($(stat -c%s "$LOG") - SZ))"
tail -c 1000 "$LOG"
