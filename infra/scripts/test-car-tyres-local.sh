#!/bin/bash
LOG=/var/www/magento/var/log/exception.log
SZ1=$(stat -c%s "$LOG")
curl -s -H 'Host: stg.tyresonline.sa' 'http://127.0.0.1/en/all-tyres/car-tyres.html' -o /tmp/car-local.html
SZ2=$(stat -c%s "$LOG")
echo "log growth: $((SZ2 - SZ1)) bytes"
grep -c 'product-item' /tmp/car-local.html
grep -ci doctype /tmp/car-local.html
tail -c 2500 "$LOG"
