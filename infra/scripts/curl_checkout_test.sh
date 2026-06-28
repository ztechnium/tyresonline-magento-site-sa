#!/bin/bash
set -e
CJ=/tmp/co_curl_test
PRODUCT=4715
rm -f "$CJ"
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/home.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/home.html | head -1)
echo "form_key=$FK"
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /tmp/add.html -w 'add:%{http_code}\n'
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/installer/ajax/savecartinstaller/' \
  -H 'Content-Type: application/json' -H 'X-Requested-With: XMLHttpRequest' \
  -d '{"pickup_store":"1","pickup_date":"2026-06-20","pickup_time":"11:00 AM - 01:00 PM"}' \
  -o /tmp/save.json -w 'save:%{http_code}\n'
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' \
  -o /tmp/co_curl.html -w 'checkout:%{http_code} size:%{size_download}\n'
echo "checkout_div=$(grep -c 'id=\"checkout\"' /tmp/co_curl.html || true)"
echo "config=$(grep -c 'window.checkoutConfig' /tmp/co_curl.html || true)"
echo "opc=$(grep -c 'opc-wrapper' /tmp/co_curl.html || true)"
