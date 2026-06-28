<?php
/**
 * Apply KSA Privacy Policy from docx-generated HTML files.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/apply-privacy-policy-ksa.php
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
    27 => [
        'file' => $base . '/privacy-policy-en.html',
        'meta_title' => 'Privacy Policy | TyresOnline.sa',
        'meta_description' => 'Read the TyresOnline.sa Privacy Policy. We protect your personal data in accordance with Saudi Arabia Personal Data Protection Law.',
        'meta_keywords' => 'privacy policy, tyresonline, saudi arabia, personal data protection',
        'title' => 'Privacy Policy',
    ],
    28 => [
        'file' => $base . '/privacy-policy-ar.html',
        'meta_title' => 'سياسة الخصوصية | TyresOnline.sa',
        'meta_description' => 'اطلع على سياسة الخصوصية لموقع TyresOnline.sa. نحمي بياناتك الشخصية وفقًا لنظام حماية البيانات الشخصية في المملكة العربية السعودية.',
        'meta_keywords' => 'سياسة الخصوصية, تايرز أونلاين, السعودية, حماية البيانات',
        'title' => 'Privacy Policy - Arabic',
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
