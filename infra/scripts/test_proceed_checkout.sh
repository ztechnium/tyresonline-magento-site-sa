#!/bin/bash
CJ=/tmp/cartj6
PRODUCT=4898
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/home.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/home.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/cart3.html
COURL=$(grep -oP 'href="\K[^"]*checkout[^"]*' /tmp/cart3.html | grep -v cart | head -1)
echo "checkout_link=$COURL"
curl -sS -L -c "$CJ" -b "$CJ" "$COURL" -o /tmp/co2.html -w 'status:%{http_code} url:%{url_effective} size:%{size_download}\n'
grep -oE '<title>[^<]+</title>' /tmp/co2.html
grep -c 'window.checkoutConfig' /tmp/co2.html
grep -c 'checkout-container\|opc-wrapper' /tmp/co2.html
grep -c 'Fatal error\|CredisException' /tmp/co2.html
wc -c /tmp/co2.html
