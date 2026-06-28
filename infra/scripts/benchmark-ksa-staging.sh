#!/bin/bash
set -eu
N="${1:-5}"

bench_page() {
  local name="$1"
  local url="$2"
  echo ""
  echo "=== $name ==="
  echo "$url"
  i=1
  while [ "$i" -le "$N" ]; do
    curl -sS -o /dev/null -w "run#$i ttfb=%{time_starttransfer}s total=%{time_total}s size=%{size_download}B http=%{http_code}\n" "$url"
    i=$((i+1))
  done
}

bench_asset() {
  local name="$1"
  local url="$2"
  echo ""
  echo "--- $name ---"
  i=1
  while [ "$i" -le 2 ]; do
    hdr=$(mktemp)
    curl -sS -D "$hdr" -o /dev/null -w "run#$i ttfb=%{time_starttransfer}s total=%{time_total}s size=%{size_download}B http=%{http_code}\n" "$url"
    grep -iE '^(x-cache|age|cache-control):' "$hdr" 2>/dev/null | head -2 | tr -d '\r' || true
    rm -f "$hdr"
    i=$((i+1))
  done
}

echo "KSA benchmark $(date -u +%Y-%m-%dT%H:%M:%SZ) runs=$N"

bench_page "Homepage" "https://stg.tyresonline.sa/"
bench_page "PLP EN car-tyres" "https://stg.tyresonline.sa/all-tyres/car-tyres.html"
bench_page "PLP AR car-tyres" "https://stg.tyresonline.sa/ar/all-tyres/car-tyres.html"

CSS_CDN="https://cdn.tyresonline.sa/static/version1782431267/_cache/merged/f6a0af642556094326f4381cfb671f40.min.css"
CSS_ORIGIN="https://stg.tyresonline.sa/static/version1782431267/_cache/merged/f6a0af642556094326f4381cfb671f40.min.css"
IMG_CDN="https://cdn.tyresonline.sa/media/catalog/product/0/-/0-01-21-0-0-03-1-0566_1_.jpg"
IMG_ORIGIN="https://stg.tyresonline.sa/media/catalog/product/0/-/0-01-21-0-0-03-1-0566_1_.jpg"

bench_asset "Merged CSS (CDN)" "$CSS_CDN"
bench_asset "Merged CSS (origin)" "$CSS_ORIGIN"
bench_asset "Product image (CDN/S3)" "$IMG_CDN"
bench_asset "Product image (origin EC2)" "$IMG_ORIGIN"

HTML=$(curl -sS "https://stg.tyresonline.sa/all-tyres/car-tyres.html")
echo ""
echo "=== PLP HTML stats ==="
echo "bytes: $(printf '%s' "$HTML" | wc -c)"
echo "cdn refs: $(printf '%s' "$HTML" | grep -o 'cdn.tyresonline.sa' | wc -l)"
echo "product imgs: $(printf '%s' "$HTML" | grep -o 'cdn.tyresonline.sa/media/catalog/product' | wc -l)"
echo "Done."
