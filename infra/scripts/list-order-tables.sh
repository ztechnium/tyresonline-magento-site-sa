#!/bin/bash
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB=tyresonline_sa
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" -N -e "
SELECT table_name, table_rows
FROM information_schema.tables
WHERE table_schema='$DB'
AND (
  table_name LIKE '%order%'
  OR table_name LIKE '%customer%'
  OR table_name LIKE '%quote%'
  OR table_name LIKE '%invoice%'
  OR table_name LIKE '%shipment%'
  OR table_name LIKE 'tabby%'
  OR table_name LIKE 'hyperpay%'
)
AND table_name NOT LIKE 'catalog%'
AND table_name NOT LIKE 'ecomteck_storelocator%'
AND table_name NOT LIKE 'cms_%'
AND table_name NOT LIKE 'admin_%'
AND table_name NOT LIKE 'authorization_%'
ORDER BY table_rows DESC
LIMIT 60;
"
