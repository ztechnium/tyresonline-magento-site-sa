#!/bin/bash
# Restore built-in FPC: Mgt_Varnish LayoutPlugin blocks Kernel::process() by forcing no-cache.
set -euo pipefail
DI=/var/www/magento/app/code/Mgt/Varnish/etc/di.xml
cd /var/www/magento

echo "=== Before ==="
grep -E 'layout-model-caching|mgt-varnish-layout' "$DI"

sudo cp "$DI" "${DI}.bak-fpc-$(date +%Y%m%d%H%M%S)"

sudo python3 - <<'PY'
from pathlib import Path
p = Path("/var/www/magento/app/code/Mgt/Varnish/etc/di.xml")
text = p.read_text()
text = text.replace(
    '<plugin name="mgt-varnish-layout-plugin" type="Mgt\\Varnish\\Model\\Plugin\\LayoutPlugin"/>',
    '<plugin name="mgt-varnish-layout-plugin" type="Mgt\\Varnish\\Model\\Plugin\\LayoutPlugin" disabled="true"/>',
)
text = text.replace(
    '<plugin name="layout-model-caching-unique-name" type="Magento\\PageCache\\Model\\Layout\\LayoutPlugin" disabled="true"/>',
    '<plugin name="layout-model-caching-unique-name" type="Magento\\PageCache\\Model\\Layout\\LayoutPlugin"/>',
)
p.write_text(text)
PY

echo "=== After ==="
grep -E 'layout-model-caching|mgt-varnish-layout' "$DI"

echo "=== Disable Mgt debug mode ==="
sudo -u www-data php bin/magento config:set mgt_varnish/module/debug_mode 0

echo "=== Flush caches ==="
sudo -u www-data php bin/magento cache:flush

echo "=== Warm homepage ==="
curl -sk -H 'Host: stg.tyresonline.sa' -o /dev/null -w 'home1: %{time_starttransfer}s\n' 'https://127.0.0.1/'
curl -sk -H 'Host: stg.tyresonline.sa' -D - -o /dev/null 'https://127.0.0.1/' | grep -iE '^cache-control:|^x-magento|^x-cache|^set-cookie:|^pragma:'
echo "redis db4: $(redis-cli -h tyresonline-sa-prod-redis.rlsoda.0001.eun1.cache.amazonaws.com -n 4 DBSIZE)"

echo "=== Warm car-tyres ==="
curl -sk -H 'Host: stg.tyresonline.sa' -o /dev/null -w 'ct1: %{time_starttransfer}s\n' 'https://127.0.0.1/car-tyres'
curl -sk -H 'Host: stg.tyresonline.sa' -D - -o /dev/null 'https://127.0.0.1/car-tyres' | grep -iE '^cache-control:|^x-magento|^x-cache|^set-cookie:|^pragma:'
curl -sk -H 'Host: stg.tyresonline.sa' -o /dev/null -w 'ct2: %{time_starttransfer}s\n' 'https://127.0.0.1/car-tyres'
echo "redis db4: $(redis-cli -h tyresonline-sa-prod-redis.rlsoda.0001.eun1.cache.amazonaws.com -n 4 DBSIZE)"
