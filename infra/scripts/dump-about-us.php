<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get('Magento\Framework\App\ResourceConnection')->getConnection();
$content = $c->fetchOne('SELECT content FROM cms_page WHERE page_id = 20');
file_put_contents('/tmp/about-cms.html', $content);
echo 'len=' . strlen($content) . PHP_EOL;
echo (substr_count($content, 'TYRESONLINE.AE') + substr_count($content, 'TyresOnline.ae')) . ' ae refs' . PHP_EOL;
echo substr_count($content, 'نفخر TyresOnline') . ' nafkhar' . PHP_EOL;
if (preg_match_all('/<img[^>]+>/', $content, $m)) {
    foreach ($m[0] as $img) {
        echo $img . PHP_EOL;
    }
}
