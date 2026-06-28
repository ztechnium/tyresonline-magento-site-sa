#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Exception $e) {
}

$emulation = $om->get(\Magento\Store\Model\App\Emulation::class);
$emulation->startEnvironmentEmulation(2, 'frontend', true);

$phrases = [
    'See on Map',
    'See On Map',
    'Select Installer',
    'Select Fitter',
];

foreach ($phrases as $phrase) {
    echo $phrase . ' => ' . (string) __($phrase) . PHP_EOL;
}

$emulation->stopEnvironmentEmulation();
