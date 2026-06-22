#!/bin/bash
set -euo pipefail
DOMAIN="stg.tyresonline.sa"
BASE="https://${DOMAIN}/"
cd /var/www/magento

sudo cp /tmp/env.php.prod /var/www/magento/app/etc/env.php
sudo chown ubuntu:www-data app/etc/env.php

php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f en_US ar_SA
php bin/magento indexer:reindex
php bin/magento cache:flush
php bin/magento deploy:mode:set production

mysql -h "$(php -r '$e=include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["host"];')" \
  -u "$(php -r '$e=include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["username"];')" \
  -p"$(php -r '$e=include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["password"];')" \
  "$(php -r '$e=include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["dbname"];')" <<SQL
UPDATE core_config_data SET value='${BASE}' WHERE path IN ('web/unsecure/base_url','web/secure/base_url','web/unsecure/base_link_url','web/secure/base_link_url');
UPDATE core_config_data SET value='${BASE}static/' WHERE path IN ('web/unsecure/base_static_url','web/secure/base_static_url');
UPDATE core_config_data SET value='${BASE}media/' WHERE path IN ('web/unsecure/base_media_url','web/secure/base_media_url');
SQL

sudo chown -R ubuntu:www-data var generated pub/static
sudo find var generated pub/static -type d -exec chmod 775 {} +
sudo find var generated pub/static -type f -exec chmod 664 {} +

echo "POST_DEPLOY_DONE"
