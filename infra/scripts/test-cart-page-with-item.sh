#!/bin/bash
set -euo pipefail
bash /tmp/test-add-product-4713.sh 2>&1 | tail -3
CJ=/tmp/cart_totals_cj
curl -sS -c "$CJ" -b "$CJ" https://stg.tyresonline.sa/en/checkout/cart/ -o /tmp/cart_with_item.html
echo "cart_bytes=$(wc -c < /tmp/cart_with_item.html)"
echo "checkoutConfig_count=$(grep -c checkoutConfig /tmp/cart_with_item.html || true)"
echo "cart-totals_count=$(grep -c cart-totals /tmp/cart_with_item.html || true)"
echo "subtotal_rows=$(grep -ci subtotal /tmp/cart_with_item.html || true)"
grep -o 'window\.checkoutConfig = {[^;]*' /tmp/cart_with_item.html | head -c 300 || echo "no checkoutConfig js"
echo
grep -E 'grand\.totals|totals\.grand|data-th=.Grand' /tmp/cart_with_item.html | head -3 || true
grep -oP 'class="price"[^>]*>\s*\K[^<]+' /tmp/cart_with_item.html | head -5 || true
sudo tail -8 /var/www/magento/var/log/system.log | grep -iE 'Composite|Shipping|CRITICAL' || true
