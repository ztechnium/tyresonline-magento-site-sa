#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$conn = $bootstrap->getObjectManager()->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

echo "=== All cms_block with الإمارات ===\n";
foreach ($conn->fetchAll(
    "SELECT block_id, identifier,
     (LENGTH(content)-LENGTH(REPLACE(content,'الإمارات','')))/CHAR_LENGTH('الإمارات') uae
     FROM cms_block HAVING uae > 0 ORDER BY uae DESC LIMIT 25"
) as $r) {
    echo "block {$r['block_id']} ({$r['identifier']}) uae={$r['uae']}\n";
}

echo "\n=== Mageplaza banners with UAE ===\n";
$tables = ['mageplaza_bannerslider_banner', 'mageplaza_bannerslider_slider'];
foreach ($tables as $t) {
    try {
        $cols = $conn->describeTable($t);
        $textCols = array_keys(array_filter($cols, fn($c) => stripos($c['DATA_TYPE'] ?? '', 'char') !== false || stripos($c['DATA_TYPE'] ?? '', 'text') !== false));
        foreach ($textCols as $col) {
            $rows = $conn->fetchAll("SELECT * FROM {$t} WHERE {$col} LIKE '%الإمارات%' OR {$col} LIKE '%UAE%' LIMIT 5");
            foreach ($rows as $row) {
                echo "$t.$col id=" . ($row['banner_id'] ?? $row['slider_id'] ?? '?') . "\n";
                echo "  " . mb_substr((string)($row[$col] ?? ''), 0, 120) . "\n";
            }
        }
    } catch (Throwable $e) {
        echo "$t: " . $e->getMessage() . "\n";
    }
}

echo "\n=== ar_SA.csv UAE lines on server ===\n";
$csv = @file_get_contents(BP . '/app/design/frontend/Hditsol/tyresonline-ar/i18n/ar_SA.csv');
if ($csv) {
    $n = 0;
    foreach (explode("\n", $csv) as $line) {
        if (strpos($line, 'الإمارات') !== false || stripos($line, 'UAE') !== false) {
            echo mb_substr($line, 0, 160) . "\n";
            if (++$n >= 15) break;
        }
    }
}
