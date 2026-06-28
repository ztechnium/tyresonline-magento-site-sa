#!/usr/bin/env bash
set -euo pipefail
MAGENTO=/var/www/magento
URL_AR='https://stg.tyresonline.sa/ar/alpha-185701488aggressor-zp01-2025.html'
URL_EN='https://stg.tyresonline.sa/en/alpha-185701488aggressor-zp01-2025.html'

curl -s "$URL_AR" -o /tmp/pdp_ar.html
curl -s "$URL_EN" -o /tmp/pdp_en.html

echo "=== HTTP status ==="
curl -sI "$URL_AR" | head -1
curl -sI "$URL_EN" | head -1

echo "=== product-details CSS refs ==="
grep -oE 'product-details[^" ]+' /tmp/pdp_ar.html | head -3 || true
grep -oE 'product-details[^" ]+' /tmp/pdp_en.html | head -3 || true

echo "=== Static CSS sizes on disk ==="
for f in product-details theme styles; do
  en="$MAGENTO/pub/static/frontend/Hditsol/tyresonline/en_US/css/${f}.min.css"
  ar="$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/${f}.min.css"
  echo -n "EN $f: "
  stat -c%s "$en" 2>/dev/null || echo missing
  echo -n "AR $f: "
  stat -c%s "$ar" 2>/dev/null || echo missing
done

echo "=== CSS HTTP from page ==="
css_ar=$(grep -oE 'https?://[^"]+product-details[^"]+\.css' /tmp/pdp_ar.html | head -1)
css_en=$(grep -oE 'https?://[^"]+product-details[^"]+\.css' /tmp/pdp_en.html | head -1)
if [ -n "$css_ar" ]; then
  echo "AR: $css_ar"
  curl -sI "$css_ar" | head -2
fi
if [ -n "$css_en" ]; then
  echo "EN: $css_en"
  curl -sI "$css_en" | head -2
fi

echo "=== html dir/lang ==="
grep -oE '<html[^>]+>' /tmp/pdp_ar.html | head -1
grep -oE '<html[^>]+>' /tmp/pdp_en.html | head -1
