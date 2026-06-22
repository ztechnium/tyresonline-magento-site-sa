#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== tyrefinder / vehicle / phone config ==='
mysql $DB -e "
SELECT path, value FROM core_config_data
WHERE path LIKE '%tyrefinder%' OR path LIKE '%vehicle%' OR path LIKE '%wheel%'
   OR path LIKE '%installer%' OR path LIKE '%phone%' OR path LIKE '%mobile%'
   OR path LIKE '%country%' OR path LIKE '%region%'
   OR value LIKE '%e9f1c418%' OR value LIKE '%token%'
ORDER BY path
LIMIT 80;
"

echo '=== grep token in code config ==='
grep -r 'e9f1c4181623dc389b0fafbde0928c0b' /var/www/magento/app/code/Hdweb /var/www/magento/app/etc 2>/dev/null | head -10

echo '=== checkout page phone/city snippets ==='
curl -s 'https://stg.tyresonline.sa/en/checkout/' -o /tmp/checkout.html 2>/dev/null || curl -s 'https://stg.tyresonline.sa/en/checkout/cart/' -o /tmp/checkout.html
grep -iE 'phone|mobile|971|966|country|city|area|getmodel|getvehicle|tyrefinder' /tmp/checkout.html | head -25
