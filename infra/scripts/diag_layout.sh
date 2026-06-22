#!/bin/bash
echo "=== checkout layout files on server ==="
find /var/www/magento -path '*/view/frontend/layout/checkout_index_index.xml' 2>/dev/null
echo "=== grep checkout.root in layouts ==="
grep -rl 'checkout.root' /var/www/magento/app/code /var/www/magento/vendor/magento/module-checkout 2>/dev/null | head -20
echo "=== onestepcheckout layout ==="
cat /var/www/magento/app/code/Ecomteck/OneStepCheckout/view/frontend/layout/onestepcheckout_index_index.xml 2>/dev/null | head -40
echo "=== checkout_index_index from module-checkout ==="
grep -A30 'checkout.root' /var/www/magento/vendor/magento/module-checkout/view/frontend/layout/checkout_index_index.xml 2>/dev/null | head -35
