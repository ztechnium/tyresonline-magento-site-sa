#!/bin/bash
CJ=/tmp/co2
rm -f "$CJ"
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/h.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/h.html | head -1)
echo "FK=$FK"
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d 'product=4898' -d 'qty=4' -d "form_key=$FK" -o /dev/null -w 'add:%{http_code}\n'
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/cart.html
FK2=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/cart.html | head -1)
echo "FK2=$FK2"
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/installer/ajax/savecartinstaller/' \
  --data-urlencode 'pickup_store=1' \
  --data-urlencode 'pickup_date=2026-06-25' \
  --data-urlencode 'pickup_time=09:00 - 11:00' \
  --data-urlencode "form_key=$FK2" \
  -o /tmp/save.json -w 'save:%{http_code}\n'
echo "save_response=$(cat /tmp/save.json)"
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' \
  -o /tmp/co.html -w 'checkout:%{http_code} url:%{url_effective}\n'
grep -oE '<title>[^<]+</title>' /tmp/co.html
echo "config=$(grep -c checkoutConfig /tmp/co.html || true)"
echo "checkout_div=$(grep -c 'id=\"checkout\"' /tmp/co.html || true)"
echo "opc=$(grep -c 'opc-wrapper\|checkout-container' /tmp/co.html || true)"
grep -o 'body[^>]*class="[^"]*"' /tmp/co.html | head -1
echo "main_snippet:"
grep -oP '(?<=column main">).{0,500}' /tmp/co.html | head -c 500
