#!/bin/bash
set -euo pipefail
CJ=/tmp/cart_fresh_test
rm -f "$CJ"
curl -sS -c "$CJ" -b "$CJ" "https://stg.tyresonline.sa/en/checkout/cart/?nocache=$(date +%s)" -o /tmp/c0.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/c0.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST "https://stg.tyresonline.sa/en/checkout/cart/add/" \
  -H 'Referer: https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' \
  --data-urlencode 'product=4715' --data-urlencode 'qty=4' --data-urlencode "form_key=$FK" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" "https://stg.tyresonline.sa/en/checkout/cart/?t=$(date +%s)" -o /tmp/cfresh.html
python3 <<'PY'
h=open('/tmp/cfresh.html').read()
for pat in ['checkoutConfig','block-shipping','block-summary','product-item-name','id="cart-totals"','Grand Total']:
    print(f'{pat}={h.count(pat)}')
PY
tail -3 /var/www/magento/var/log/system.log | grep -i shipping || echo 'no recent shipping errors'
