#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
LIST="$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"

cp /tmp/Productlisting.php "$MAGENTO/app/code/Hdweb/Tyrefinder/Helper/Productlisting.php"
install -D /tmp/gallery.phtml "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml"
cp "$LIST" "${LIST}.bak-$(date +%Y%m%d%H%M)"

perl -pi -e 's/\$brand_title = \$brandDetails->getName\(\);?/\$brand_title = (\$brandDetails \&\& is_object(\$brandDetails)) ? \$brandDetails->getName() : '\'''\'';/g' "$LIST"
perl -pi -e 's/\$front_brand_title = \$frontBrandDetails->getName\(\);/\$front_brand_title = (\$frontBrandDetails \&\& is_object(\$frontBrandDetails)) ? \$frontBrandDetails->getName() : '\'''\'';/g' "$LIST"
perl -pi -e 's/\$rear_brand_title = \$rearBrandDetails->getName\(\);/\$rear_brand_title = (\$rearBrandDetails \&\& is_object(\$rearBrandDetails)) ? \$rearBrandDetails->getName() : '\'''\'';/g' "$LIST"

chown -R www-data:www-data "$MAGENTO/app/code/Hdweb/Tyrefinder/Helper/Productlisting.php" \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml" \
  "$LIST"

cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush
rm -rf var/page_cache/* var/view_preprocessed/*
echo "Deploy complete"
