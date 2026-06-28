#!/bin/bash
set -e
perl -pi -e "s/'engine' => 'opensearch'/'engine' => 'elasticsuite'/" /var/www/magento/app/etc/env.php
grep -n "engine" /var/www/magento/app/etc/env.php | grep -v innodb
