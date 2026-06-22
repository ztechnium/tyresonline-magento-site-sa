#!/bin/bash
CJ=/tmp/cartj2
PRODUCT=4898
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/home.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/home.html | head -1)
echo "form_key=$FK"
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /tmp/addresp.html -w 'add:%{http_code}\n'
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/cart.html -w 'cart:%{http_code}\n'
grep -c 'cart-item' /tmp/cart.html || true
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' -o /tmp/co.html -w 'checkout:%{http_code}\n'
grep -oE '<title>[^<]+</title>' /tmp/co.html || true
grep -c 'checkout-index-index\|opc-wrapper\|checkout-container' /tmp/co.html || true
wc -c /tmp/co.html
