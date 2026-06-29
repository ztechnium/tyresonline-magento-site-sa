#!/bin/bash
# Fix PLP Buy Now showing ERROR popup on KSA staging.
# Root causes: PHP 8.2 dynamic properties in helpers, stale ajax-to-cart.js,
# checkout/cart/redirect_to_cart forcing backUrl on every ajax add.
set -euo pipefail
MAGENTO="${MAGENTO:-/var/www/magento}"
cd "$MAGENTO"

echo "=== Deploy PHP/JS fixes from repo ==="
REPO="${REPO:-$MAGENTO}"
for f in \
  app/code/Hdweb/Installer/Helper/Data.php \
  app/code/Lof/All/Helper/Data.php \
  app/code/Chetu/Ajaxcart/view/frontend/web/js/ajax-to-cart.js
do
  if [ -f "$REPO/$f" ]; then
    cp "$REPO/$f" "$MAGENTO/$f"
    echo "  $f"
  fi
done

echo "=== Magento config ==="
sudo -u www-data php bin/magento config:set checkout/cart/redirect_to_cart 0
sudo -u www-data php bin/magento config:set ajaxcart/general/header_text "Added to Cart"

echo "=== Static deploy + cache ==="
sudo -u www-data php bin/magento setup:static-content:deploy en_US ar_SA -f \
  --theme Hditsol/tyresonline --theme Hditsol/tyresonline-ar
sudo -u www-data php bin/magento cache:flush

echo "Done. Test: Buy Now on PLP should show 'Added to Cart' popup, not ERROR."
