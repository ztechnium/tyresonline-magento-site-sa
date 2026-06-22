#!/bin/bash
mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa -e "
SHOW TABLES LIKE 'catalog_product_flat%';
SELECT COUNT(*) AS flat_en FROM catalog_product_flat_1;
SELECT COUNT(*) AS flat_store1 FROM catalog_product_flat WHERE store_id=1;
SELECT COUNT(*) AS entities FROM catalog_product_entity;
"
cd /var/www/magento
sudo -u www-data php bin/magento config:show catalog/frontend/flat_catalog_product 2>/dev/null
sudo -u www-data php bin/magento config:show catalog/frontend/flat_catalog_category 2>/dev/null
