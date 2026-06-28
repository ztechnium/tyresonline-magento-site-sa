#!/usr/bin/env php
<?php
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$c = $om->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
$brands = $c->fetchAll('SELECT * FROM mgs_brand ORDER BY brand_id');
$stores = $c->fetchAll('SELECT brand_id, store_id FROM mgs_brand_store');
echo json_encode(['brands' => $brands, 'stores' => $stores], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
