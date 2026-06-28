<?php
require __DIR__ . '/../../app/bootstrap.php';
$b = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$c = $b->getObjectManager()->get(Magento\Framework\App\ResourceConnection::class)->getConnection();
$c->update('mageplaza_bannerslider_banner', ['url_banner' => 'mobile-tyre-fitting-service-in-uae'], ['banner_id = ?' => 8]);
echo "banner 8 updated\n";
