#!/bin/bash
set -e
cd /var/www/magento
php bin/magento indexer:reindex catalogsearch_fulltext
php bin/magento indexer:reindex elasticsuite_categories_fulltext
php bin/magento cache:flush
curl -s 'http://127.0.0.1:9200/_cat/aliases?v' | grep catalog_product || true
