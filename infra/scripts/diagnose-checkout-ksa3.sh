#!/bin/bash
sed -n '48,130p' /var/www/magento/app/code/Hdweb/Core/Helper/Data.php

echo '=== getmodel test ==='
curl -s 'https://stg.tyresonline.sa/en/tyrefinder/ajax/getmodel?make=toyota' | head -c 500
echo

echo '=== exception tail ==='
tail -3 /var/www/magento/var/log/exception.log 2>/dev/null | head -c 800
