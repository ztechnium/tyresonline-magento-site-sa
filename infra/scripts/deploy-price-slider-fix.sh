#!/bin/bash
set -e
M=/var/www/magento
SRC=/tmp/price-fix

cp -f "$SRC/priceslider.css" "$M/app/design/frontend/Hditsol/tyresonline/Smile_ElasticsuiteCatalog/web/css/priceslider.css"
cp -f "$SRC/slider.phtml" "$M/app/design/frontend/Hditsol/tyresonline/Smile_ElasticsuiteCatalog/templates/layer/filter/slider.phtml"
cp -f "$SRC/product-list.css" "$M/app/design/frontend/Hditsol/tyresonline/web/css/product-list.css"
cp -f "$SRC/range-slider-widget.js" "$M/app/code/MGS/Ajaxlayernavigation/view/frontend/web/js/range-slider-widget.js"

for locale in en_US ar_SA; do
  theme=tyresonline
  [ "$locale" = "ar_SA" ] && theme=tyresonline-ar
  mkdir -p "$M/pub/static/frontend/Hditsol/$theme/$locale/Smile_ElasticsuiteCatalog/css"
  mkdir -p "$M/pub/static/frontend/Hditsol/$theme/$locale/css"
  cp -f "$M/app/design/frontend/Hditsol/tyresonline/Smile_ElasticsuiteCatalog/web/css/priceslider.css" \
    "$M/pub/static/frontend/Hditsol/$theme/$locale/Smile_ElasticsuiteCatalog/css/priceslider.css"
  cp -f "$M/app/design/frontend/Hditsol/tyresonline/web/css/product-list.css" \
    "$M/pub/static/frontend/Hditsol/$theme/$locale/css/product-list.css"
done

jsdest="$M/pub/static/frontend/Hditsol/tyresonline/en_US/MGS_Ajaxlayernavigation/js/range-slider-widget.js"
mkdir -p "$(dirname "$jsdest")"
cp -f "$M/app/code/MGS/Ajaxlayernavigation/view/frontend/web/js/range-slider-widget.js" "$jsdest"
mkdir -p "$M/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/MGS_Ajaxlayernavigation/js"
cp -f "$jsdest" "$M/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/MGS_Ajaxlayernavigation/js/range-slider-widget.js"

rm -rf "$M/pub/static/_cache/merged" "$M/pub/static/_cache" "$M/var/page_cache"/* "$M/var/view_preprocessed"/* 2>/dev/null || true

cd "$M"
php bin/magento cache:flush 2>&1 | tail -3

echo DONE
grep -c bubble-label "$M/app/design/frontend/Hditsol/tyresonline/Smile_ElasticsuiteCatalog/templates/layer/filter/slider.phtml" || true
grep -c slider-bar-wrap "$M/app/design/frontend/Hditsol/tyresonline/Smile_ElasticsuiteCatalog/templates/layer/filter/slider.phtml"
