#!/bin/bash
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html' -o /tmp/car6.html
grep -iE 'filter-current|amshopby|state-item' /tmp/car6.html | head -10

cd /var/www/magento
sudo cp app/code/Hdweb/Tyrefinder/etc/events.xml /tmp/events.xml.bak
sudo cp /tmp/DebugCollection.php app/code/Hdweb/Tyrefinder/Observer/DebugCollection.php 2>/dev/null || sudo tee app/code/Hdweb/Tyrefinder/Observer/DebugCollection.php >/dev/null <<'PHP'
<?php
namespace Hdweb\Tyrefinder\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
class DebugCollection implements ObserverInterface {
    public function execute(Observer $observer) {
        $col = $observer->getEvent()->getCollection();
        file_put_contents('/tmp/list_collection_debug.log', date('c').' class='.get_class($col).' size='.$col->getSize().' sql='.substr((string)$col->getSelect(),0,200)."\n", FILE_APPEND);
    }
}
PHP

sudo sed -i '/<\/event>/i\        <observer name="debug_collection" instance="Hdweb\\Tyrefinder\\Observer\\DebugCollection"/>' app/code/Hdweb/Tyrefinder/etc/events.xml

sudo rm -f /tmp/list_collection_debug.log
sudo -u www-data php bin/magento cache:flush >/dev/null 2>&1
curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?dbg3=1' >/dev/null
echo '=== observer log ==='
cat /tmp/list_collection_debug.log 2>/dev/null

sudo cp /tmp/events.xml.bak app/code/Hdweb/Tyrefinder/etc/events.xml
sudo rm -f app/code/Hdweb/Tyrefinder/Observer/DebugCollection.php
