#!/bin/bash
set -e
DB=tyresonline_sa
HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
USER=magento
PASS=uxaYMIQRwEU0AFl1Ck69LJnH

mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB" -N -e "
SELECT table_name FROM information_schema.tables
WHERE table_schema = '$DB'
AND (
  table_name LIKE 'catalog_product%'
  OR table_name LIKE 'cataloginventory%'
  OR table_name = 'catalog_category_product'
  OR table_name LIKE 'inventory_source_item'
  OR table_name LIKE 'inventory_reservation'
  OR table_name LIKE 'inventory_stock_%'
  OR table_name LIKE 'sequence_product%'
)
AND table_name NOT IN (
  'catalog_product_entity_type',
  'catalog_product_link_type',
  'catalog_product_link_attribute',
  'catalog_product_option_type_price',
  'catalog_product_attribute_cl',
  'catalog_product_category_cl',
  'catalog_product_price_cl'
)
ORDER BY table_name;
" | while read -r t; do
  printf 'TRUNCATE TABLE `%s`;\n' "$t"
done > /tmp/truncate-products.sql

# Product URL rewrites (keep category/cms rewrites)
echo "DELETE FROM url_rewrite WHERE entity_type = 'product';" >> /tmp/truncate-products.sql

{
  echo 'SET FOREIGN_KEY_CHECKS=0;'
  cat /tmp/truncate-products.sql
  echo 'SET FOREIGN_KEY_CHECKS=1;'
} > /tmp/truncate-products-run.sql

echo "Generated $(grep -cE 'TRUNCATE|DELETE' /tmp/truncate-products-run.sql) statements"

echo "=== BEFORE ==="
mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB" -N -e "
SELECT 'products', COUNT(*) FROM catalog_product_entity UNION ALL
SELECT 'categories', COUNT(*) FROM catalog_category_entity UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'cms_blocks', COUNT(*) FROM cms_block;
"

mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB" < /tmp/truncate-products-run.sql

echo "=== AFTER ==="
mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB" -N -e "
SELECT 'products', COUNT(*) FROM catalog_product_entity UNION ALL
SELECT 'categories', COUNT(*) FROM catalog_category_entity UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'cms_blocks', COUNT(*) FROM cms_block;
"

sudo -u www-data php /var/www/magento/bin/magento cache:flush 2>&1 | tail -2
echo Done.
