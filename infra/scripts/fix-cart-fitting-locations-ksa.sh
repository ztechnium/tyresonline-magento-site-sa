#!/bin/bash
# Deploy cart fitting locations fix to KSA staging EC2.
# Fixes: circular require in ecomteck_storelocator.js, Google Maps init in right.phtml,
#        PHP 8.2 dynamic properties in Productdetails helper.
set -euo pipefail

MAGENTO="${MAGENTO:-/var/www/magento}"
REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

echo "Deploying cart fitting locations fix from $REPO_ROOT ..."

sudo cp -v "$REPO_ROOT/app/code/Ecomteck/StoreLocator/view/frontend/web/js/ecomteck_storelocator.js" \
  "$MAGENTO/app/code/Ecomteck/StoreLocator/view/frontend/web/js/ecomteck_storelocator.js"

sudo cp -v "$REPO_ROOT/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml" \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml"

if [ -f "$REPO_ROOT/app/design/frontend/Hditsol/tyresonline-ar/Ecomteck_StoreLocator/templates/storelocator/right.phtml" ]; then
  sudo cp -v "$REPO_ROOT/app/design/frontend/Hditsol/tyresonline-ar/Ecomteck_StoreLocator/templates/storelocator/right.phtml" \
    "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/Ecomteck_StoreLocator/templates/storelocator/right.phtml"
fi

sudo cp -v "$REPO_ROOT/app/code/Hdweb/Addattribute/Helper/Productdetails.php" \
  "$MAGENTO/app/code/Hdweb/Addattribute/Helper/Productdetails.php"

sudo chown -R www-data:www-data \
  "$MAGENTO/app/code/Ecomteck/StoreLocator/view/frontend/web/js/ecomteck_storelocator.js" \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml" \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/Ecomteck_StoreLocator/templates/storelocator/right.phtml" \
  "$MAGENTO/app/code/Hdweb/Addattribute/Helper/Productdetails.php" 2>/dev/null || true

cd "$MAGENTO"
echo "Deploying static assets for store locator JS..."
sudo -u www-data php bin/magento setup:static-content:deploy en_US ar_SA -f \
  --theme Hditsol/tyresonline --theme Hditsol/tyresonline-ar \
  --area frontend --no-html --no-css --no-less --no-images --no-fonts --no-misc --jobs=4 2>&1 | tail -25

sudo -u www-data php bin/magento cache:flush layout block_html full_page
sudo systemctl restart apache2 || true

echo "Verification:"
grep -c "loadGoogleMaps" "$MAGENTO/app/code/Ecomteck/StoreLocator/view/frontend/web/js/ecomteck_storelocator.js"
grep -c "whenGoogleMapsReady" "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml"
grep -c "protected \$_productloader" "$MAGENTO/app/code/Hdweb/Addattribute/Helper/Productdetails.php"
echo "Done."
