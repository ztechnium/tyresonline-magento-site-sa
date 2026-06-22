#!/bin/bash
set -e
DB=tyresonline_sa
HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
USER=magento
PASS=uxaYMIQRwEU0AFl1Ck69LJnH
MYSQL=(mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB")

echo "=== BEFORE ==="
"${MYSQL[@]}" -N -e "
SELECT 'products', COUNT(*) FROM catalog_product_entity UNION ALL
SELECT 'categories', COUNT(*) FROM catalog_category_entity UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'cms_blocks', COUNT(*) FROM cms_block;
"

# Collect category data tables (entity + EAV), skip changelog/index tmp tables
"${MYSQL[@]}" -N -e "
SELECT table_name FROM information_schema.tables
WHERE table_schema = '$DB'
AND table_name LIKE 'catalog_category%'
AND table_name NOT LIKE '%_cl'
AND table_name NOT LIKE '%_index%'
AND table_name NOT LIKE '%_tmp'
ORDER BY table_name;
" | while read -r t; do
  if [[ "$t" == "catalog_category_entity" ]]; then
    printf 'DELETE FROM `%s` WHERE entity_id NOT IN (1, 2);\n' "$t"
  elif [[ "$t" == catalog_category_entity_* ]]; then
    printf 'DELETE FROM `%s` WHERE entity_id NOT IN (1, 2);\n' "$t"
  else
    printf 'TRUNCATE TABLE `%s`;\n' "$t"
  fi
done > /tmp/delete-categories.sql

echo "DELETE FROM url_rewrite WHERE entity_type = 'category' AND entity_id NOT IN (1, 2);" >> /tmp/delete-categories.sql
echo "UPDATE catalog_category_entity SET children_count = 0 WHERE entity_id = 2;" >> /tmp/delete-categories.sql
echo "UPDATE catalog_category_entity SET children_count = 1 WHERE entity_id = 1;" >> /tmp/delete-categories.sql

{
  echo 'SET FOREIGN_KEY_CHECKS=0;'
  cat /tmp/delete-categories.sql
  echo 'SET FOREIGN_KEY_CHECKS=1;'
} > /tmp/delete-categories-run.sql

echo "Generated $(grep -cE 'DELETE|TRUNCATE|UPDATE' /tmp/delete-categories-run.sql) statements"
"${MYSQL[@]}" < /tmp/delete-categories-run.sql

echo "=== AFTER ==="
"${MYSQL[@]}" -N -e "
SELECT 'products', COUNT(*) FROM catalog_product_entity UNION ALL
SELECT 'categories', COUNT(*) FROM catalog_category_entity UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'cms_blocks', COUNT(*) FROM cms_block;
"

sudo -u www-data php /var/www/magento/bin/magento cache:flush 2>&1 | tail -2
sudo -u www-data php /var/www/magento/bin/magento indexer:reindex catalog_category_product catalog_product_category catalogsearch_fulltext 2>&1 | tail -5
echo Done.
