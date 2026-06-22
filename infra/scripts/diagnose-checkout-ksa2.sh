#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

mysql $DB -e "
SELECT COUNT(*) total FROM checkout_city_area;
SELECT city, COUNT(*) c FROM checkout_city_area GROUP BY city ORDER BY c DESC LIMIT 20;
SELECT * FROM checkout_city_area LIMIT 10;
"

echo '=== server tyrefinder helper key ==='
grep 'WHEEL_SEARCH_APIKEY' /var/www/magento/app/code/Hdweb/Tyrefinder/Helper/Data.php /var/www/magento/app/code/Hdweb/Vehicles/Helper/Data.php

echo '=== test wheel-size API medm vs gcc ==='
KEY=e9f1c4181623dc389b0fafbde0928c0b
for region in medm gcc ksa sa; do
  code=$(curl -s -o /tmp/ws.json -w '%{http_code}' "https://api.wheel-size.com/v2/makes/?user_key=${KEY}&region=${region}")
  cnt=$(python3 -c "import json; d=json.load(open('/tmp/ws.json')); print(len(d.get('data',[])))" 2>/dev/null || echo err)
  echo "region=$region http=$code makes=$cnt"
done

echo '=== phone template on server ==='
cat /var/www/magento/app/code/Hdweb/Core/view/base/web/template/ui/form/element/phone-overwrite.html 2>/dev/null
