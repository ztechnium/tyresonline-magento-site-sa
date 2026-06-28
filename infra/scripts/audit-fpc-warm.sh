#!/bin/bash
CJ=/tmp/audit_cj
rm -f "$CJ"
echo "=== Request 1 (cold) ==="
curl -sS -c "$CJ" -D /tmp/h1.hdr -o /dev/null -w "ttfb=%{time_starttransfer}s total=%{time_total}s\n" https://stg.tyresonline.sa/all-tyres/car-tyres.html
grep -iE 'cache-control|x-magento|age:' /tmp/h1.hdr | tr -d '\r'
echo "=== Request 2 (same session) ==="
curl -sS -b "$CJ" -c "$CJ" -D /tmp/h2.hdr -o /dev/null -w "ttfb=%{time_starttransfer}s total=%{time_total}s\n" https://stg.tyresonline.sa/all-tyres/car-tyres.html
grep -iE 'cache-control|x-magento|age:' /tmp/h2.hdr | tr -d '\r'
echo "=== Request 3 (no cookies) ==="
curl -sS -D /tmp/h3.hdr -o /dev/null -w "ttfb=%{time_starttransfer}s total=%{time_total}s\n" https://stg.tyresonline.sa/all-tyres/car-tyres.html
grep -iE 'cache-control|x-magento|age:' /tmp/h3.hdr | tr -d '\r'
echo "=== Redis FPC db4 ==="
redis-cli -h tyresonline-sa-prod-redis.rlsoda.0001.eun1.cache.amazonaws.com -n 4 DBSIZE
echo "=== deploy mode ==="
cd /var/www/magento && sudo -u www-data php bin/magento deploy:mode:show 2>/dev/null | head -5
echo "=== PLP resource counts ==="
PLP=$(curl -sS https://stg.tyresonline.sa/all-tyres/car-tyres.html)
echo "size=$(echo -n "$PLP" | wc -c) scripts=$(echo "$PLP" | grep -oE '<script[^>]+src=' | wc -l) imgs=$(echo "$PLP" | grep -o '<img' | wc -l) gtm=$(echo "$PLP" | grep -c googletagmanager) fw=$(echo "$PLP" | grep -c fw-cdn)"
