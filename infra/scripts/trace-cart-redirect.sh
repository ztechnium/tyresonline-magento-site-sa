#!/bin/bash
COOKIE=/tmp/ct3.txt
rm -f "$COOKIE"
curl -sSL -c "$COOKIE" -b "$COOKIE" 'https://stg.tyresonline.sa/en/alpha-185701488aggressor-zp01-2025.html' -o /tmp/p.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/p.html | head -1)
echo "POST add (no follow):"
curl -sS -c "$COOKIE" -b "$COOKIE" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "product=4713" --data-urlencode "qty=1" --data-urlencode "form_key=$FK" \
  -D - -o /dev/null --max-redirs 0 2>&1 | head -20

echo ""
echo "Trace redirect chain (max 5):"
curl -sS -c "$COOKIE" -b "$COOKIE" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "product=4713" --data-urlencode "qty=1" --data-urlencode "form_key=$FK" \
  -L --max-redirs 5 -D - -o /dev/null 2>&1 | grep -iE '^HTTP|^location:' | head -20
