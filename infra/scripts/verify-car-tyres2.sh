#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

echo '=== OPENSEARCH INDICES ==='
curl -s 'http://127.0.0.1:9200/_cat/indices?v' | grep satyres

echo '=== ALIASES ==='
curl -s 'http://127.0.0.1:9200/_cat/aliases?v' | grep satyres

echo '=== DB catalog_product_category for product 5112 ==='
mysql $DB -e "SELECT * FROM catalog_product_category WHERE product_id=5112 LIMIT 20;"

echo '=== DB category count 1945 in index table ==='
mysql $DB -e "SELECT COUNT(*) FROM catalog_category_product_index WHERE category_id=1945 AND store_id=1;"

echo '=== SAMPLE indexed categories for recent products ==='
mysql $DB -e "SELECT product_id, category_id FROM catalog_category_product WHERE category_id=1945 LIMIT 5;"

echo '=== Search any doc with category 1945 across all indices ==='
for idx in $(curl -s 'http://127.0.0.1:9200/_cat/indices?h=index' | grep satyres); do
  cnt=$(curl -s "http://127.0.0.1:9200/$idx/_count" -H 'Content-Type: application/json' -d '{"query":{"terms":{"category_ids":["1945"]}}}' | python3 -c "import sys,json; print(json.load(sys.stdin).get('count',0))" 2>/dev/null)
  echo "$idx => $cnt"
done

echo '=== Page toolbar / items ==='
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' | grep -E 'toolbar-amount|product-item-info|can.t find|Items ' | head -10
