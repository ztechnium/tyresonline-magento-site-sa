#!/bin/bash
set -e
M=/var/www/magento
TS=$(date +%Y%m%d-%H%M%S)
BK=/var/backups/tyresonline/phase1-$TS
mkdir -p "$BK"

cp "$M/app/design/frontend/Hditsol/tyresonline/Magento_Theme/templates/html/footer.phtml" "$BK/en-footer.phtml" 2>/dev/null || true
cp "$M/app/design/frontend/Hditsol/tyresonline-ar/Magento_Theme/templates/html/footer.phtml" "$BK/ar-footer.phtml" 2>/dev/null || true
cp "$M/app/design/frontend/Hditsol/tyresonline-ar/web/css/custom-style.css" "$BK/custom-style.css" 2>/dev/null || true

cp /tmp/phase1/en-footer.phtml "$M/app/design/frontend/Hditsol/tyresonline/Magento_Theme/templates/html/footer.phtml"
cp /tmp/phase1/ar-footer.phtml "$M/app/design/frontend/Hditsol/tyresonline-ar/Magento_Theme/templates/html/footer.phtml"
cp /tmp/phase1/custom-style.css "$M/app/design/frontend/Hditsol/tyresonline-ar/web/css/custom-style.css"

mkdir -p "$M/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css"
cp "$M/app/design/frontend/Hditsol/tyresonline-ar/web/css/custom-style.css" \
   "$M/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/custom-style.css"

cd "$M"
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -2
rm -rf var/page_cache/* var/view_preprocessed/* 2>/dev/null || true
echo "Phase1 UI deployed. Backup: $BK"
