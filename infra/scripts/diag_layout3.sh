#!/bin/bash
echo "=== checkout page layout ==="
cat /var/www/magento/vendor/magento/module-checkout/view/frontend/page_layout/checkout.xml

echo "=== magento checkout_index_index body start ==="
grep -A15 '<body>' /var/www/magento/vendor/magento/module-checkout/view/frontend/layout/checkout_index_index.xml | head -20

echo "=== grep checkout.root in co_full ==="
grep -n 'checkout.root\|onepage.phtml\|id=\"checkout\"' /tmp/co_full.html

echo "=== content container in co_full ==="
grep -oP '(?<=<div class="column main">).{0,5000}' /tmp/co_full.html | head -c 3000
