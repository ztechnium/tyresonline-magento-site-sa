#!/bin/bash
URLS=(
  'https://stg.tyresonline.sa/static/version1781783979/_cache/merged/59b536e00a0bb68f1b54cc8da95fd173.min.css'
  'https://stg.tyresonline.sa/static/version1781783979/_cache/merged/081999111bf9a55bf594fe64852a9810.min.css'
  'https://stg.tyresonline.sa/static/version1781783979/frontend/Hditsol/tyresonline-ar/ar_SA/js/custom.min.js'
  'https://stg.tyresonline.sa/static/version1781783979/frontend/Hditsol/tyresonline/en_US/js/custom.min.js'
)
for u in "${URLS[@]}"; do
  code=$(curl -sI -o /dev/null -w '%{http_code}' "$u")
  echo "$code $u"
done
echo '=== themes ==='
ls -la /var/www/magento/app/design/frontend/Hditsol/
echo '=== static ==='
ls /var/www/magento/pub/static/frontend/Hditsol/
echo '=== tyresonline-ar ==='
ls /var/www/magento/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/ 2>/dev/null | head -10 || echo MISSING
