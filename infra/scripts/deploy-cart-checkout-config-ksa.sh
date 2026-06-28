#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
THEME_BASE=app/design/frontend/Hditsol/tyresonline/Magento_Checkout

echo "Deploying cart checkout-config fix..."
sudo cp -v "$MAGENTO/$THEME_BASE/layout/checkout_cart_index.xml" "$MAGENTO/$THEME_BASE/layout/checkout_cart_index.xml.bak.$(date +%s)" 2>/dev/null || true
sudo cp -v /tmp/cart-fix/checkout_cart_index.xml "$MAGENTO/$THEME_BASE/layout/"
sudo cp -v /tmp/cart-fix/checkout-config.phtml "$MAGENTO/$THEME_BASE/templates/cart/"
sudo chown -R www-data:www-data "$MAGENTO/$THEME_BASE/layout/checkout_cart_index.xml" "$MAGENTO/$THEME_BASE/templates/cart/checkout-config.phtml"
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush layout block_html full_page
echo "Done."
