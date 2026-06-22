#!/bin/bash
echo '=== curl localhost bypass ==='
curl -s -H 'Host: stg.tyresonline.sa' 'http://127.0.0.1/en/all-tyres/car-tyres.html?nocache='$(date +%s) -o /tmp/local.html
grep -c 'product-item-info' /tmp/local.html
grep -i "can't find" /tmp/local.html | head -1

echo '=== cache types ==='
cd /var/www/magento
sudo -u www-data php bin/magento cache:status 2>&1 | head -20

echo '=== fpc config ==='
sudo -u www-data php bin/magento config:show system/full_page_cache 2>/dev/null | head -15

echo '=== nginx fastcgi cache? ==='
grep -r 'fastcgi_cache\|proxy_cache\|varnish' /etc/nginx/ 2>/dev/null | head -10

echo '=== simulate HTTP request via PHP ==='
sudo -u www-data php -r "
\$_SERVER['HTTP_HOST']='stg.tyresonline.sa';
\$_SERVER['REQUEST_URI']='/en/all-tyres/car-tyres.html';
\$_SERVER['SCRIPT_NAME']='/index.php';
\$_SERVER['SCRIPT_FILENAME']='/var/www/magento/pub/index.php';
\$_SERVER['DOCUMENT_ROOT']='/var/www/magento/pub';
\$_SERVER['REQUEST_METHOD']='GET';
\$_SERVER['SERVER_PORT']='443';
\$_SERVER['HTTPS']='on';
require '/var/www/magento/app/bootstrap.php';
\$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$app = \$bootstrap->createApplication(\Magento\Framework\App\Http::class);
ob_start();
try { \$app->launch(); } catch (\Throwable \$e) { echo 'ERR: '.\$e->getMessage(); }
\$html = ob_get_clean();
echo 'html bytes: '.strlen(\$html).PHP_EOL;
echo 'product-item-info: '.substr_count(\$html,'product-item-info').PHP_EOL;
echo (strpos(\$html,\"can't find\")!==false || strpos(\$html,'can&#039;t find')!==false ? 'EMPTY_MSG' : 'HAS_PRODUCTS').PHP_EOL;
" 2>&1 | tail -10
