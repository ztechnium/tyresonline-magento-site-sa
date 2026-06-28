#!/usr/bin/env bash
set -euo pipefail
echo '=== exception.log tail ==='
sudo tail -100 /var/www/magento/var/log/exception.log 2>/dev/null || echo 'no exception.log'
echo '=== system.log errors ==='
sudo grep -iE 'CRITICAL|ERROR|Fatal|car-tyres|catalog_category' /var/www/magento/var/log/system.log 2>/dev/null | tail -40 || true
echo '=== apache error ==='
sudo tail -50 /var/log/apache2/error.log 2>/dev/null || true
echo '=== php log ==='
sudo tail -30 /var/log/php*.log 2>/dev/null || true
echo '=== localhost page2 ==='
curl -sI -H 'Host: stg.tyresonline.ae' 'http://127.0.0.1/en/all-tyres/car-tyres.html?p=2' | head -8
echo '=== disk/mem ==='
df -h /var/www/magento | tail -1
free -m | head -2
