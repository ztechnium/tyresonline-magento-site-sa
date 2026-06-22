#!/bin/bash
mysql --default-character-set=utf8mb4 -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa 2>/dev/null <<'SQL'
SELECT stores_id, store_id, name, opening_hours_text2
FROM ecomteck_storelocator_stores
WHERE stores_id IN (1,2);
SQL
