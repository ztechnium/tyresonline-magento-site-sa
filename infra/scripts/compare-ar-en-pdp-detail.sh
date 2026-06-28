#!/usr/bin/env bash
curl -s 'https://stg.tyresonline.sa/en/alpha-185701488aggressor-zp01-2025.html' -o /tmp/pdp_en.html
curl -s 'https://stg.tyresonline.sa/ar/alpha-185701488aggressor-zp01-2025.html' -o /tmp/pdp_ar.html
echo "x20 en=$(grep -o '&#x20;' /tmp/pdp_en.html | wc -l) ar=$(grep -o '&#x20;' /tmp/pdp_ar.html | wc -l)"
echo "=== html tags ==="
grep -o '<html[^>]*>' /tmp/pdp_en.html
grep -o '<html[^>]*>' /tmp/pdp_ar.html
echo "=== merged css sizes ==="
for f in 8cdb357419380e62df23a0a6e075f39a 59b536e00a0bb68f1b54cc8da95fd173; do
  sz=$(stat -c%s /var/www/magento/pub/static/_cache/merged/${f}.min.css 2>/dev/null || echo missing)
  echo "$f size=$sz"
done
echo "=== product media gallery block ==="
grep -c 'fotorama' /tmp/pdp_en.html /tmp/pdp_ar.html
grep -c 'gallery-placeholder' /tmp/pdp_en.html /tmp/pdp_ar.html
echo "=== stylesheet count ==="
grep -c '\.css' /tmp/pdp_en.html /tmp/pdp_ar.html
