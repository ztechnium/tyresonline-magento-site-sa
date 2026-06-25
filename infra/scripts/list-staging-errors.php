#!/usr/bin/env php
<?php
$reportDir = '/var/www/magento/var/report';
$files = glob($reportDir . '/*') ?: [];
usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));

echo "=== Recent error reports (today) ===\n";
$count = 0;
foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }
    $mtime = filemtime($file);
    if ($mtime < strtotime('today UTC')) {
        continue;
    }
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) {
        continue;
    }
    $count++;
    echo date('Y-m-d H:i:s', $mtime) . "\n";
    echo '  Report ID: ' . basename($file) . "\n";
    echo '  Error: ' . substr((string)($data[0] ?? 'unknown'), 0, 120) . "\n";
    echo '  URL: ' . ($data['url'] ?? 'n/a') . "\n\n";
    if ($count >= 15) {
        break;
    }
}

echo "=== exception.log (today, unique messages) ===\n";
$log = @file_get_contents('/var/www/magento/var/log/exception.log');
if ($log) {
    $seen = [];
    foreach (explode("\n", $log) as $line) {
        if (strpos($line, date('Y-m-d')) === false) {
            continue;
        }
        if (!preg_match('/main\.CRITICAL: ([^:]+): (.+?) in /', $line, $m)) {
            continue;
        }
        $key = $m[1] . ': ' . substr($m[2], 0, 100);
        if (isset($seen[$key])) {
            $seen[$key]++;
        } else {
            $seen[$key] = 1;
        }
    }
    arsort($seen);
    foreach ($seen as $msg => $n) {
        echo "  [$n x] $msg\n";
    }
}
