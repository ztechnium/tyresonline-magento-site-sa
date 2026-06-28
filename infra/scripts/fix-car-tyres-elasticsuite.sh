#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
ENV=$MAGENTO/app/etc/env.php
UAE=ubuntu@ec2-13-50-7-98.eu-north-1.compute.amazonaws.com
KEY=/tmp/recovery.pem

echo "=== 1. Fix env.php search engine (opensearch -> elasticsuite) ==="
python3 - <<'PY'
from pathlib import Path
p = Path("/var/www/magento/app/etc/env.php")
text = p.read_text()
old = "'engine' => 'opensearch',"
new = "'engine' => 'elasticsuite',"
if old not in text:
    if "'engine' => 'elasticsuite'," in text:
        print("env.php already uses elasticsuite engine")
    else:
        raise SystemExit("engine line not found in env.php")
else:
    p.write_text(text.replace(old, new, 1))
    print("env.php engine set to elasticsuite")
PY

echo "=== 2. Copy missing tyre_compare.phtml from UAE ==="
mkdir -p "$MAGENTO/app/code/Hdweb/Tyrefinder/view/frontend/templates"
if [ -f "$KEY" ]; then
  scp -o StrictHostKeyChecking=no -i "$KEY" \
    "$UAE:/var/www/magento/app/code/Hdweb/Tyrefinder/view/frontend/templates/tyre_compare.phtml" \
    "$MAGENTO/app/code/Hdweb/Tyrefinder/view/frontend/templates/tyre_compare.phtml"
  echo "tyre_compare.phtml copied"
else
  echo "WARN: $KEY missing, skipping template copy"
fi

echo "=== 3. Reindex catalog search (ElasticSuite catalog_product indices) ==="
cd "$MAGENTO"
php bin/magento indexer:reindex catalogsearch_fulltext 2>&1 | tail -5

echo "=== 4. Reindex ElasticSuite categories ==="
php bin/magento indexer:reindex elasticsuite_categories_fulltext 2>&1 | tail -3

echo "=== 5. Verify OpenSearch indices ==="
curl -s 'http://127.0.0.1:9200/_cat/aliases?v' | grep -E 'catalog_product|product_1' || true

echo "=== 6. Flush cache ==="
php bin/magento cache:flush 2>&1 | tail -3
sudo rm -rf var/page_cache/* var/view_preprocessed/* 2>/dev/null || true
sudo systemctl restart apache2

sleep 4
echo "=== 7. Test car-tyres page ==="
curl -s "https://stg.tyresonline.sa/en/all-tyres/car-tyres.html" -o /tmp/car-tyres-fixed.html
wc -c /tmp/car-tyres-fixed.html
echo -n "product-item-info: "; grep -c 'product-item-info' /tmp/car-tyres-fixed.html || echo 0
echo -n "modern-layout: "; grep -c 'product-box modern-layout' /tmp/car-tyres-fixed.html || echo 0
echo -n "SAR prices: "; grep -oE 'SAR [0-9][0-9.,]+' /tmp/car-tyres-fixed.html | wc -l
tail -3 "$MAGENTO/var/log/system.log" || true
