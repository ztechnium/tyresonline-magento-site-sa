#!/bin/bash
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' -o /tmp/car_final.html
echo "bytes: $(wc -c < /tmp/car_final.html)"
echo "product-item-info: $(grep -c 'product-item-info' /tmp/car_final.html)"
echo "product-item-name: $(grep -c 'product-item-name' /tmp/car_final.html)"
echo "empty msg: $(grep -ci \"can't find\" /tmp/car_final.html)"
grep -o 'categorypath-[^" ]*' /tmp/car_final.html | head -3
grep -i 'TYO-' /tmp/car_final.html | head -3
grep -i 'toolbar-amount\|Items ' /tmp/car_final.html | head -5
