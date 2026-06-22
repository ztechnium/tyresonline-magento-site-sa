#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
TS=$(date +%Y%m%d%H%M)
SRC=/tmp/uae-sync

echo "=== Backup KSA templates ==="
for f in \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml" \
  "$MAGENTO/app/code/Hdweb/Tyrefinder/view/frontend/templates/wheel-protectors-products-and-after.phtml" \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml"
do
  cp "$f" "${f}.bak-${TS}"
done

echo "=== Install UAE-synced templates ==="
install -D "$SRC/list.phtml" "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"
install -D "$SRC/wheel-protectors-products-and-after.phtml" "$MAGENTO/app/code/Hdweb/Tyrefinder/view/frontend/templates/wheel-protectors-products-and-after.phtml"
install -D "$SRC/gallery.phtml" "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml"

chown -R www-data:www-data \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/" \
  "$MAGENTO/app/code/Hdweb/Tyrefinder/view/frontend/templates/"

echo "=== Magento cache ==="
cd "$MAGENTO"
rm -rf var/page_cache/* var/view_preprocessed/* var/cache/mage--*
sudo -u www-data php bin/magento cache:clean block_html full_page layout
sudo -u www-data php bin/magento cache:flush

echo "=== Restart Apache ==="
systemctl restart apache2
sleep 3

echo "=== Verify KSA ==="
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' -o /tmp/ksa-after-sync.html
echo "KSA size: $(wc -c < /tmp/ksa-after-sync.html)"
echo "KSA prices: $(grep -oE 'SAR [0-9][0-9.,]+' /tmp/ksa-after-sync.html | wc -l)"
echo "KSA modern-layout: $(grep -c 'product-box modern-layout' /tmp/ksa-after-sync.html)"
grep 'products wrapper' /tmp/ksa-after-sync.html | head -2

echo "=== Benchmark UAE ==="
curl -s 'https://stg.tyresonline.ae/en/all-tyres/car-tyres.html' -o /tmp/uae-bench.html
echo "UAE size: $(wc -c < /tmp/uae-bench.html)"
echo "UAE prices: $(grep -oE 'AED [0-9][0-9.,]+' /tmp/uae-bench.html | wc -l)"
echo "UAE modern-layout: $(grep -c 'product-box modern-layout' /tmp/uae-bench.html)"

echo DONE
