#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
LIST="$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"

python3 << 'PY'
from pathlib import Path
path = Path("/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml")
text = path.read_text()
text = text.replace(
    'products-<?= /* @noEscape */ $viewMode ?> d-none d-md-block',
    'products-<?= /* @noEscape */ $viewMode ?>',
)
text = text.replace('products-list d-md-none', 'products-list d-none')
text = text.replace(
    '$brand_title = $brandDetails->getName();',
    '$brand_title = ($brandDetails && is_object($brandDetails)) ? $brandDetails->getName() : \'\';',
)
text = text.replace(
    '$brand_title = $brandDetails->getName(); ',
    '$brand_title = ($brandDetails && is_object($brandDetails)) ? $brandDetails->getName() : \'\'; ',
)
text = text.replace(
    '$front_brand_title = $frontBrandDetails->getName();',
    '$front_brand_title = ($frontBrandDetails && is_object($frontBrandDetails)) ? $frontBrandDetails->getName() : \'\';',
)
text = text.replace(
    '$rear_brand_title = $rearBrandDetails->getName();',
    '$rear_brand_title = ($rearBrandDetails && is_object($rearBrandDetails)) ? $rearBrandDetails->getName() : \'\';',
)
path.write_text(text)
print('list.phtml patched')
PY

chown www-data:www-data "$LIST"
cd "$MAGENTO"
rm -rf var/view_preprocessed/* var/page_cache/* var/cache/mage--*
sudo -u www-data php bin/magento cache:clean block_html full_page layout
sudo -u www-data php bin/magento cache:flush
echo done
