#!/bin/bash
CJ=/tmp/cartj5
PRODUCT=4898
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/' -o /tmp/home.html
FK=$(grep -oP 'name="form_key" type="hidden" value="\K[^"]+' /tmp/home.html | head -1)
curl -sS -c "$CJ" -b "$CJ" -X POST 'https://stg.tyresonline.sa/en/checkout/cart/add/' \
  -d "product=$PRODUCT" -d 'qty=4' -d "form_key=$FK" -o /tmp/add.html -w 'add:%{http_code}\n'
curl -sS -c "$CJ" -b "$CJ" 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/cart2.html
grep -c 'cart item' /tmp/cart2.html
grep -o 'subtotal[^<]*' /tmp/cart2.html | head -3
grep -o 'Proceed to Checkout' /tmp/cart2.html | head -1
grep -o 'checkout/cart' /tmp/add.html | head -3
