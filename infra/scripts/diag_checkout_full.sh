#!/bin/bash
set -e

echo "=== 1. Full checkout flow test ==="
CJ=/tmp/co_diag_$$
PRODUCT=4898
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/h.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/h.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /dev/null
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/installer/ajax/savecartinstaller/' \
  -d "pickup_store=1&pickup_date=2026-06-25&pickup_time=09:00 - 11:00&form_key=$FK" -o /dev/null
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' \
  -o /tmp/co.html -w 'status:%{http_code} url:%{url_effective} size:%{size_download}\n'
grep -oE '<title>[^<]+</title>' /tmp/co.html
echo "checkoutConfig=$(grep -c checkoutConfig /tmp/co.html || true)"
echo "checkout_div=$(grep -c 'id=\"checkout\"' /tmp/co.html || true)"
echo "opc=$(grep -c 'opc-wrapper\|checkout-container' /tmp/co.html || true)"
grep -o 'body[^>]*class="[^"]*"' /tmp/co.html | head -1
echo "main_snippet:"
grep -oP '(?<=column main">).{0,800}' /tmp/co.html | head -c 600

echo
echo "=== 2. Static files ==="
BASE=/var/www/magento/pub/static/frontend/Hditsol/tyresonline/en_US
for f in mage/requirejs/mixins.js mage/requirejs/mixins.min.js Magento_Checkout/js/view/checkout.js requirejs/require.js; do
  [ -f "$BASE/$f" ] && echo "OK $f" || echo "MISSING $f"
done
du -sh /var/www/magento/pub/static/frontend/Hditsol/tyresonline/en_US 2>/dev/null

echo
echo "=== 3. Data.php redis fix ==="
grep -A2 envConfig /var/www/magento/app/code/Hdweb/Core/Helper/Data.php | head -4

echo
echo "=== 4. Recent exceptions ==="
grep '^\[2026' /var/www/magento/var/log/exception.log | tail -5 | cut -c1-300

echo
echo "=== 5. checkout.root in merged layout ==="
find /var/www/magento/var/view_preprocessed -name '*checkout_index*' 2>/dev/null | head -3
grep -l 'checkout.root' /var/www/magento/vendor/magento/module-checkout/view/frontend/layout/checkout_index_index.xml 2>/dev/null && echo "core layout has checkout.root"
