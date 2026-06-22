#!/bin/bash
echo "=== recent redis exceptions ==="
grep -c '127.0.0.1:6379' /var/www/magento/var/log/exception.log || true
tail -1 /var/www/magento/var/log/exception.log | cut -c1-200
echo
echo "=== test Onepage jsLayout ==="
sudo -u www-data php /tmp/test_checkout_jslayout.php 2>&1
