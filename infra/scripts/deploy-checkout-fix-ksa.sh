#!/bin/bash
# Deploy checkout page fix: jsLayout normalizer + plugins for empty checkout.
set -euo pipefail
MAGENTO=/var/www/magento
SRC=/tmp/checkout-fix

echo "=== Deploy checkout Coreoverride fix ==="
sudo mkdir -p "$MAGENTO/app/code/Hdweb/Coreoverride/Model/Checkout" \
    "$MAGENTO/app/code/Hdweb/Coreoverride/Plugin/Checkout" \
    "$MAGENTO/app/code/Hdweb/Coreoverride/Observer" \
    "$MAGENTO/app/code/Hdweb/Coreoverride/etc/frontend" \
    "$MAGENTO/app/code/Ecomteck/OneStepCheckoutCompatible/Block/Checkout" \
    "$MAGENTO/app/code/Hdweb/Shippingform/Controller/Ajax" \
    "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Checkout/templates"

sudo cp -v "$SRC/CheckoutJsLayoutNormalizer.php" "$MAGENTO/app/code/Hdweb/Coreoverride/Model/Checkout/"
sudo cp -v "$SRC/LayoutProcessorPlugin.php" "$MAGENTO/app/code/Hdweb/Coreoverride/Plugin/Checkout/"
sudo cp -v "$SRC/OnepagePlugin.php" "$MAGENTO/app/code/Hdweb/Coreoverride/Plugin/Checkout/"
sudo cp -v "$SRC/EnsureCheckoutLayoutHandleObserver.php" "$MAGENTO/app/code/Hdweb/Coreoverride/Observer/"
sudo cp -v "$SRC/Getvehiclemodel.php" "$MAGENTO/app/code/Hdweb/Shippingform/Controller/Ajax/"
sudo cp -v "$SRC/Getvehicleyear.php" "$MAGENTO/app/code/Hdweb/Shippingform/Controller/Ajax/"
sudo cp -v "$SRC/frontend_di.xml" "$MAGENTO/app/code/Hdweb/Coreoverride/etc/frontend/di.xml"
sudo cp -v "$SRC/frontend_events.xml" "$MAGENTO/app/code/Hdweb/Coreoverride/etc/frontend/events.xml"
if [ -f "$SRC/checkout-loader-fix.phtml" ]; then
    sudo cp -v "$SRC/checkout-loader-fix.phtml" "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Checkout/templates/"
fi

sudo chown -R www-data:www-data \
    "$MAGENTO/app/code/Hdweb/Coreoverride/Model/Checkout" \
    "$MAGENTO/app/code/Hdweb/Coreoverride/Plugin/Checkout" \
    "$MAGENTO/app/code/Hdweb/Coreoverride/Observer" \
    "$MAGENTO/app/code/Hdweb/Coreoverride/etc/frontend" \
    "$MAGENTO/app/code/Ecomteck/OneStepCheckoutCompatible/Block/Checkout" \
    "$MAGENTO/app/code/Hdweb/Shippingform/Controller/Ajax"

cd "$MAGENTO"
sudo rm -rf generated/code/Hdweb/Coreoverride/ var/page_cache/* var/view_preprocessed/*
sudo -u www-data php bin/magento setup:di:compile 2>&1 | tail -3
sudo -u www-data php bin/magento cache:flush layout block_html full_page config
sudo systemctl restart apache2 2>/dev/null || true

echo "=== Verify plugins and observer ==="
sudo -u www-data php -r "require 'app/bootstrap.php'; echo class_exists('Hdweb\Coreoverride\Plugin\Checkout\LayoutProcessorPlugin') ? 'plugin OK' : 'plugin FAIL'; echo PHP_EOL; echo class_exists('Hdweb\Coreoverride\Observer\EnsureCheckoutLayoutHandleObserver') ? 'observer OK' : 'observer FAIL'; echo PHP_EOL;"

echo "Done."
