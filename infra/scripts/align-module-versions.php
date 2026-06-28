#!/usr/bin/env php
<?php
$magentoRoot = '/var/www/magento';
$env = include "$magentoRoot/app/etc/env.php";
$db = $env['db']['connection']['default'];
$pdo = new PDO("mysql:host={$db['host']};dbname={$db['dbname']}", $db['username'], $db['password']);

function findModuleXmlFiles(string $dir): array {
    $files = [];
    if (!is_dir($dir)) return $files;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getFilename() === 'module.xml') {
            $files[] = $file->getPathname();
        }
    }
    return $files;
}

$versions = [];
foreach (["$magentoRoot/app/code", "$magentoRoot/vendor/smile", "$magentoRoot/vendor/magento"] as $dir) {
    foreach (findModuleXmlFiles($dir) as $file) {
        $xml = @simplexml_load_file($file);
        if (!$xml || !isset($xml->module)) continue;
        $name = (string)$xml->module['name'];
        $ver = (string)($xml->module['setup_version'] ?? '1.0.0');
        if ($name) $versions[$name] = $ver ?: '1.0.0';
    }
}

$existing = [];
foreach ($pdo->query('SELECT module FROM setup_module')->fetchAll(PDO::FETCH_COLUMN) as $mod) {
    $existing[$mod] = true;
}

$inserted = 0;
$ins = $pdo->prepare('INSERT INTO setup_module (module, schema_version, data_version) VALUES (?,?,?)');
foreach ($versions as $name => $ver) {
    if (isset($existing[$name])) continue;
    $ins->execute([$name, $ver, $ver]);
    echo "Inserted $name -> $ver\n";
    $inserted++;
}

$rows = $pdo->query('SELECT module, schema_version, data_version FROM setup_module')->fetchAll(PDO::FETCH_ASSOC);
$fixed = 0;
foreach ($rows as $row) {
    if (!isset($versions[$row['module']])) continue;
    $codeVer = $versions[$row['module']];
    if ($row['schema_version'] !== $codeVer || $row['data_version'] !== $codeVer) {
        $pdo->prepare('UPDATE setup_module SET schema_version=?, data_version=? WHERE module=?')
            ->execute([$codeVer, $codeVer, $row['module']]);
        echo "Fixed {$row['module']}: {$row['schema_version']} -> $codeVer\n";
        $fixed++;
    }
}
echo "Done. Inserted $inserted, fixed $fixed modules.\n";
