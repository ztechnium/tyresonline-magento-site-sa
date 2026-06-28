#!/bin/bash
set -euo pipefail
CJ=/tmp/cart_local_test
rm -f "$CJ"
BASE="http://127.0.0.1/en/checkout/cart/"
HDR=( -H 'Host: stg.tyresonline.sa' )
curl -sS -c "$CJ" -b "$CJ" "${HDR[@]}" "${BASE}?n=1" -o /tmp/cl0.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/cl0.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST "${HDR[@]}" "http://127.0.0.1/en/checkout/cart/add/" \
  -H 'Referer: http://stg.tyresonline.sa/en/all-tyres/car-tyres.html' \
  --data-urlencode 'product=4715' --data-urlencode 'qty=4' --data-urlencode "form_key=$FK" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" "${HDR[@]}" "${BASE}?t=$(date +%s)" -o /tmp/clcart.html
python3 <<'PY'
h=open('/tmp/clcart.html').read()
for pat in ['checkoutConfig','checkout.cart.checkout_config','checkout_config','product-item-name']:
    print(f'{pat}={h.count(pat)}')
PY
