#!/usr/bin/env php
<?php
/**
 * Translate home_brands CMS block + ar_SA.csv strings for Arabic homepage.
 * Source: Home Page - KSA Website - Ar.docx
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

$titleEn = 'Authorized Tyre brands';
$titleAr = 'ماركات كفرات معتمدة';
$paraEn = 'We have brands for every taste and need. From globally established, award winning and innovative brands to newcomers in the market looking to make their mark.';
$paraAr = 'لدينا علامات تجارية تلبي كل الأذواق والاحتياجات، من العلامات العالمية الراسخة والحائزة على جوائز والمبتكرة، إلى العلامات الناشئة في السوق التي تسعى لترك بصمتها.';

$row = $conn->fetchRow(
    "SELECT block_id, content FROM cms_block WHERE identifier = ?",
    ['home_brands']
);
if (!$row) {
    echo "home_brands block not found\n";
    exit(1);
}

$content = (string) $row['content'];
$original = $content;

$content = preg_replace(
    '/<h2[^>]*>\s*Authorized\s*<span>\s*Tyre brands\s*<\/span>\s*<\/h2>/i',
    '<h2 class="text-uppercase">' . $titleAr . '</h2>',
    $content
);
$content = preg_replace(
    '/<h2[^>]*>\s*' . preg_quote('Tyre Brands', '/') . '\s*<span>\s*' . preg_quote('We Trust', '/') . '\s*<\/span>\s*<\/h2>/i',
    '<h2 class="text-uppercase">' . $titleAr . '</h2>',
    $content
);

$content = str_replace($paraEn, $paraAr, $content);
$content = str_replace(
    'We have brands for every taste and need. From globally established, award winning and innovative brands to newcomers to the market looking to make their mark.',
    $paraAr,
    $content
);

if ($content === $original) {
    echo "WARN: home_brands content unchanged — check block HTML structure\n";
    echo substr($original, 0, 1200) . "\n";
} else {
    $conn->update('cms_block', ['content' => $content], ['block_id = ?' => (int) $row['block_id']]);
    echo "UPDATED cms_block home_brands (id={$row['block_id']})\n";
}

$csvPath = BP . '/app/design/frontend/Hditsol/tyresonline-ar/i18n/ar_SA.csv';
$csv = is_readable($csvPath) ? file_get_contents($csvPath) : '';
$lines = [
    $titleEn => $titleAr,
    'Authorized' => 'ماركات',
    'Tyre brands' => 'كفرات معتمدة',
    $paraEn => $paraAr,
    'We have brands for every taste and need. From globally established, award winning and innovative brands to newcomers to the market looking to make their mark.' => $paraAr,
    '60+ Authorized Tyre Brands' => 'ماركات كفرات معتمدة +50',
];
foreach ($lines as $en => $ar) {
    $line = '"' . str_replace('"', '""', $en) . '","' . str_replace('"', '""', $ar) . '"';
    $pattern = '/^"' . preg_quote(str_replace('"', '""', $en), '/') . '",.*$/m';
    if (preg_match($pattern, $csv)) {
        $csv = preg_replace($pattern, $line, $csv);
        echo "UPDATED csv: $en\n";
    } elseif (strpos($csv, $line) === false) {
        $csv .= "\n" . $line;
        echo "ADDED csv: $en\n";
    }
}
file_put_contents($csvPath, $csv);
echo "Done.\n";
