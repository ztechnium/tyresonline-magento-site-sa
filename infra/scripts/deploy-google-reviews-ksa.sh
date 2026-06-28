#!/bin/bash
set -euo pipefail
M=/var/www/magento
TS=$(date +%Y%m%d-%H%M%S)
BK=/var/backups/tyresonline/google-reviews-$TS
sudo mkdir -p "$BK"

sudo cp "$M/pub/google-reviews.html" "$BK/" 2>/dev/null || true
sudo cp "$M/app/design/frontend/Hditsol/tyresonline/Magento_Theme/templates/html/footer.phtml" "$BK/en-footer.phtml" 2>/dev/null || true
sudo cp "$M/app/design/frontend/Hditsol/tyresonline-ar/Magento_Theme/templates/html/footer.phtml" "$BK/ar-footer.phtml" 2>/dev/null || true

sudo cp /tmp/pending/google-reviews.html "$M/pub/google-reviews.html"
sudo cp /tmp/pending/en-footer.phtml "$M/app/design/frontend/Hditsol/tyresonline/Magento_Theme/templates/html/footer.phtml"
sudo cp /tmp/pending/ar-footer.phtml "$M/app/design/frontend/Hditsol/tyresonline-ar/Magento_Theme/templates/html/footer.phtml"

sudo chown www-data:www-data "$M/pub/google-reviews.html" 2>/dev/null || true
sudo chmod 644 "$M/pub/google-reviews.html"

cd "$M"
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -2
echo "Google Reviews deployed. Backup: $BK"
