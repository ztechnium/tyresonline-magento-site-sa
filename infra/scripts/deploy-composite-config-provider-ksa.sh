#!/bin/bash
set -euo pipefail
REMOTE=/var/www/magento
SRC=/tmp/CompositeConfigProvider.php

sudo cp "$SRC" "$REMOTE/app/code/Hdweb/Core/Model/Checkout/CompositeConfigProvider.php"
sudo chown www-data:www-data "$REMOTE/app/code/Hdweb/Core/Model/Checkout/CompositeConfigProvider.php"

cd "$REMOTE"
sudo -u www-data php bin/magento cache:flush config layout block_html full_page 2>&1 | tail -3
sudo rm -rf var/page_cache/* var/view_preprocessed/* generated/code/Hdweb/Core/ 2>/dev/null || true

echo "=== Verify class ==="
sudo -u www-data php -r "require 'app/bootstrap.php'; echo class_exists('Hdweb\Core\Model\Checkout\CompositeConfigProvider') ? 'OK' : 'FAIL'; echo PHP_EOL;"

echo "=== Cart totals test ==="
sudo -u www-data php /tmp/test-cart-totals.php 2>&1
