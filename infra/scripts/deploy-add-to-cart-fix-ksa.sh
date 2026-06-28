#!/bin/bash
# Deploy add-to-cart fixes to KSA staging
set -euo pipefail
REMOTE=/var/www/magento
LOCAL_SA="C:/Users/khab/OneDrive/Desktop/projects/TyresOnline/tyresonline-sa"
# Use Windows paths via scp from local - script runs from dev machine via ssh for server parts only

echo "=== Deploy list.phtml ==="
sudo cp /tmp/list.phtml "$REMOTE/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"
sudo chown www-data:www-data "$REMOTE/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"

echo "=== Deploy cart-installer.phtml ==="
sudo cp /tmp/cart-installer.phtml "$REMOTE/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml"
sudo chown www-data:www-data "$REMOTE/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml"

echo "=== Deploy storelocator right.phtml ==="
sudo cp /tmp/right.phtml "$REMOTE/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml"
sudo chown www-data:www-data "$REMOTE/app/design/frontend/Hditsol/tyresonline/Ecomteck_StoreLocator/templates/storelocator/right.phtml"

echo "=== Deploy ajax-to-cart.js (source + static) ==="
sudo cp /tmp/ajax-to-cart.js "$REMOTE/app/code/Chetu/Ajaxcart/view/frontend/web/js/ajax-to-cart.js"
sudo chown www-data:www-data "$REMOTE/app/code/Chetu/Ajaxcart/view/frontend/web/js/ajax-to-cart.js"
for locale in en_US ar_SA; do
  STATIC="$REMOTE/pub/static/frontend/Hditsol/tyresonline/${locale}/Chetu_Ajaxcart/js/ajax-to-cart.js"
  if [ -f "$STATIC" ]; then
    sudo cp /tmp/ajax-to-cart.js "$STATIC"
    sudo chown www-data:www-data "$STATIC"
    echo "updated $STATIC"
  fi
done
for locale in en_US ar_SA; do
  STATIC="$REMOTE/pub/static/frontend/Hditsol/tyresonline-ar/${locale}/Chetu_Ajaxcart/js/ajax-to-cart.js"
  if [ -f "$STATIC" ]; then
    sudo cp /tmp/ajax-to-cart.js "$STATIC"
    sudo chown www-data:www-data "$STATIC"
    echo "updated $STATIC"
  fi
done

echo "=== Deploy custom.js ==="
sudo cp /tmp/custom.js "$REMOTE/app/design/frontend/Hditsol/tyresonline/web/js/custom.js"
sudo chown www-data:www-data "$REMOTE/app/design/frontend/Hditsol/tyresonline/web/js/custom.js"
for theme in tyresonline tyresonline-ar; do
  for locale in en_US ar_SA; do
    STATIC="$REMOTE/pub/static/frontend/Hditsol/${theme}/${locale}/js/custom.js"
    if [ -f "$STATIC" ]; then
      sudo cp /tmp/custom.js "$STATIC"
      sudo chown www-data:www-data "$STATIC"
      echo "updated $STATIC"
    fi
  done
done

echo "=== Flush caches ==="
cd "$REMOTE"
sudo -u www-data php bin/magento cache:flush full_page block_html layout 2>&1 | tail -3
sudo rm -rf var/page_cache/* var/view_preprocessed/* 2>/dev/null || true

echo "=== Verify add-to-cart test ==="
sed -i 's/\r$//' /tmp/test-add-to-cart-plp.sh 2>/dev/null || true
bash /tmp/test-add-to-cart-plp.sh 2>&1 | tail -15

echo "DONE"
