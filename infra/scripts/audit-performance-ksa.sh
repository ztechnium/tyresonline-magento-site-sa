#!/bin/bash
# KSA staging performance audit — run on EC2 or locally against stg.tyresonline.sa
set -uo pipefail
BASE="https://stg.tyresonline.sa"
CDN="https://cdn.tyresonline.sa"
CURL="${CURL:-/usr/bin/curl}"
N="${1:-3}"

section() { echo ""; echo "========== $1 =========="; }

bench_url() {
  local label="$1" url="$2"
  echo "--- $label ---"
  echo "$url"
  local i=1 total=0 ttfb=0
  while [ "$i" -le "$N" ]; do
    local hdr=$(mktemp)
    local line
    line=$($CURL -sS -D "$hdr" -o /dev/null -w "run#$i dns=%{time_namelookup}s connect=%{time_connect}s tls=%{time_appconnect}s ttfb=%{time_starttransfer}s total=%{time_total}s size=%{size_download} http=%{http_code}" "$url" 2>/dev/null || echo "run#$i FAILED")
    echo "$line"
    grep -iE '^(cache-control|x-magento-cache|x-cache|age|set-cookie|content-encoding|server):' "$hdr" 2>/dev/null | head -6 | tr -d '\r' || true
    rm -f "$hdr"
    i=$((i+1))
  done
}

section "TIMING $(date -u +%Y-%m-%dT%H:%M:%SZ) runs=$N"
bench_url "Homepage" "$BASE/"
bench_url "Homepage EN" "$BASE/en/"
bench_url "PLP EN car-tyres" "$BASE/all-tyres/car-tyres.html"
bench_url "PLP AR car-tyres" "$BASE/ar/all-tyres/car-tyres.html"
bench_url "Cart (cold session)" "$BASE/en/checkout/cart/"
bench_url "Store locator" "$BASE/en/storelocator"

section "CACHE HEADERS (single request each)"
for u in "$BASE/" "$BASE/all-tyres/car-tyres.html" "$BASE/ar/all-tyres/car-tyres.html"; do
  echo "URL: $u"
  $CURL -sI "$u" 2>/dev/null | grep -iE '^(HTTP|cache-control|x-magento|age|set-cookie|content-length|content-encoding):' | tr -d '\r' || true
done

section "HTML PAGE WEIGHT"
for path in "/" "/all-tyres/car-tyres.html" "/ar/all-tyres/car-tyres.html"; do
  html=$($CURL -sS "$BASE$path" 2>/dev/null || true)
  bytes=$(printf '%s' "$html" | wc -c)
  scripts=$(printf '%s' "$html" | grep -oE '<script[^>]+src=' | wc -l)
  styles=$(printf '%s' "$html" | grep -oE '<link[^>]+stylesheet' | wc -l)
  imgs=$(printf '%s' "$html" | grep -oE '<img[^>]+' | wc -l)
  cdn=$(printf '%s' "$html" | grep -o 'cdn.tyresonline.sa' | wc -l)
  inline_js=$(printf '%s' "$html" | grep -o '<script' | wc -l)
  echo "$path: ${bytes}B | link-css=$styles script-src=$scripts img-tags=$imgs inline-script-tags=$inline_js cdn-refs=$cdn"
done

section "STATIC ASSETS"
# Discover current merged CSS from PLP
PLP=$($CURL -sS "$BASE/all-tyres/car-tyres.html" 2>/dev/null || true)
CSS=$(printf '%s' "$PLP" | grep -oE 'https?://[^"]+\.min\.css' | head -1)
JS=$(printf '%s' "$PLP" | grep -oE 'https?://[^"]+requirejs[^"]+\.js' | head -1)
[ -n "$CSS" ] && bench_url "Merged CSS" "$CSS"
[ -n "$JS" ] && bench_url "RequireJS loader" "$JS"
bench_url "Product image CDN" "$CDN/media/catalog/product/0/-/0-01-21-0-0-03-1-0566_1_.jpg"
bench_url "KSA map CDN" "$CDN/media/images/icon/ksa-map.png"

section "REDIRECT CHAIN"
$CURL -sI -L -w "final_url=%{url_effective} redirects=%{num_redirects} total=%{time_total}s\n" -o /dev/null "$BASE/en/" 2>/dev/null || true

section "SERVER-SIDE (localhost only)"
if [ -f /var/www/magento/app/bootstrap.php ]; then
  echo "Local curl from EC2:"
  for u in "http://127.0.0.1/" "http://127.0.0.1/all-tyres/car-tyres.html"; do
    $CURL -sS -o /dev/null -w "$u ttfb=%{time_starttransfer}s total=%{time_total}s size=%{size_download}\n" "$u" -H "Host: stg.tyresonline.sa" 2>/dev/null || true
  done
  if [ -f /tmp/diag-fpc-ksa.php ]; then
    sudo -u www-data php /tmp/diag-fpc-ksa.php 2>/dev/null | head -40 || true
  fi
  echo "Redis page_cache DBSIZE:"
  redis-cli -h tyresonline-sa-prod-redis.rlsoda.0001.eun1.cache.amazonaws.com -n 1 DBSIZE 2>/dev/null || echo "redis unavailable"
  echo "Indexers:"
  cd /var/www/magento && sudo -u www-data php bin/magento indexer:status 2>/dev/null | grep -E 'Reindex|Ready|Processing' | head -15 || true
  echo "Cache status:"
  cd /var/www/magento && sudo -u www-data php bin/magento cache:status 2>/dev/null | head -20 || true
  echo "EC2 load:"
  uptime 2>/dev/null || true
  free -h 2>/dev/null | head -2 || true
fi

echo ""
echo "AUDIT DONE."
