#!/bin/bash
set -euo pipefail
URL="${1:-https://stg.tyresonline.sa/all-tyres/car-tyres.html}"
TMP=$(mktemp)
HDR=$(mktemp)

echo "=== Page: $URL ==="
curl -sSL "$URL" -o "$TMP"
curl -sSI "$URL" -o "$HDR"

echo ""
echo "=== CSP check (*.tyresonline.sa in img-src / style-src) ==="
if grep -qi 'img-src[^;]*\*\.tyresonline\.sa' "$HDR"; then echo "PASS img-src"; else echo "FAIL img-src missing *.tyresonline.sa"; fi
if grep -qi 'style-src[^;]*\*\.tyresonline\.sa' "$HDR"; then echo "PASS style-src"; else echo "FAIL style-src missing *.tyresonline.sa"; fi

echo ""
echo "=== Asset hosts in HTML ==="
grep -oE 'https://[^\"'\'' ]+\.(css|jpg|jpeg|png|webp|svg|js)' "$TMP" | sed 's|\?.*||' | awk -F/ '{print $3}' | sort | uniq -c | sort -rn | head -10

echo ""
echo "=== Sample CDN asset HEAD checks ==="
FAIL=0
while IFS= read -r asset; do
  code=$(curl -sSI "$asset" | awk 'NR==1{print $2}')
  if [[ "$code" != "200" ]]; then
    echo "FAIL $code $asset"
    FAIL=$((FAIL+1))
  else
    echo "PASS 200 $asset"
  fi
done < <(grep -oE 'https://cdn\.tyresonline\.sa[^\"'\'' ]+\.(css|jpg|jpeg|png|webp|svg)' "$TMP" | head -8)

echo ""
echo "=== Product images in HTML ==="
echo -n "cdn product images: "; grep -c 'cdn.tyresonline.sa/media/catalog/product' "$TMP" || true
echo -n "product-item blocks: "; grep -c 'product-item' "$TMP" || true

rm -f "$TMP" "$HDR"
exit "$FAIL"
