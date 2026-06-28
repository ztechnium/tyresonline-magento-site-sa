#!/usr/bin/env bash
set -euo pipefail
LIST="/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"
cp "$LIST" "${LIST}.bak-$(date +%Y%m%d%H%M%S)"

perl -pi -e 's/\$brand_title = \$brandDetails->getName\(\);?/\$brand_title = (\$brandDetails \&\& is_object(\$brandDetails)) ? \$brandDetails->getName() : '\'''\'';/g' "$LIST"
perl -pi -e 's/\$front_brand_title = \$frontBrandDetails->getName\(\);/\$front_brand_title = (\$frontBrandDetails \&\& is_object(\$frontBrandDetails)) ? \$frontBrandDetails->getName() : '\'''\'';/g' "$LIST"
perl -pi -e 's/\$rear_brand_title = \$rearBrandDetails->getName\(\);/\$rear_brand_title = (\$rearBrandDetails \&\& is_object(\$rearBrandDetails)) ? \$rearBrandDetails->getName() : '\'''\'';/g' "$LIST"

chown www-data:www-data "$LIST"
cd /var/www/magento
sudo -u www-data php bin/magento cache:flush
sudo rm -rf var/page_cache/* var/view_preprocessed/pub/static/frontend/Hditsol/tyresonline 2>/dev/null || true
echo "Patched list.phtml brand null checks"
