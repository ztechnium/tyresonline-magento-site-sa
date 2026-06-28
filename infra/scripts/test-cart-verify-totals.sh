#!/bin/bash
set -euo pipefail
CJ=/tmp/cart_verify
rm -f "$CJ"
curl -sS -c "$CJ" -b "$CJ" "https://stg.tyresonline.sa/en/checkout/cart/" -o /tmp/v0.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/v0.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST "https://stg.tyresonline.sa/en/checkout/cart/add/" \
  -H 'Referer: https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' \
  --data-urlencode 'product=4715' --data-urlencode 'qty=4' --data-urlencode "form_key=$FK" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" "https://stg.tyresonline.sa/en/checkout/cart/" -o /tmp/vcart.html
python3 <<'PY'
import re
h=open('/tmp/vcart.html').read()
print('checkoutConfig', h.count('checkoutConfig'))
print('totalsData', 'totalsData' in h or 'quoteData' in h)
m=re.search(r'window\.checkoutConfig\s*=\s*(\{)', h)
print('has_window_checkoutConfig', bool(m))
for label in ['ITEM(S) TOTAL', 'Grand Total', 'VAT', 'Installation Fee', 'SAR']:
    print(label, h.count(label))
# extract quote grand total from config if present
m2=re.search(r'"grand_total"\s*:\s*"([^"]+)"', h)
print('grand_total_value', m2.group(1) if m2 else 'not found')
PY
