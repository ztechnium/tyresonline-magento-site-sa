#!/bin/bash
CJ=/tmp/cofull
PRODUCT=4898
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/h2.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/h2.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/installer/ajax/savecartinstaller/' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -d "pickup_store=1&pickup_date=2026-06-20&pickup_time=09:00 - 11:00&form_key=$FK" \
  -o /tmp/installer_resp.json -w 'installer:%{http_code}\n'
cat /tmp/installer_resp.json | head -c 400; echo
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' \
  -o /tmp/co_full.html -w 'checkout:%{http_code} url:%{url_effective} size:%{size_download}\n'
grep -oE '<title>[^<]+</title>' /tmp/co_full.html
grep -o 'body[^>]*class="[^"]*"' /tmp/co_full.html | head -1
grep -c 'window.checkoutConfig' /tmp/co_full.html
grep -c 'id=\"checkout\"' /tmp/co_full.html
grep -ci 'fatal\|exception\|CredisException' /tmp/co_full.html
# extract script srcs that 404
grep -oP 'src=\"\K[^\"]+checkout[^\"]*' /tmp/co_full.html | head -5
