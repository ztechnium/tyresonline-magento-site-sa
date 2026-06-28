#!/usr/bin/env bash
set -euo pipefail
MAGENTO=/var/www/magento
AR_CSS="$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/css/custom-style.css"
BLOCK='.products.product-add-cart {
  order: 3;
}

.products.product-specs {
  order: 4;
  width: 100%;
}

.products.product-people-asked {
  order: 5;
  width: 100%;
}

.products.product-why-choose {
  order: 6;
  width: 100%;
}

.products.related-product-list {
  order: 7;'

if grep -q 'products.product-add-cart' "$AR_CSS"; then
  echo "Order rules already present in AR custom-style.css"
else
  sed -i 's/\.products\.related-product-list {\n  order: 3;/PLACEHOLDER/' "$AR_CSS" || true
  python3 - <<'PY'
from pathlib import Path
path = Path('/var/www/magento/app/design/frontend/Hditsol/tyresonline-ar/web/css/custom-style.css')
text = path.read_text(encoding='utf-8')
block = '''.products.product-add-cart {
  order: 3;
}

.products.product-specs {
  order: 4;
  width: 100%;
}

.products.product-people-asked {
  order: 5;
  width: 100%;
}

.products.product-why-choose {
  order: 6;
  width: 100%;
}

'''
old = '.products.related-product-list {\n  order: 3;'
new = block + '.products.related-product-list {\n  order: 7;'
if old not in text:
    raise SystemExit('Expected related-product-list order:3 block not found')
path.write_text(text.replace(old, new, 1), encoding='utf-8')
print('Patched AR custom-style.css')
PY
fi

rm -f "$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/ar_SA/css/custom-style.min.css"
rm -rf "$MAGENTO/pub/static/_cache/merged/"*

cd "$MAGENTO"
sudo -u www-data php bin/magento setup:static-content:deploy ar_SA -f --area frontend --theme Hditsol/tyresonline-ar 2>&1 | tail -4
sudo -u www-data php bin/magento cache:flush | tail -2

echo "Done. Verify grep:"
grep -n 'products.product' "$AR_CSS" | head -10
