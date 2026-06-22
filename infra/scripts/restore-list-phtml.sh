#!/bin/bash
set -euo pipefail
LIST=/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
if [ -f "${LIST}.bak-log" ]; then
  cp "${LIST}.bak-log" "$LIST"
  echo "Restored list.phtml from bak-log"
elif ls "${LIST}.bak-"* 1>/dev/null 2>&1; then
  cp "$(ls -t ${LIST}.bak-* | head -1)" "$LIST"
  echo "Restored list.phtml from timestamped backup"
fi
chown www-data:www-data "$LIST"
cd /var/www/magento
sudo -u www-data php bin/magento cache:enable full_page
sudo -u www-data php bin/magento cache:flush
rm -rf var/view_preprocessed/* var/page_cache/*
echo "Restored and cache flushed"
