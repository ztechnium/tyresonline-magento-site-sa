#!/usr/bin/env bash
curl -s 'https://stg.tyresonline.sa/en/alpha-185701488aggressor-zp01-2025.html' -o /tmp/pdp_en.html
curl -s 'https://stg.tyresonline.sa/ar/alpha-185701488aggressor-zp01-2025.html' -o /tmp/pdp_ar.html
echo '=== html open tag ==='
grep -o '<html[^>]*>' /tmp/pdp_en.html | head -1
grep -o '<html[^>]*>' /tmp/pdp_ar.html | head -1
echo '=== body class ==='
grep -o '<body[^>]*>' /tmp/pdp_en.html | head -1
grep -o '<body[^>]*>' /tmp/pdp_ar.html | head -1
echo '=== rtl css ==='
grep -i rtl /tmp/pdp_ar.html | head -5
grep -i rtl /tmp/pdp_en.html | head -3
echo '=== unclosed/broken snippets ar ==='
grep -c '<div' /tmp/pdp_ar.html; grep -c '</div>' /tmp/pdp_ar.html
grep -c '<div' /tmp/pdp_en.html; grep -c '</div>' /tmp/pdp_en.html
echo '=== visible broken ==='
grep -iE 'undefined|null|NaN|Fatal|Exception' /tmp/pdp_ar.html | head -5
