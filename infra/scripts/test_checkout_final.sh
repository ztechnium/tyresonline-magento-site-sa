#!/bin/bash
CJ=/tmp/cartj4
PRODUCT=4898
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/home.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/home.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /dev/null
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' \
  -o /tmp/checkout_final.html -w 'final_url:%{url_effective} status:%{http_code} size:%{size_download}\n'
grep -oE '<title>[^<]+</title>' /tmp/checkout_final.html
grep -o 'checkout-index-index' /tmp/checkout_final.html | head -1
grep -c 'window.checkoutConfig' /tmp/checkout_final.html
grep -c 'opc-wrapper' /tmp/checkout_final.html
grep -c 'checkout-container' /tmp/checkout_final.html
grep -c 'Fatal error\|CredisException' /tmp/checkout_final.html
wc -c /tmp/checkout_final.html
