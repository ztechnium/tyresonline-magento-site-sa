#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
LIST="$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"
WHEEL="$MAGENTO/app/code/Hdweb/Tyrefinder/view/frontend/templates/wheel-protectors-products-and-after.phtml"

echo "=== Deploy helper + gallery ==="
cp /tmp/Productlisting.php "$MAGENTO/app/code/Hdweb/Tyrefinder/Helper/Productlisting.php"
install -D /tmp/gallery.phtml "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml"

echo "=== Patch wheel-protectors ==="
python3 << 'PY'
from pathlib import Path
path = Path("/var/www/magento/app/code/Hdweb/Tyrefinder/view/frontend/templates/wheel-protectors-products-and-after.phtml")
text = path.read_text()
old = "$childrenProducts = $_product->getTypeInstance()->getUsedProducts($_product);"
new = """$childrenProducts = [];
                       if ($_product->getTypeId() === 'configurable') {
                           $childrenProducts = $_product->getTypeInstance()->getUsedProducts($_product);
                       }"""
if old in text:
    text = text.replace(old, new)
    path.write_text(text)
    print('patched wheel-protectors')
else:
    print('wheel-protectors ok')
PY

echo "=== Patch list.phtml visibility + brand safety ==="
python3 << 'PY'
from pathlib import Path
path = Path("/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml")
text = path.read_text()
text = text.replace('products-<?= /* @noEscape */ $viewMode ?> d-none d-md-block', 'products-<?= /* @noEscape */ $viewMode ?>')
text = text.replace('products-list d-md-none', 'products-list d-none')
text = text.replace('$brand_title = $brandDetails->getName();', "$brand_title = ($brandDetails && is_object($brandDetails)) ? $brandDetails->getName() : '';")
text = text.replace('$brand_title = $brandDetails->getName(); ', "$brand_title = ($brandDetails && is_object($brandDetails)) ? $brandDetails->getName() : ''; ")
text = text.replace('$front_brand_title = $frontBrandDetails->getName();', "$front_brand_title = ($frontBrandDetails && is_object($frontBrandDetails)) ? $frontBrandDetails->getName() : '';")
text = text.replace('$rear_brand_title = $rearBrandDetails->getName();', "$rear_brand_title = ($rearBrandDetails && is_object($rearBrandDetails)) ? $rearBrandDetails->getName() : '';")
path.write_text(text)
print('list.phtml patched')
PY

chown -R www-data:www-data "$MAGENTO/app/code/Hdweb/Tyrefinder" "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/"

echo "=== Magento cache ==="
cd "$MAGENTO"
rm -rf var/page_cache/* var/view_preprocessed/* var/cache/mage--*
sudo -u www-data php bin/magento cache:clean block_html full_page layout
sudo -u www-data php bin/magento cache:flush

echo "=== Restart Apache ==="
systemctl restart apache2
sleep 2

echo "=== Verify ==="
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?fixed='$(date +%s) -o /tmp/car-fixed.html
echo "size: $(wc -c < /tmp/car-fixed.html)"
echo "prices: $(grep -o 'SAR [0-9][0-9.,]*' /tmp/car-fixed.html | wc -l)"
grep 'products wrapper' /tmp/car-fixed.html | head -2
echo DONE
