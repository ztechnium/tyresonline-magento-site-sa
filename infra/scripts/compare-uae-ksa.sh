#!/bin/bash
echo "=== KSA ==="
grep -c 'product-box modern-layout' /tmp/ksa-after-sync.html
grep -c 'list-view-mobile' /tmp/ksa-after-sync.html
grep -ci 'doctype html' /tmp/ksa-after-sync.html
grep -c 'li class="product-item"' /tmp/ksa-after-sync.html
grep -oE 'SAR [0-9][0-9.,]+' /tmp/ksa-after-sync.html | head -15
echo "=== UAE ==="
grep -c 'product-box modern-layout' /tmp/uae-car.html
grep -c 'list-view-mobile' /tmp/uae-car.html
grep -ci 'doctype html' /tmp/uae-car.html
grep -oE 'AED [0-9][0-9.,]+' /tmp/uae-car.html | head -15
curl -sI 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' | grep -iE 'cache|cf-|age|x-magento'
