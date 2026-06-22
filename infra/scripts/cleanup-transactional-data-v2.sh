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
  OR table_name LIKE 'amazon\_%'
  OR table_name LIKE 'rating\_option\_vote%'
  OR table_name LIKE 'downloadable\_link\_purchased%'
  OR table_name LIKE 'email\_wishlist%'
  OR table_name LIKE 'inventory\_pickup\_location\_quote%'
  OR table_name = 'login_as_customer_assistance_allowed'
  OR table_name = 'inventory_shipment_source'
  OR table_name = 'salesrule_customer'
)
AND table_name NOT IN (
  'sales_order_status','sales_order_status_state','sales_order_status_label',
  'customer_group','customer_eav_attribute','customer_eav_attribute_website','customer_form_attribute',
  'review_status','review_entity','report_event_types','newsletter_template'
)
ORDER BY table_name;
" | while read -r t; do
  printf 'TRUNCATE TABLE `%s`;\n' "$t"
done > /tmp/truncate-all.sql

{
  echo 'SET FOREIGN_KEY_CHECKS=0;'
  cat /tmp/truncate-all.sql
  echo 'SET FOREIGN_KEY_CHECKS=1;'
} > /tmp/truncate-run.sql

echo "Generated $(grep -c TRUNCATE /tmp/truncate-run.sql) truncates"

echo "=== BEFORE ==="
mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB" -N -e "
SELECT 'orders', COUNT(*) FROM sales_order UNION ALL
SELECT 'customers', COUNT(*) FROM customer_entity UNION ALL
SELECT 'quotes', COUNT(*) FROM quote UNION ALL
SELECT 'reviews', COUNT(*) FROM review UNION ALL
SELECT 'newsletter', COUNT(*) FROM newsletter_subscriber UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'products', COUNT(*) FROM catalog_product_entity;
"

mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB" < /tmp/truncate-run.sql

echo "=== AFTER ==="
mysql -h "$HOST" -u "$USER" -p"$PASS" "$DB" -N -e "
SELECT 'orders', COUNT(*) FROM sales_order UNION ALL
SELECT 'customers', COUNT(*) FROM customer_entity UNION ALL
SELECT 'quotes', COUNT(*) FROM quote UNION ALL
SELECT 'reviews', COUNT(*) FROM review UNION ALL
SELECT 'newsletter', COUNT(*) FROM newsletter_subscriber UNION ALL
SELECT 'admin_users', COUNT(*) FROM admin_user UNION ALL
SELECT 'storelocator', COUNT(*) FROM ecomteck_storelocator_stores UNION ALL
SELECT 'cms_pages', COUNT(*) FROM cms_page UNION ALL
SELECT 'products', COUNT(*) FROM catalog_product_entity;
"

sudo -u www-data php /var/www/magento/bin/magento cache:flush 2>&1 | tail -2
echo Done.
