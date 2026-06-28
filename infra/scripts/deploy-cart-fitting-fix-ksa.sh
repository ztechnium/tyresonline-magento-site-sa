#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
KEY="${STAGING_KEY:-/home/ubuntu/staging-key-tyresonline.pem}"

echo "Deploying cart fitting location fix..."
sudo cp -v /tmp/fitting-fix/Savecartinstaller.php \
  "$MAGENTO/app/code/Hdweb/Installer/Controller/Ajax/Savecartinstaller.php"
sudo cp -v /tmp/fitting-fix/ecomteck_storelocator.js \
  "$MAGENTO/app/code/Ecomteck/StoreLocator/view/frontend/web/js/ecomteck_storelocator.js"
sudo cp -v /tmp/fitting-fix/right.phtml \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml"
sudo cp -v /tmp/fitting-fix/jquery.storelocator.js \
  "$MAGENTO/app/code/Ecomteck/StoreLocator/view/frontend/web/js/plugins/storeLocator/jquery.storelocator.js"
sudo chown -R www-data:www-data \
  "$MAGENTO/app/code/Hdweb/Installer/Controller/Ajax/Savecartinstaller.php" \
  "$MAGENTO/app/code/Ecomteck/StoreLocator/view/frontend/web/js/ecomteck_storelocator.js" \
  "$MAGENTO/app/code/Ecomteck/StoreLocator/view/frontend/web/js/plugins/storeLocator/jquery.storelocator.js" \
  "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml"

cd "$MAGENTO"
echo "Deploying static assets (JS only)..."
sudo -u www-data php bin/magento setup:static-content:deploy en_US -t Hditsol/tyresonline \
  --area frontend --no-html --no-css --no-less --no-images --no-fonts --no-misc --jobs=4 -f 2>&1 | tail -20

sudo -u www-data php bin/magento cache:flush layout block_html full_page
echo "Done."
