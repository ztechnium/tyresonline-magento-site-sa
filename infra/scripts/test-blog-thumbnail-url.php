#!/usr/bin/env php
<?php
declare(strict_types=1);
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Throwable) {}
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeManager->setCurrentStore('ar');
$helper = $om->get(\Hdweb\Tyrefinder\Helper\ProductImage::class);
$collection = $om->create(\MGS\Blog\Model\ResourceModel\Post\Collection::class);
$collection->setPageSize(3);
foreach ($collection as $post) {
    echo 'title=' . $post->getTitle() . "\n";
    echo 'thumb=' . $post->getThumbnail() . "\n";
    echo 'url=' . $helper->getBlogThumbnailUrl($post) . "\n---\n";
}
