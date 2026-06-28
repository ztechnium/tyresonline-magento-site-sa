#!/bin/bash
set -euo pipefail
COOKIE=/tmp/ct2.txt
rm -f "$COOKIE"
curl -sSL -c "$COOKIE" -b "$COOKIE" 'https://stg.tyresonline.sa/en/alpha-185701488aggressor-zp01-2025.html' -o /tmp/p.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/p.html | head -1)
echo "form_key=$FK"
curl -sSL -c "$COOKIE" -b "$COOKIE" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "product=4713" --data-urlencode "qty=1" --data-urlencode "form_key=$FK" \
  -D /tmp/h.txt -o /tmp/b.txt -w 'code=%{http_code}\n'
grep -i '^location:' /tmp/h.txt || true
echo "Cookies:"; cat "$COOKIE"
grep -iE 'message-error|session has expired|addCartSuccess|added.*cart' /tmp/b.txt | head -3 || true
curl -sSL -b "$COOKIE" 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/cart.html
if grep -qi 'cart-empty\|You have no items' /tmp/cart.html; then echo HTTP_CART=EMPTY; else echo HTTP_CART=OK; fi
echo "=== last exception ==="
tail -2 /var/www/magento/var/log/exception.log 2>/dev/null || true
