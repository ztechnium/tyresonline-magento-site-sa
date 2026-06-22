#!/bin/bash
echo "=== Data.php redis config ==="
grep -A5 envConfig /var/www/magento/app/code/Hdweb/Core/Helper/Data.php | head -6

echo "=== localhost Credis clients ==="
grep -rn "Credis_Client('127.0.0.1'" /var/www/magento/app/code/ 2>/dev/null || true

echo "=== exception log entries count ==="
wc -l /var/www/magento/var/log/exception.log

echo "=== last 3 exception report lines ==="
grep -E '^\[20|report_id|main\.CRITICAL|main\.ERROR' /var/www/magento/var/log/exception.log | tail -10

echo "=== checkout config ==="
cd /var/www/magento
sudo -u www-data php bin/magento config:show onestepcheckout/general/enabled 2>/dev/null || true
sudo -u www-data php bin/magento config:show checkout/options/guest_checkout 2>/dev/null || true

echo "=== onestepcheckout route ==="
grep -r onestepcheckout /var/www/magento/app/code/Ecomteck/OneStepCheckout/etc/frontend/routes.xml 2>/dev/null || true
