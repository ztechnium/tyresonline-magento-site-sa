<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$c = $bootstrap->getObjectManager()->get('Magento\Framework\App\ResourceConnection')->getConnection();

$tables = $c->fetchCol('SHOW TABLES');
$hits = [];
foreach ($tables as $table) {
    $cols = $c->fetchCol("SHOW COLUMNS FROM `$table`");
    $textCols = array_values(array_filter($cols, fn($col) => preg_match('/content|value|body|html|data|serialized/i', $col)));
    if (!$textCols) {
        continue;
    }
    foreach ($textCols as $col) {
        try {
            $count = (int)$c->fetchOne("SELECT COUNT(*) FROM `$table` WHERE `$col` LIKE ?", ['%Map.svg%']);
            if ($count > 0) {
                $hits[] = "$table.$col => $count";
            }
        } catch (\Throwable $e) {
            // skip
        }
    }
}
echo implode(PHP_EOL, $hits) ?: "No Map.svg in DB text columns\n";

$env = include BP . '/app/etc/env.php';
echo 'page_cache backend: ' . ($env['cache']['frontend']['page_cache']['backend'] ?? 'default') . PHP_EOL;
