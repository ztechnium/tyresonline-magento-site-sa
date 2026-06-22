#!/bin/bash
URL="${1:-https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?nocache=$(date +%s)}"
curl -s "$URL" -H 'Cache-Control: no-cache' -o /tmp/car-tyres.html
echo "size: $(wc -c < /tmp/car-tyres.html)"
echo "SAR prices: $(grep -o 'SAR [0-9][0-9.,]*' /tmp/car-tyres.html | wc -l)"
echo "product-item li: $(grep -c 'li class="product-item"' /tmp/car-tyres.html)"
echo "product-box divs: $(grep -c 'class="product-box' /tmp/car-tyres.html)"
echo "doctype count: $(grep -ci doctype /tmp/car-tyres.html)"
echo "--- prices ---"
grep -o 'SAR [0-9][0-9.,]*' /tmp/car-tyres.html
