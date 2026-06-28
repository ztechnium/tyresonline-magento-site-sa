<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get('Magento\Framework\App\ResourceConnection')->getConnection();

$page = $c->fetchRow("SELECT * FROM cms_page WHERE identifier='home'");
echo "page_id={$page['page_id']} title={$page['title']}\n";
echo "content:\n{$page['content']}\n\n";

$stores = $c->fetchAll('SELECT * FROM cms_page_store WHERE page_id = ?', [$page['page_id']]);
print_r($stores);

echo "\nTheme per store:\n";
$configs = $c->fetchAll("SELECT scope, scope_id, value FROM core_config_data WHERE path='design/theme/theme_id'");
print_r($configs);

echo "\nMode: ";
echo `cd /var/www/magento && php bin/magento deploy:mode:show 2>/dev/null`;
