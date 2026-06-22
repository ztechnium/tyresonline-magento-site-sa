#!/bin/bash
echo "=== static mixins file ==="
ls -la /var/www/magento/pub/static/frontend/Hditsol/tyresonline/en_US/mage/requirejs/mixins.js 2>&1
ls -la /var/www/magento/pub/static/frontend/Hditsol/tyresonline/en_US/mage/requirejs/mixins.min.js 2>&1
ls -la /var/www/magento/pub/static/version*/frontend/Hditsol/tyresonline/en_US/mage/requirejs/mixins.js 2>&1 | head -3

echo "=== curl mixins from web ==="
curl -sS -o /dev/null -w 'mixins.js:%{http_code}\n' 'https://stg.tyresonline.sa/static/version1781772583/frontend/Hditsol/tyresonline/en_US/mage/requirejs/mixins.js'
curl -sS -o /dev/null -w 'mixins.min.js:%{http_code}\n' 'https://stg.tyresonline.sa/static/version1781772583/frontend/Hditsol/tyresonline/en_US/mage/requirejs/mixins.min.js'

echo "=== exceptions after 10:30 ==="
grep '^\[2026-06-18T1' /var/www/magento/var/log/exception.log | tail -15 | cut -c1-250

echo "=== checkout static js ==="
curl -sS -o /dev/null -w 'checkout.min.js:%{http_code}\n' 'https://stg.tyresonline.sa/static/version1781772583/frontend/Hditsol/tyresonline/en_US/Magento_Checkout/js/view/checkout.min.js' 2>/dev/null || true
curl -sS -o /dev/null -w 'onestep:%{http_code}\n' 'https://stg.tyresonline.sa/en/onestepcheckout/' 2>/dev/null || true

echo "=== deploy mode ==="
cd /var/www/magento && sudo -u www-data php bin/magento deploy:mode:show 2>/dev/null
