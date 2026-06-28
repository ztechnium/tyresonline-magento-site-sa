#!/bin/bash
set -euo pipefail
BASE='https://stg.tyresonline.sa/en'
CJ=/tmp/atc_test.txt
rm -f "$CJ"

echo "=== 1. Session + form_key from cart (non-FPC) ==="
curl -sS -c "$CJ" -b "$CJ" "$BASE/checkout/cart/" -o /tmp/atc_cart.html
FK_CART=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/atc_cart.html | head -1)
echo "cart form_key=${FK_CART:0:16}..."

echo "=== 2. PLP form_key (may be FPC stale) ==="
curl -sS -c "$CJ" -b "$CJ" "$BASE/all-tyres/car-tyres.html" -o /tmp/atc_plp.html
FK_PLP=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/atc_plp.html | head -1)
echo "plp form_key=${FK_PLP:0:16}..."
echo "match: $([ "$FK_CART" = "$FK_PLP" ] && echo YES || echo NO - STALE KEY)"

PID=$(grep -oP 'name="product" value="\K[0-9]+' /tmp/atc_plp.html | head -1)
echo "product_id=$PID"

echo "=== 3. Ajax add (Chetu route) with cart form_key ==="
curl -sS -c "$CJ" -b "$CJ" -X POST "$BASE/ajaxcart/cart/showPopup/" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H "Referer: $BASE/all-tyres/car-tyres.html" \
  -H 'X-Requested-With: XMLHttpRequest' \
  --data-urlencode "product=$PID" --data-urlencode "qty=4" --data-urlencode "form_key=$FK_CART" \
  -o /tmp/atc_ajax.json -w 'ajax http=%{http_code} size=%{size_download}\n'
head -c 500 /tmp/atc_ajax.json; echo

echo "=== 4. Direct cart/add with cart form_key ==="
curl -sS -c "$CJ" -b "$CJ" -X POST "$BASE/checkout/cart/add/" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H "Referer: $BASE/all-tyres/car-tyres.html" \
  --data-urlencode "product=$PID" --data-urlencode "qty=4" --data-urlencode "form_key=$FK_CART" \
  -D /tmp/atc_add.hdr -o /tmp/atc_add.body -w 'add http=%{http_code}\n'
grep -iE 'location:|mage-messages' /tmp/atc_add.hdr | tr -d '\r' | head -3

echo "=== 5. Direct cart/add with STALE plp form_key ==="
curl -sS -c "$CJ" -b "$CJ" -X POST "$BASE/checkout/cart/add/" \
  --data-urlencode "product=$PID" --data-urlencode "qty=4" --data-urlencode "form_key=$FK_PLP" \
  -D /tmp/atc_bad.hdr -o /dev/null -w 'bad http=%{http_code}\n'
grep -i mage-messages /tmp/atc_bad.hdr | tr -d '\r' | head -1

echo "=== 6. Cart items ==="
curl -sS -b "$CJ" "$BASE/checkout/cart/" | grep -c 'product-item-name' || echo 0
