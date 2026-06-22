#!/bin/bash
wc -c /tmp/co_full.html
echo "=== body tail ==="
tail -c 3000 /tmp/co_full.html
echo
echo "=== search key checkout elements ==="
grep -n 'checkout.root\|id=\"checkout\"\|checkoutConfig\|x-magento-init\|Magento_Checkout' /tmp/co_full.html | head -30
echo "=== exceptions last 2 min ==="
date -u
tail -3 /var/www/magento/var/log/exception.log | cut -c1-350
