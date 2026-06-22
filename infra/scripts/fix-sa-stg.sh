#!/bin/bash
set -euo pipefail
cd /var/www/magento

mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com \
  -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa <<'SQL'
UPDATE core_config_data SET value='.tyresonline.sa' WHERE path='web/cookie/cookie_domain';
UPDATE core_config_data SET value='satyresonline2' WHERE path LIKE '%opensearch_index_prefix%' OR path LIKE '%elasticsearch%index_prefix%';
DELETE FROM core_config_data WHERE path LIKE 'web/%' AND value LIKE '%tyresonline.ae%';
SQL

php bin/magento cache:flush
php bin/magento indexer:reindex catalogsearch_fulltext

sudo chown -R www-data:www-data var generated pub/static
sudo chmod -R 775 var generated pub/static

echo FIX_DONE
