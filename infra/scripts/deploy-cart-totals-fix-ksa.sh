#!/bin/bash
# Deploy cart Summary totals fix: outputs window.checkoutConfig on cart page
# when checkout.cart.shipping block is not rendered.
set -euo pipefail
MAGENTO=/var/www/magento
THEME_BASE=app/design/frontend/Hditsol/tyresonline/Magento_Checkout
SRC=/tmp/cart-totals-fix

echo "=== Deploy cart totals fix ==="
sudo mkdir -p "$MAGENTO/$THEME_BASE/templates/cart" "$MAGENTO/app/code/Hdweb/Core/Model/Checkout"

sudo cp -v "$SRC/totals.phtml" "$MAGENTO/$THEME_BASE/templates/cart/totals.phtml"
sudo cp -v "$SRC/checkout_cart_index.xml" "$MAGENTO/$THEME_BASE/layout/checkout_cart_index.xml"
sudo cp -v "$SRC/CompositeConfigProvider.php" "$MAGENTO/app/code/Hdweb/Core/Model/Checkout/CompositeConfigProvider.php"
if [ -f "$SRC/fix-knockout-template-loader-ksa.sh" ]; then
    sudo cp -v "$SRC/fix-knockout-template-loader-ksa.sh" /tmp/fix-knockout-template-loader-ksa.sh
    sudo bash /tmp/fix-knockout-template-loader-ksa.sh
fi
if [ -f "$SRC/Data.php" ]; then
    sudo cp -v "$SRC/Data.php" "$MAGENTO/app/code/Meetanshi/OrderUpload/Helper/Data.php"
fi

sudo chown -R www-data:www-data \
    "$MAGENTO/$THEME_BASE/templates/cart/totals.phtml" \
    "$MAGENTO/$THEME_BASE/layout/checkout_cart_index.xml" \
    "$MAGENTO/app/code/Hdweb/Core/Model/Checkout/CompositeConfigProvider.php"
if [ -f "$MAGENTO/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml" ] && [ -f "$SRC/cart-installer.phtml" ]; then
    sudo cp -v "$SRC/cart-installer.phtml" "$MAGENTO/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml"
    sudo chown www-data:www-data "$MAGENTO/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml"
fi
[ -f "$MAGENTO/app/code/Meetanshi/OrderUpload/Helper/Data.php" ] && \
    sudo chown www-data:www-data "$MAGENTO/app/code/Meetanshi/OrderUpload/Helper/Data.php"

cd "$MAGENTO"
sudo rm -rf generated/code/Hdweb/Core/ generated/code/Meetanshi/ var/page_cache/* var/view_preprocessed/pub/static/frontend/Hditsol/tyresonline 2>/dev/null || true
sudo -u www-data php bin/magento setup:di:compile 2>&1 | tail -5
sudo -u www-data php bin/magento cache:flush layout block_html full_page config 2>&1 | tail -3
sudo systemctl restart apache2 2>/dev/null || sudo service apache2 restart 2>/dev/null || true

echo "=== Verify CompositeConfigProvider ==="
sudo -u www-data php -r "require 'app/bootstrap.php'; echo class_exists('Hdweb\Core\Model\Checkout\CompositeConfigProvider') ? 'OK' : 'FAIL'; echo PHP_EOL;"

echo "=== Done ==="
