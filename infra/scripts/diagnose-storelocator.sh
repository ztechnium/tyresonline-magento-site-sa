#!/bin/bash
echo '=== Ecomteck storelocator grep ==='
grep -rn 'dataLocation\|storelocator' /var/www/magento/app/code/Ecomteck --include='*.js' --include='*.phtml' --include='*.xml' 2>/dev/null | head -40

echo '=== cart page storelocator config ==='
curl -s 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/cart.html
grep -i 'dataLocation\|storelocator\|initMap' /tmp/cart.html | head -20

echo '=== DB stores ==='
mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -e "
SHOW TABLES LIKE '%storelocator%';
SHOW TABLES LIKE '%store_pickup%';
SHOW TABLES LIKE '%ecomteck%';
SELECT COUNT(*) AS stores FROM ecomteck_storelocator_stores;
SELECT store_id, name, status, latitude, longitude FROM ecomteck_storelocator_stores LIMIT 10;
"

echo '=== config ==='
cd /var/www/magento
sudo -u www-data php bin/magento config:show | grep -iE 'storelocator|store_pickup|ecomteck|google.*api|maps' 2>/dev/null | head -25
