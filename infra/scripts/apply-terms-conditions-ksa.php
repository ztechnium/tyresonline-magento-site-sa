<?php
/**
 * Apply KSA Terms & Conditions from docx-generated HTML files.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/apply-terms-conditions-ksa.php
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
try {
    $om->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$base = __DIR__;

$pages = [
    25 => [
        'file' => $base . '/terms-conditions-en.html',
        'meta_title' => 'Terms & Conditions | TyresOnline.sa',
        'meta_description' => 'Terms and conditions for the sale of tyres and fitting services on TyresOnline.sa in the Kingdom of Saudi Arabia.',
        'meta_keywords' => 'terms and conditions, tyresonline, saudi arabia, tyre sale',
        'title' => 'Terms & Conditions for Sale of Tyres',
    ],
    26 => [
        'file' => $base . '/terms-conditions-ar.html',
        'meta_title' => 'شروط وأحكام موقعنا | TyresOnline.sa',
        'meta_description' => 'شروط وأحكام بيع الإطارات وخدمات التركيب على TyresOnline.sa في المملكة العربية السعودية.',
        'meta_keywords' => 'شروط وأحكام, تايرز أونلاين, السعودية, بيع الإطارات',
        'title' => 'Terms & Conditions for Sale of Tyres - Arabic',
    ],
];

foreach ($pages as $pageId => $cfg) {
    if (!is_file($cfg['file'])) {
        echo "Missing {$cfg['file']}\n";
        exit(1);
    }
    $content = file_get_contents($cfg['file']);
    $before = (int)$conn->fetchOne('SELECT LENGTH(content) FROM cms_page WHERE page_id = ?', [$pageId]);

    $update = ['content' => $content];
    foreach (['meta_title', 'meta_description', 'meta_keywords', 'title', 'content_heading'] as $col) {
        $cols = $conn->describeTable('cms_page');
        if (!isset($cols[$col])) {
            continue;
        }
        if ($col === 'content_heading') {
            $update[$col] = '';
            continue;
        }
        if (isset($cfg[$col])) {
            $update[$col] = $cfg[$col];
        }
    }

    $conn->update('cms_page', $update, ['page_id = ?' => $pageId]);
    echo "Updated page_id={$pageId} len {$before} -> " . strlen($content) . "\n";
}

echo "Done\n";
