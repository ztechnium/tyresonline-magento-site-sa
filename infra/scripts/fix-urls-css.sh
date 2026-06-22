#!/bin/bash
set -euo pipefail
BASE="https://stg.tyresonline.sa"
cd /var/www/magento

mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com \
  -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa <<SQL
UPDATE core_config_data SET value='${BASE}/' WHERE path IN (
  'web/unsecure/base_url',
  'web/secure/base_url',
  'web/unsecure/base_link_url',
  'web/secure/base_link_url'
);
UPDATE core_config_data SET value='${BASE}/static/' WHERE path IN (
  'web/unsecure/base_static_url',
  'web/secure/base_static_url'
);
UPDATE core_config_data SET value='${BASE}/media/' WHERE path IN (
  'web/unsecure/base_media_url',
  'web/secure/base_media_url'
);
UPDATE core_config_data SET value='1' WHERE path IN (
  'web/secure/use_in_frontend',
  'web/secure/use_in_adminhtml',
  'web/secure/enable_hsts',
  'web/seo/use_rewrites'
);
UPDATE core_config_data SET value='0' WHERE path IN (
  'web/url/redirect_to_base',
  'web/url/use_store'
);
UPDATE core_config_data SET value='.tyresonline.sa' WHERE path='web/cookie/cookie_domain';
UPDATE core_config_data SET value='1' WHERE path='web/cookie/cookie_httponly';
UPDATE core_config_data SET value='1' WHERE path='web/cookie/cookie_secure';
DELETE FROM core_config_data WHERE value LIKE '%tyresonline.ae%' AND path LIKE 'web/%';
SQL

# Ensure env.php has no conflicting base URLs
sudo -u www-data php bin/magento config:set web/unsecure/base_url "${BASE}/"
sudo -u www-data php bin/magento config:set web/secure/base_url "${BASE}/"
sudo -u www-data php bin/magento config:set web/unsecure/base_static_url "${BASE}/static/"
sudo -u www-data php bin/magento config:set web/secure/base_static_url "${BASE}/static/"
sudo -u www-data php bin/magento config:set web/unsecure/base_media_url "${BASE}/media/"
sudo -u www-data php bin/magento config:set web/secure/base_media_url "${BASE}/media/"
sudo -u www-data php bin/magento config:set web/secure/use_in_frontend 1
sudo -u www-data php bin/magento config:set web/secure/use_in_adminhtml 1
sudo -u www-data php bin/magento config:set web/url/redirect_to_base 0

sudo -u www-data php bin/magento cache:flush
sudo -u www-data php bin/magento setup:static-content:deploy -f en_US ar_SA 2>&1 | tail -5

sudo chown -R www-data:www-data var generated pub/static pub/media
sudo chmod -R 775 var generated pub/static pub/media

echo URL_FIX_DONE
