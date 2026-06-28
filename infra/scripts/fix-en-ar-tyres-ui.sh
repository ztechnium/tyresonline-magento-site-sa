#!/bin/bash
set -e
MAGENTO=/var/www/magento
REPO=/tmp/tyresonline-fixes

mkdir -p "$REPO"

# Files uploaded separately via scp to /tmp/tyresonline-fixes/
cp -f /tmp/tyresonline-fixes/catalog_category_view.xml "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/layout/"
cp -f /tmp/tyresonline-fixes/priceslider.css "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Smile_ElasticsuiteCatalog/web/css/"
cp -f /tmp/tyresonline-fixes/footer-en.phtml "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Theme/templates/html/footer.phtml"
cp -f /tmp/tyresonline-fixes/footer-ar.phtml "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/Magento_Theme/templates/html/footer.phtml"
cp -f /tmp/tyresonline-fixes/custom-style-ar.css "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/css/custom-style.css"

# Sync CSS to deployed static (avoid full static-content deploy)
for pair in "tyresonline/en_US" "tyresonline-ar/ar_SA"; do
  IFS=/ read -r theme locale <<< "$pair"
  base="$MAGENTO/pub/static/frontend/Hditsol/$theme/$locale"
  mkdir -p "$base/css" "$base/Smile_ElasticsuiteCatalog/css"
  cp -f "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Smile_ElasticsuiteCatalog/web/css/priceslider.css" "$base/Smile_ElasticsuiteCatalog/css/priceslider.css" 2>/dev/null || true
  if [ "$theme" = "tyresonline-ar" ]; then
    cp -f "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/css/custom-style.css" "$base/css/custom-style.css"
  fi
done

cd "$MAGENTO"
php bin/magento cache:flush 2>&1 | tail -3
sudo rm -rf var/page_cache/* var/view_preprocessed/* 2>/dev/null || true

echo "=== VERIFY ==="
curl -sS -m 90 -L -o /tmp/en-fix.html https://stg.tyresonline.sa/en/all-tyres/car-tyres.html
curl -sS -m 90 -L -o /tmp/ar-fix.html https://stg.tyresonline.sa/all-tyres/car-tyres.html
echo -n "EN priceslider: "; grep -c priceslider /tmp/en-fix.html || echo 0
echo -n "AR priceslider: "; grep -c priceslider /tmp/ar-fix.html || echo 0
echo -n "EN footer-services: "; grep -c footer-services /tmp/en-fix.html || echo 0
echo -n "AR footer-services: "; grep -c footer-services /tmp/ar-fix.html || echo 0
echo -n "AR https chat: "; grep -c 'https://uae.fw-cdn.com' /tmp/ar-fix.html || echo 0
