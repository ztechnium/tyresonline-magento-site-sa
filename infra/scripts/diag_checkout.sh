#!/bin/bash
set -e
CJ=/tmp/co_test_$$
PRODUCT=4898

curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/home_co.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/home_co.html | head -1)
echo "form_key=$FK"

curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /tmp/add_co.html -w 'add_status:%{http_code}\n'

curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' \
  -o /tmp/checkout_live.html -w 'checkout_status:%{http_code} final_url:%{url_effective} size:%{size_download}\n'

echo "title=$(grep -oE '<title>[^<]+</title>' /tmp/checkout_live.html | head -1)"
echo "body_class=$(grep -oP 'body[^>]*class="\K[^"]+' /tmp/checkout_live.html | head -1)"
echo "checkoutConfig=$(grep -c 'window.checkoutConfig' /tmp/checkout_live.html || true)"
echo "checkout_div=$(grep -c 'id=\"checkout\"' /tmp/checkout_live.html || true)"
echo "opc=$(grep -c 'opc-wrapper\|checkout-container\|onestepcheckout' /tmp/checkout_live.html || true)"
echo "fatal=$(grep -ci 'fatal error\|CredisException\|Uncaught' /tmp/checkout_live.html || true)"
echo "html_size=$(wc -c < /tmp/checkout_live.html)"

echo "--- last 5 exceptions ---"
tail -5 /var/www/magento/var/log/exception.log 2>/dev/null | while read -r line; do echo "$line" | cut -c1-300; done

echo "--- recent exception messages ---"
grep -oP '"exception":"\[object\] \(\K[^)]+' /var/www/magento/var/log/exception.log | tail -5
