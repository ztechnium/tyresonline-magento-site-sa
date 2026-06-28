#!/usr/bin/env bash
set -euo pipefail
URL="${1:-https://stg.tyresonline.sa/ar/}"
echo "=== Live page: $URL ==="
curl -s -H 'Cache-Control: no-cache' "$URL" | grep -c 'TyresOnline.ae' || true
curl -s -H 'Cache-Control: no-cache' "$URL" | grep -oE '.{0,40}TyresOnline\.ae.{0,40}' | head -5 || true
echo "=== Theme grep ==="
grep -r 'TyresOnline.ae' /var/www/magento/app/design/frontend/Hditsol/ 2>/dev/null | head -15 || true
echo "=== Brand config ==="
cd /var/www/magento
php -r "
require 'app/bootstrap.php';
\$b = Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$c = \$b->getObjectManager()->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
foreach (\$c->fetchAll(\"SELECT scope, scope_id, path, value FROM core_config_data WHERE path LIKE 'brand/list_page_settings/%'\") as \$r) {
    echo implode(' | ', \$r) . PHP_EOL;
}
"
echo "=== Block 18 UAE snippets ==="
php -r "
require 'app/bootstrap.php';
\$b = Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$c = \$b->getObjectManager()->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
\$content = \$c->fetchOne('SELECT content FROM cms_block WHERE block_id = 18');
foreach (['الإمارات', 'TyresOnline.ae', 'الإمارات السبع'] as \$needle) {
    if (strpos(\$content, \$needle) !== false) echo \"FOUND: \$needle\n\";
}
"
