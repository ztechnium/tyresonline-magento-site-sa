<?php
require '/var/www/magento/app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$om->get(Magento\Framework\App\State::class)->setAreaCode('frontend');
$c = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
$row = $c->fetchRow('SELECT item_id, quote_id, qty, sku FROM quote_item WHERE item_id = 76');
echo json_encode($row) . PHP_EOL;
$quote = $c->fetchRow('SELECT entity_id, items_count, pickup_store, pickup_date, pickup_time FROM quote WHERE entity_id = ' . (int)$row['quote_id']);
echo json_encode($quote) . PHP_EOL;
