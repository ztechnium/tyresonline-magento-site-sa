#!/bin/bash
BASE=/var/www/magento/pub/static/frontend/Hditsol/tyresonline/en_US
echo "=== key checkout static files ==="
for f in \
  mage/requirejs/mixins.js \
  mage/requirejs/mixins.min.js \
  Magento_Checkout/js/view/checkout.js \
  Magento_Checkout/js/view/checkout.min.js \
  Magento_Ui/js/core/app.js \
  Magento_Ui/js/core/app.min.js \
  requirejs/require.js \
  requirejs/require.min.js
do
  if [ -f "$BASE/$f" ]; then
    echo "OK $f $(stat -c%s "$BASE/$f")"
  else
    echo "MISSING $f"
  fi
done

echo "=== static dir size ==="
du -sh /var/www/magento/pub/static/frontend/Hditsol/tyresonline/en_US 2>/dev/null

echo "=== checkout with pickup via curl ==="
CJ=/tmp/copick
PRODUCT=4898
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/h.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/h.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' -d "product=$PRODUCT" -d qty=4 -d "form_key=$FK" -o /dev/null
# set pickup on quote via store pickup endpoint if exists
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/storepickup/index/save/' \
  -d "store_id=1" -d "pickup_date=2026-06-20" -d "pickup_time=09:00 - 11:00" -d "form_key=$FK" -o /tmp/pickup_resp.txt -w 'pickup:%{http_code}\n' 2>/dev/null || echo pickup_endpoint_failed
head -c 300 /tmp/pickup_resp.txt 2>/dev/null; echo
curl -sS -L -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/' -o /tmp/co_pick.html -w 'checkout:%{http_code} url:%{url_effective} size:%{size_download}\n'
grep -oE '<title>[^<]+</title>' /tmp/co_pick.html
grep -c 'id=\"checkout\"' /tmp/co_pick.html
grep -c 'checkout-index-index' /tmp/co_pick.html
grep -c 'checkout-cart-index' /tmp/co_pick.html
