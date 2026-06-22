#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== cataloginventory_stock_status ==='
mysql $DB -e "SELECT COUNT(*) total, SUM(stock_status=1) in_stock FROM cataloginventory_stock_status;"
mysql $DB -e "SELECT * FROM cataloginventory_stock_status LIMIT 5;"

echo '=== OpenSearch stock fields sample ==='
curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product/_search' -H 'Content-Type: application/json' -d '{"size":3,"_source":["sku","stock","is_in_stock","quantity_and_stock_status"]}' | python3 -m json.tool | head -80

echo '=== in-stock filter count in index ==='
curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product/_search' -H 'Content-Type: application/json' -d '{"size":0,"query":{"bool":{"must":[{"nested":{"path":"category","query":{"term":{"category.category_id":1945}}}},{"term":{"stock.is_in_stock":true}}]}}}' | python3 -m json.tool

curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product/_search' -H 'Content-Type: application/json' -d '{"size":0,"query":{"bool":{"must":[{"nested":{"path":"category","query":{"term":{"category.category_id":1945}}}},{"term":{"stock.is_in_stock":false}}]}}}' | python3 -m json.tool

echo '=== ElasticSuite stock config ==='
cd /var/www/magento
sudo -u www-data php bin/magento config:show cataloginventory/options/show_out_of_stock 2>/dev/null
sudo -u www-data php bin/magento config:show smile_elasticsuite_catalogsearch_settings 2>/dev/null | head -10
grep -r 'is_in_stock\|stock_status\|ShowOutOfStock' vendor/smile/elasticsuite/src/module-elasticsuite-catalog/Model/Layer/Filter/Stock.php 2>/dev/null | head -10
