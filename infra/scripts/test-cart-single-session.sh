#!/bin/bash
set -euo pipefail
CJ=/tmp/cart_full_test
rm -f "$CJ"
curl -sS -c "$CJ" -b "$CJ" https://stg.tyresonline.sa/en/checkout/cart/ -o /tmp/c1.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/c1.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST https://stg.tyresonline.sa/en/checkout/cart/add/ \
  -H 'Referer: https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' \
  --data-urlencode 'product=4715' --data-urlencode 'qty=4' --data-urlencode "form_key=$FK" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" https://stg.tyresonline.sa/en/checkout/cart/ -o /tmp/cart_item.html
echo "bytes=$(wc -c < /tmp/cart_item.html)"
echo "product-items=$(grep -c product-item-name /tmp/cart_item.html || true)"
echo "checkoutConfig=$(grep -c checkoutConfig /tmp/cart_item.html || true)"
grep -o 'block-totals\|totals\.grand\|cart-summary\|data-bind="text: getValue' /tmp/cart_item.html | sort | uniq -c
grep -A2 'cart-summary' /tmp/cart_item.html | head -20
grep -E 'Subtotal|Grand Total|VAT|Shipping' /tmp/cart_item.html | head -15
