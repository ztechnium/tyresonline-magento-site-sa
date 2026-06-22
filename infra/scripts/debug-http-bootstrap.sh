#!/bin/bash
cd /var/www/magento/pub
sudo -u www-data php -r "
\$_SERVER['HTTP_HOST']='stg.tyresonline.sa';
\$_SERVER['REQUEST_URI']='/en/all-tyres/car-tyres.html?cli=1';
\$_SERVER['SCRIPT_NAME']='/index.php';
\$_SERVER['SCRIPT_FILENAME']='/var/www/magento/pub/index.php';
\$_SERVER['DOCUMENT_ROOT']='/var/www/magento/pub';
\$_SERVER['REQUEST_METHOD']='GET';
\$_SERVER['SERVER_PORT']='443';
\$_SERVER['HTTPS']='on';
\$_SERVER['REMOTE_ADDR']='127.0.0.1';
require '../app/bootstrap.php';
\$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
/** @var \Magento\Framework\App\Http \$app */
\$app = \$bootstrap->createApplication(\Magento\Framework\App\Http::class);
\$bootstrap->run(\$app);
" 2>/tmp/http_err.log | tee /tmp/http_out.html | grep -oE 'DEBUG size=[^<]+|product-item-info|can.t find' | head -10

echo '--- err tail ---'
tail -3 /tmp/http_err.log 2>/dev/null
echo 'product items:' $(grep -c 'product-item-info' /tmp/http_out.html)
