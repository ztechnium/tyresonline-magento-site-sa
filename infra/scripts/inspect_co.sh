#!/bin/bash
grep -o 'body id="[^"]*"' /tmp/co.html | head -1
grep -o 'body class="[^"]*"' /tmp/co.html | head -1
echo checkout-index-index=$(grep -c checkout-index-index /tmp/co.html)
echo checkout-cart-index=$(grep -c checkout-cart-index /tmp/co.html)
echo exception_tail:
tail -3 /var/www/magento/var/log/exception.log 2>/dev/null || true
