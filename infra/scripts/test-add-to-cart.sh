#!/bin/bash
set -euo pipefail
cd /var/www/magento
DB="mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -N"

SKU=$($DB -e "SELECT cpe.sku FROM catalog_product_entity cpe JOIN catalog_category_product cp ON cp.product_id=cpe.entity_id AND cp.category_id=1945 JOIN cataloginventory_stock_status st ON st.product_id=cpe.entity_id AND st.stock_status=1 LIMIT 1")
PID=$($DB -e "SELECT entity_id FROM catalog_product_entity WHERE sku='$SKU'")
PATH=$($DB -e "SELECT request_path FROM url_rewrite WHERE entity_type='product' AND entity_id=$PID AND store_id=1 AND redirect_type=0 LIMIT 1")
PDP="https://stg.tyresonline.sa/en/$PATH"
echo "SKU=$SKU PID=$PID"
echo "PDP=$PDP"

COOKIE=/tmp/carttest.txt
rm -f "$COOKIE"
curl -sSL -c "$COOKIE" -b "$COOKIE" "$PDP" -o /tmp/pdp.html
FORMKEY=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/pdp.html | head -1)
echo "form_key=${FORMKEY:0:16}..."

curl -sSL -c "$COOKIE" -b "$COOKIE" -X POST \
  "https://stg.tyresonline.sa/en/checkout/cart/add/" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  --data-urlencode "product=$PID" \
  --data-urlencode "qty=1" \
  --data-urlencode "form_key=$FORMKEY" \
  -D /tmp/add.hdr -o /tmp/add.body -w "add_http=%{http_code} redirect=%{redirect_url}\n"

head -8 /tmp/add.hdr
echo "body snippet:"; head -c 300 /tmp/add.body; echo

curl -sSL -b "$COOKIE" "https://stg.tyresonline.sa/en/checkout/cart/" -o /tmp/cart.html
if grep -qi 'cart-empty\|You have no items' /tmp/cart.html; then echo "CART: EMPTY"; else echo "CART: HAS ITEMS"; fi
grep -o 'product-item-name\|cart item' /tmp/cart.html | head -3

echo "=== Recent exception.log ==="
tail -30 var/log/exception.log 2>/dev/null || true

echo "=== Recent system.log cart ==="
grep -iE 'cart|quote|session' var/log/system.log 2>/dev/null | tail -10 || true
