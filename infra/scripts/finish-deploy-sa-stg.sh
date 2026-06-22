#!/bin/bash
set -euo pipefail
cd /var/www/magento
sudo cp /tmp/env.php.prod app/etc/env.php
sudo chown ubuntu:www-data app/etc/env.php

php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f en_US ar_SA
php bin/magento indexer:reindex
php bin/magento cache:flush
php bin/magento deploy:mode:set production

mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com \
  -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa <<'SQL'
UPDATE core_config_data SET value='https://stg.tyresonline.sa/' WHERE path IN ('web/unsecure/base_url','web/secure/base_url','web/unsecure/base_link_url','web/secure/base_link_url');
UPDATE core_config_data SET value='https://stg.tyresonline.sa/static/' WHERE path IN ('web/unsecure/base_static_url','web/secure/base_static_url');
UPDATE core_config_data SET value='https://stg.tyresonline.sa/media/' WHERE path IN ('web/unsecure/base_media_url','web/secure/base_media_url');
SQL

sudo chown -R ubuntu:www-data var generated pub/static
echo DEPLOY_DONE
