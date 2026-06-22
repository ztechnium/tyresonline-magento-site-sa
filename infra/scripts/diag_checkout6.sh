#!/bin/bash
echo "=== grep content container blocks in html ==="
grep -n 'column main\|class=\"checkout\|main content\|id=\"checkout\"\|checkoutLoader\|onepage' /tmp/co_full.html | head -30

echo "=== amasty pagespeed ==="
grep -r 'Amasty\\PageSpeed' /var/www/magento/app/etc/config.php 2>/dev/null | head -3
cd /var/www/magento && sudo -u www-data php bin/magento config:show amasty_pagespeed/general/enabled 2>/dev/null || true

echo "=== fpc checkout ==="
cd /var/www/magento && sudo -u www-data php bin/magento cache:status 2>/dev/null | grep full_page
