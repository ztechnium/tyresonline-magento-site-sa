#!/bin/bash
set -euo pipefail
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

mysql $DB -e "
UPDATE cataloginventory_stock_item SET qty=GREATEST(qty,5), is_in_stock=1 WHERE qty < 5 OR is_in_stock=0;
INSERT INTO cataloginventory_stock_item (product_id, stock_id, qty, is_in_stock, manage_stock, use_config_manage_stock, min_qty, is_qty_decimal, backorders, use_config_backorders, min_sale_qty, use_config_min_sale_qty, max_sale_qty, use_config_max_sale_qty, is_decimal_divided, website_id)
SELECT p.entity_id, 1, 5, 1, 1, 1, 0, 0, 0, 1, 1, 1, 10000, 1, 0, 0
FROM catalog_product_entity p
LEFT JOIN cataloginventory_stock_item s ON s.product_id=p.entity_id
WHERE s.product_id IS NULL;

INSERT INTO inventory_source_item (source_code, sku, quantity, status)
SELECT 'default', p.sku, 5, 1 FROM catalog_product_entity p
LEFT JOIN inventory_source_item si ON si.sku=p.sku AND si.source_code='default'
WHERE si.sku IS NULL;

UPDATE inventory_source_item si
JOIN catalog_product_entity p ON p.sku=si.sku
SET si.quantity=GREATEST(si.quantity,5), si.status=1
WHERE si.source_code='default' AND (si.quantity < 5 OR si.status=0);

UPDATE inventory_stock_sales_channel SET stock_id=3 WHERE type='website' AND code='base';
INSERT IGNORE INTO inventory_source_stock_link (stock_id, source_code, priority) VALUES (3,'default',1);
"

cd /var/www/magento
sudo -u www-data php bin/magento indexer:reindex inventory cataloginventory_stock catalogsearch_fulltext 2>&1

mysql $DB -e "SELECT COUNT(*) stock_status_ok FROM cataloginventory_stock_status WHERE stock_status=1 AND website_id=1;"

sudo -u www-data php bin/magento cache:flush 2>&1 | tail -2
sudo rm -rf var/page_cache/* 2>/dev/null || true

curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' -o /tmp/car3.html
echo "product-item-info: $(grep -c 'product-item-info' /tmp/car3.html)"
grep -iE "can't find|Items " /tmp/car3.html | head -3
