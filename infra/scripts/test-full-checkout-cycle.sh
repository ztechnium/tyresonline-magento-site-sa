#!/bin/bash
# Full checkout cycle test (HTTP session simulation)
set -euo pipefail
BASE='https://stg.tyresonline.sa/en'
CJ=/tmp/fullcycle.txt
CURL=/usr/bin/curl
rm -f "$CJ"

step() { echo ""; echo "=== $1 ==="; }

get_fk() {
  grep -oP 'name="form_key" type="hidden" value="\K[^"]+' "$1" | head -1
}

step "1. Start session + form_key (non-cached cart page)"
$CURL -sS -c "$CJ" -b "$CJ" "$BASE/" -o /tmp/fc_home.html -w 'home HTTP %{http_code}\n'
$CURL -sS -c "$CJ" -b "$CJ" "$BASE/checkout/cart/?nocache=$(date +%s)" -o /tmp/fc_cartfk.html -w 'cart HTTP %{http_code}\n'
FK=$(get_fk /tmp/fc_cartfk.html)
if [ -z "$FK" ]; then FK=$(get_fk /tmp/fc_home.html); fi
echo "form_key=${FK:0:16}..."
echo "session cookie:"; grep PHPSESSID "$CJ" || true

ROW=$(sudo -u www-data php /tmp/get-test-product.php)
PID="${ROW%%|*}"
PPATH="${ROW#*|}"
PDP="$BASE/$PPATH"
echo "PDP=$PDP PID=$PID"
$CURL -sS -c "$CJ" -b "$CJ" "$PDP" -o /tmp/fc_pdp.html -w 'pdp HTTP %{http_code}\n'
FK_PDP=$(get_fk /tmp/fc_pdp.html)
# Keep homepage form_key — FPC-cached PDP often has a stale key from another session
echo "pdp form_key=${FK_PDP:0:16}... (ignored, using session key)"

step "2. Add product to cart"
$CURL -sS -c "$CJ" -b "$CJ" -X POST "$BASE/checkout/cart/add/" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H "Referer: $PDP" \
  --data-urlencode "product=$PID" --data-urlencode "qty=4" --data-urlencode "form_key=$FK" \
  -D /tmp/fc_add.hdr -o /tmp/fc_add.body -w 'HTTP %{http_code}\n'
grep -i '^location:\|mage-messages' /tmp/fc_add.hdr | tr -d '\r' | head -3

step "3. Verify cart"
$CURL -sS -b "$CJ" "$BASE/checkout/cart/" -o /tmp/fc_cart.html -w 'HTTP %{http_code}\n'
if grep -qi 'cart-empty\|You have no items' /tmp/fc_cart.html; then
  echo "FAIL: Cart is empty"
  grep -oP 'message-error[^>]*>[^<]+' /tmp/fc_cart.html | head -3 || true
  exit 1
fi
echo "PASS: Cart has items"
grep -oP 'product-item-name[^>]*>\s*\K[^<]+' /tmp/fc_cart.html | head -1 || true
FK=$(get_fk /tmp/fc_cart.html)
echo "cart form_key=${FK:0:16}..."

step "4. Select fitting location (mail order / pickup store)"
PICKUP_DATE=$(date -d '+3 days' +%Y-%m-%d 2>/dev/null || date -v+3d +%Y-%m-%d 2>/dev/null || echo 2026-06-30)
$CURL -sS -c "$CJ" -b "$CJ" -X POST "$BASE/installer/ajax/savecartinstaller/" \
  -H 'Content-Type: application/json' \
  -d "{\"pickup_store\":\"1\",\"pickup_date\":\"$PICKUP_DATE\",\"pickup_time\":\"09:00 - 11:00\"}" \
  -o /tmp/fc_installer.json -w 'installer HTTP %{http_code}\n'
head -c 400 /tmp/fc_installer.json; echo

# Also try setfitment for mail-order
$CURL -sS -c "$CJ" -b "$CJ" -X POST "$BASE/installer/ajax/setfitment" \
  -H 'Content-Type: application/json' \
  -d '{"fitment_installer":"0","installer_id":"26"}' \
  -o /tmp/fc_fit.json -w 'setfitment HTTP %{http_code}\n' 2>/dev/null || true
head -c 200 /tmp/fc_fit.json 2>/dev/null; echo

step "5. Proceed to checkout"
$CURL -sS -L -c "$CJ" -b "$CJ" "$BASE/checkout/" -o /tmp/fc_checkout.html -w 'HTTP %{http_code} size=%{size_download}\n'
grep -oE '<title>[^<]+</title>' /tmp/fc_checkout.html || true
echo -n "checkoutConfig: "; grep -c 'window.checkoutConfig' /tmp/fc_checkout.html || echo 0
echo -n "opc-wrapper: "; grep -c 'opc-wrapper\|checkout-container' /tmp/fc_checkout.html || echo 0
echo -n "payment methods: "; grep -ciE 'hyperpay|tabby|cashondelivery|payment-method' /tmp/fc_checkout.html || echo 0
if grep -qi 'cart-empty\|no items\|shopping cart is empty' /tmp/fc_checkout.html; then
  echo "FAIL: Redirected to empty cart"
  exit 1
fi
if grep -qi 'fatal\|exception\|CredisException' /tmp/fc_checkout.html; then
  echo "FAIL: Error in checkout HTML"
  grep -oiE 'fatal error[^<]+|exception[^<]{0,80}' /tmp/fc_checkout.html | head -3
  exit 1
fi

step "6. Checkout REST — shipping + payment info"
FK=$(get_fk /tmp/fc_checkout.html)
CARTID=$(grep -oP '"quoteId"\s*:\s*\K[0-9]+' /tmp/fc_checkout.html | head -1 || true)
echo "quoteId=$CARTID"

# Guest shipping information
$CURL -sS -b "$CJ" -X POST "$BASE/rest/en/V1/guest-carts/estimate-shipping-methods" \
  -H 'Content-Type: application/json' \
  -d '{"address":{"country_id":"SA","postcode":"12345","region_id":null,"region":"Riyadh","city":"Riyadh"}}' \
  -o /tmp/fc_ship.json -w 'estimate-shipping HTTP %{http_code}\n' 2>/dev/null || echo "REST guest cart may need mask id"

step "7. Summary"
if grep -q 'window.checkoutConfig' /tmp/fc_checkout.html && ! grep -qi 'cart-empty' /tmp/fc_checkout.html; then
  echo "RESULT: CHECKOUT PAGE LOADED — manual payment step requires browser/KO UI"
  exit 0
else
  echo "RESULT: CHECKOUT BLOCKED — see above"
  exit 1
fi
