#!/bin/bash
echo "=== remove checkout.root ==="
grep -rn 'checkout.root' /var/www/magento/app/code /var/www/magento/vendor/magento/module-checkout/view/frontend/layout/ 2>/dev/null | grep -i 'remove\|unset' | head -20

echo "=== page layout checkout.xml exists ==="
ls -la /var/www/magento/vendor/magento/module-checkout/view/frontend/page_layout/checkout.xml 2>&1

echo "=== theme checkout layouts ==="
find /var/www/magento/app/design -name 'checkout_index_index.xml' 2>/dev/null
find /var/www/magento/app/design -name 'onestepcheckout.xml' 2>/dev/null

echo "=== compare Coreoverride layout page attr ==="
head -3 /var/www/magento/app/code/Hdweb/Coreoverride/view/frontend/layout/checkout_index_index.xml
head -3 /var/www/magento/vendor/magento/module-checkout/view/frontend/layout/checkout_index_index.xml
