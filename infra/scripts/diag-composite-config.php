<?php
require '/var/www/magento/app/bootstrap.php';
$m = include BP . '/generated/metadata/frontend.php';
$map = $m['arguments']['Magento\\Checkout\\Model\\CompositeConfigProvider']['configProviders']['_vac_'] ?? [];
echo 'provider_count=' . count($map) . PHP_EOL;
$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try { $state->setAreaCode('frontend'); } catch (\Exception $e) {}
$p = $om->get(\Magento\Checkout\Model\CompositeConfigProvider::class);
$c = $p->getConfig();
echo 'config_keys=' . count($c) . PHP_EOL;
echo 'sample_keys=' . implode(',', array_slice(array_keys($c), 0, 10)) . PHP_EOL;
