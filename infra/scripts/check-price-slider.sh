#!/bin/bash
curl -sS -m 120 -L -o /tmp/price-check.html https://stg.tyresonline.sa/en/all-tyres/car-tyres.html
echo "priceslider refs:" $(grep -c priceslider /tmp/price-check.html)
grep priceslider /tmp/price-check.html | head -2
echo "---slider block---"
grep -A60 'range-price-slider-price' /tmp/price-check.html | head -65
echo "---jquery ui---"
grep -c jquery-ui /tmp/price-check.html
grep jquery-ui /tmp/price-check.html | head -3
