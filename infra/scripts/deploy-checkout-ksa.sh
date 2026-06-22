#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
SRC=/tmp/checkout-ksa-fix
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB_NAME=tyresonline_sa

echo '=== Copy patched files ==='
install -D "$SRC/Core/Helper/Data.php" "$MAGENTO/app/code/Hdweb/Core/Helper/Data.php"
install -D "$SRC/Core/Model/Checkout/LayoutProcessor.php" "$MAGENTO/app/code/Hdweb/Core/Model/Checkout/LayoutProcessor.php"
install -D "$SRC/Core/view/base/web/template/ui/form/element/phone-overwrite.html" "$MAGENTO/app/code/Hdweb/Core/view/base/web/template/ui/form/element/phone-overwrite.html"
install -D "$SRC/Shippingform/view/frontend/web/js/view/custom-vehicle-form.js" "$MAGENTO/app/code/Hdweb/Shippingform/view/frontend/web/js/view/custom-vehicle-form.js"
install -D "$SRC/Shippingform/Controller/Ajax/Getvehiclemodel.php" "$MAGENTO/app/code/Hdweb/Shippingform/Controller/Ajax/Getvehiclemodel.php"
install -D "$SRC/Shippingform/Controller/Ajax/Getvehicleyear.php" "$MAGENTO/app/code/Hdweb/Shippingform/Controller/Ajax/Getvehicleyear.php"
install -D "$SRC/Tyrefinder/Helper/Data.php" "$MAGENTO/app/code/Hdweb/Tyrefinder/Helper/Data.php"
install -D "$SRC/Tyrefinder/Controller/Ajax/Getmodel.php" "$MAGENTO/app/code/Hdweb/Tyrefinder/Controller/Ajax/Getmodel.php"
install -D "$SRC/Vehicles/Helper/Data.php" "$MAGENTO/app/code/Hdweb/Vehicles/Helper/Data.php"

chown -R www-data:www-data "$MAGENTO/app/code/Hdweb/Core" "$MAGENTO/app/code/Hdweb/Shippingform" "$MAGENTO/app/code/Hdweb/Tyrefinder" "$MAGENTO/app/code/Hdweb/Vehicles"

echo '=== Saudi areas DB ==='
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SRC/checkout_city_area_ksa.sql"

echo '=== Clear vehicle makes cache ==='
redis-cli DEL vehicle_makes_list 2>/dev/null || true

echo '=== Magento ==='
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -2
sudo rm -rf var/view_preprocessed/* pub/static/frontend/Hditsol/tyresonline/en_US/Hdweb_Core pub/static/frontend/Hditsol/tyresonline/en_US/Hdweb_Shippingform 2>/dev/null || true
sudo -u www-data php bin/magento setup:static-content:deploy -f en_US --theme Hditsol/tyresonline 2>&1 | tail -5

echo DONE
