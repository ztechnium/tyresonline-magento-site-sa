#!/usr/bin/env php
<?php
/**
 * Validate active promotion banner SHOP NOW URLs.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/validate-promotions-links-ksa.php
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
try {
    $om->get(\Magento\Framework\App\State::class)->setAreaCode('frontend');
} catch (\Exception $e) {
}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$baseUrl = rtrim($storeManager->getStore()->getBaseUrl(), '/');

$rows = $conn->fetchAll(
    "SELECT banner_id, title, title_ar, url_banner, category
     FROM mageplaza_bannerslider_banner
     WHERE status = 1 AND category NOT LIKE '%battery%'
     ORDER BY banner_id"
);

echo "Base: {$baseUrl}\n\n";
$issues = 0;
foreach ($rows as $row) {
    $url = trim((string) ($row['url_banner'] ?? ''));
    $title = $row['title_ar'] ?: $row['title'];
    echo "#{$row['banner_id']} {$title}\n  url_banner: {$url}\n";

    if ($url === '') {
        echo "  WARN: empty url\n";
        $issues++;
        continue;
    }
    if (stripos($url, 'bicycle-tyres') !== false) {
        echo "  FAIL: bicycle link\n";
        $issues++;
        continue;
    }
    if (stripos($url, 'is_ajax') !== false || preg_match('/_=\d+/', $url)) {
        echo "  FAIL: ajax junk in url\n";
        $issues++;
        continue;
    }
    if (stripos($url, 'AED') !== false) {
        echo "  FAIL: UAE currency in url\n";
        $issues++;
        continue;
    }

    $full = $baseUrl . '/' . ltrim($url, '/');
    $ch = curl_init($full);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Host: stg.tyresonline.sa'],
    ]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 400) {
        echo "  OK: HTTP {$code}\n";
    } else {
        echo "  FAIL: HTTP {$code} ({$full})\n";
        $issues++;
    }
}

echo "\nDone. Issues: {$issues}\n";
exit($issues > 0 ? 1 : 0);
