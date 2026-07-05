#!/usr/bin/env bash
set -euo pipefail

ROOT="/var/www/magento"
THEME="$ROOT/app/design/frontend/Hditsol"
TMP="${TMPDIR:-/tmp}/homepage-whitespace"

mkdir -p "$TMP"

cp "$TMP/cms_index_index.xml" "$THEME/tyresonline/Magento_Cms/layout/cms_index_index.xml"
cp "$TMP/customheader.phtml" "$THEME/tyresonline/Magento_Theme/templates/html/customheader.phtml"
cp "$TMP/Page.php" "$ROOT/app/code/MGS/Fbuilder/Block/Cms/Page.php"
cp "$TMP/home.css" "$THEME/tyresonline/web/css/home.css"
cp "$TMP/home-ar.css" "$THEME/tyresonline-ar/web/css/home.css"
cp "$TMP/custom-style.css" "$THEME/tyresonline/web/css/custom-style.css"

mkdir -p "$ROOT/pub/static/frontend/Hditsol/tyresonline/en_US/css"
mkdir -p "$ROOT/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css"
cp "$THEME/tyresonline/web/css/home.css" "$ROOT/pub/static/frontend/Hditsol/tyresonline/en_US/css/home.css"
cp "$THEME/tyresonline/web/css/custom-style.css" "$ROOT/pub/static/frontend/Hditsol/tyresonline/en_US/css/custom-style.css"
cp "$THEME/tyresonline-ar/web/css/home.css" "$ROOT/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/home.css"

cd "$ROOT"
rm -rf var/view_preprocessed/* var/page_cache/* pub/static/_cache/merged/* 2>/dev/null || true
sudo rm -rf /var/cache/apache2/mod_cache_disk/* 2>/dev/null || true
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -3

echo "Homepage whitespace + breadcrumb fix deployed."
