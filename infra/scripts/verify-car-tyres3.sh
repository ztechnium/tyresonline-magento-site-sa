#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== catalog_category_product_index ==='
mysql $DB -e "SHOW TABLES LIKE '%category_product%';"
mysql $DB -e "SELECT store_id, COUNT(*) c FROM catalog_category_product_index GROUP BY store_id;"
mysql $DB -e "SELECT category_id, COUNT(*) c FROM catalog_category_product_index WHERE category_id IN (1944,1945,2) GROUP BY category_id;"

echo '=== STORE / WEBSITE ==='
mysql $DB -e "SELECT store_id, code, website_id FROM store;"
mysql $DB -e "SELECT website_id, code FROM store_website;"

echo '=== SAMPLE NEW INDEX DOC ==='
curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product_20260617_133729/_search' -H 'Content-Type: application/json' -d '{"size":1,"query":{"match_all":{}}}' | python3 -m json.tool | head -80

echo '=== SEARCH category in new index (nested?) ==='
curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product_20260617_133729/_mapping' | python3 -c "import sys,json; m=json.load(sys.stdin); props=list(m.values())[0]['mappings']['properties'].keys(); print([p for p in props if 'categ' in p.lower()])"

curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product_20260617_133729/_search' -H 'Content-Type: application/json' -d '{"size":0,"aggs":{"cats":{"terms":{"field":"category.category_id","size":10}}}}' 2>/dev/null | python3 -m json.tool | head -30

curl -s 'http://127.0.0.1:9200/satyresonline2_en_catalog_product_20260617_133729/_search' -H 'Content-Type: application/json' -d '{"size":0,"aggs":{"cats":{"terms":{"field":"category_ids","size":10}}}}' 2>/dev/null | python3 -m json.tool | head -30

echo '=== Magento search engine config ==='
cd /var/www/magento
sudo -u www-data php bin/magento config:show catalog/search/engine 2>/dev/null
sudo -u www-data php bin/magento config:show smile_elasticsuite_core_base_settings/indices_settings/alias 2>/dev/null
