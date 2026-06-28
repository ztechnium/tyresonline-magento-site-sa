#!/bin/bash
CJ=/tmp/atc4713
rm -f "$CJ"
curl -sS -c "$CJ" -b "$CJ" https://stg.tyresonline.sa/en/checkout/cart/ -o /tmp/c.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/c.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST https://stg.tyresonline.sa/en/checkout/cart/add/ \
  -H 'Referer: https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' \
  --data-urlencode 'product=4715' --data-urlencode 'qty=4' --data-urlencode "form_key=$FK" \
  -D /tmp/h.hdr -o /dev/null
grep -i mage-messages /tmp/h.hdr | tr -d '\r'
ITEMS=$(curl -sS -b "$CJ" https://stg.tyresonline.sa/en/checkout/cart/ | grep -c product-item-name || echo 0)
echo "cart_items=$ITEMS"
