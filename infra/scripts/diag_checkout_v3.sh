#!/bin/bash
CJ=/tmp/co3
rm -f "$CJ"
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/h.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/h.html | head -1)
curl -sS -L -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d 'product=4898' -d 'qty=4' -d "form_key=$FK" -o /tmp/add.html -w 'add:%{http_code}\n'
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/cart.html
echo "cart_items=$(grep -ci 'cart.item\|product-item-name' /tmp/cart.html)"
FK2=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/cart.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/installer/ajax/savecartinstaller/' \
  --data-urlencode 'pickup_store=1' \
  --data-urlencode 'pickup_date=2026-06-25' \
  --data-urlencode 'pickup_time=09:00 - 11:00' \
  --data-urlencode "form_key=$FK2" \
  -o /tmp/save.json -w 'save:%{http_code}\n'
echo "save=$(cat /tmp/save.json)"
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' \
  -o /tmp/co.html -w 'checkout:%{http_code} url:%{url_effective} size:%{size_download}\n'
grep -oE '<title>[^<]+</title>' /tmp/co.html
echo "config=$(grep -c checkoutConfig /tmp/co.html || true)"
echo "checkout_div=$(grep -c 'id=\"checkout\"' /tmp/co.html || true)"
echo "opc=$(grep -c 'opc-wrapper\|checkout-container' /tmp/co.html || true)"
grep -o 'body[^>]*class="[^"]*"' /tmp/co.html | head -1
python3 - <<'PY'
import re
html=open('/tmp/co.html').read()
m=re.search(r'column main">(.*?)</div>\s*</div>\s*</main>', html, re.S)
print('main_snippet:', re.sub(r'<[^>]+>','', m.group(1))[:400] if m else 'NONE')
PY
