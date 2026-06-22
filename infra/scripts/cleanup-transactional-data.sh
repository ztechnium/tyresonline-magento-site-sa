#!/bin/bash
set -e
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB=tyresonline_sa

mysql_cmd() {
  mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" "$@"
}

echo "=== BEFORE ==="
mysql_cmd -N -e "
SELECT 'orders', COUNT(*) FROM sales_order UNION ALL
SELECT 'customers', COUNT(*) FROM customer_entity UNION ALL
SELECT 'quotes', COUNT(*) FROM quote UNION ALL
SELECT 'reviews', COUNT(*) FROM review UNION ALL
SELECT 'newsletter', COUNT(*) FROM newsletter_subscriber UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'cms_blocks', COUNT(*) FROM cms_block UNION ALL
SELECT 'products', COUNT(*) FROM catalog_product_entity;
"

TABLES=$(mysql_cmd -N -e "
SELECT table_name FROM information_schema.tables
WHERE table_schema = '$DB'
AND (
  table_name LIKE 'sales\_%'
  OR table_name LIKE 'quote%'
  OR table_name REGEXP '^customer_(entity|address|visitor|log|grid_flat)'
  OR table_name LIKE 'review%'
  OR table_name LIKE 'wishlist%'
  OR table_name LIKE 'newsletter\_%'
  OR table_name LIKE 'report\_%'
  OR table_name LIKE 'sequence\_%'
  OR table_name LIKE 'purchase\_order%'
  OR table_name LIKE 'mageworx\_order%'
  OR table_name LIKE 'mageworx\_ordersgrid%'
  OR table_name LIKE 'tax\_order\_aggregated%'
  OR table_name LIKE 'mageplaza\_social\_customer%'
  OR table_name = 'login_as_customer_assistance_allowed'
  OR table_name = 'inventory_shipment_source'
  OR table_name = 'magento_sales_order_grid_archive'
  OR table_name = 'salesrule_customer'
)
AND table_name NOT IN (
  'sales_order_status',
  'sales_order_status_state',
  'sales_order_status_label',
  'customer_group',
  'customer_eav_attribute',
  'customer_eav_attribute_website',
  'customer_form_attribute'
)
ORDER BY table_name;
")

echo "Truncating $(echo \"$TABLES\" | wc -w) tables..."
mysql_cmd -e "SET SESSION foreign_key_checks = 0;"
for t in $TABLES; do
  echo "  TRUNCATE $t"
  mysql_cmd -e "TRUNCATE TABLE \`$t\`;" || echo "  WARN: failed $t"
done
mysql_cmd -e "SET SESSION foreign_key_checks = 1;"

echo "=== AFTER ==="
mysql_cmd -N -e "
SELECT 'orders', COUNT(*) FROM sales_order UNION ALL
SELECT 'customers', COUNT(*) FROM customer_entity UNION ALL
SELECT 'quotes', COUNT(*) FROM quote UNION ALL
SELECT 'reviews', COUNT(*) FROM review UNION ALL
SELECT 'newsletter', COUNT(*) FROM newsletter_subscriber UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'cms_blocks', COUNT(*) FROM cms_block UNION ALL
SELECT 'products', COUNT(*) FROM catalog_product_entity;
"

sudo -u www-data php /var/www/magento/bin/magento cache:flush 2>&1 | tail -2
echo "Done."
