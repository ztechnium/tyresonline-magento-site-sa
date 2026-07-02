#!/bin/bash
set -euo pipefail

cd /var/www/magento

for store in en_US ar_SA; do
  for theme in tyresonline tyresonline-ar; do
    for base in \
      "/var/www/magento/pub/static/frontend/Hditsol/${theme}/${store}/js/custom.js" \
      "/var/www/magento/pub/static/frontend/Hditsol/${theme}/${store}/Magento_Theme/js/custom.js"; do
      if [ -f "$base" ]; then
        min="${base%.js}.min.js"
        cp -f "$base" "$min"
      fi
    done
  done
done

sudo -u www-data php bin/magento cache:flush
echo "OK"

