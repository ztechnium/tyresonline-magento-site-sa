<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(Magento\Framework\App\State::class)->setAreaCode('frontend');
$c = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
$rows = $c->fetchAll(
    'SELECT entity_id, items_count, pickup_store, pickup_date, pickup_time, customer_email, updated_at
     FROM quote WHERE is_active = 1 AND items_count > 0 ORDER BY updated_at DESC LIMIT 8'
);
foreach ($rows as $r) {
    echo json_encode($r) . PHP_EOL;
}
