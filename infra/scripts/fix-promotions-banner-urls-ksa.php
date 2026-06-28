#!/usr/bin/env php
<?php
/**
 * Audit and fix Mageplaza banner SHOP NOW URLs for KSA promotions page.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/fix-promotions-banner-urls-ksa.php
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
$table = 'mageplaza_bannerslider_banner';

function normalizeBannerUrl(string $url): string
{
    $url = trim($url);
    if ($url === '' || $url === '#') {
        return '';
    }

    // Strip accidental AJAX cache-buster params copied from PLP filters.
    $url = preg_replace('/([?&])_=\d+&?/', '$1', $url) ?? $url;
    $url = preg_replace('/([?&])is_ajax=1&?/', '$1', $url) ?? $url;
    $url = str_replace(['?&', '&&'], ['?', '&'], $url);
    $url = rtrim($url, '?&');

    $replacements = [
        'bicycle-tyres' => '',
        'mobile-tyre-fitting-service-in-ksa' => 'mobile-tyre-fitting-service-in-uae',
        'UP+TO+AED+700+OFF' => 'UP+TO+SAR+700+OFF',
        'UP TO AED 700 OFF' => 'UP TO SAR 700 OFF',
        'car-insurance-uae' => 'all-tyres/car-tyres.html',
    ];

    foreach ($replacements as $from => $to) {
        if ($to === '' && stripos($url, $from) !== false) {
            return '';
        }
        $url = str_replace($from, $to, $url);
    }

    return $url;
}

echo "=== Current active banners ===\n";
$rows = $conn->fetchAll(
    "SELECT banner_id, status, title, title_ar, category, url_banner
     FROM {$table}
     WHERE status = 1
     ORDER BY banner_id"
);

$updates = 0;
foreach ($rows as $row) {
    $id = (int) $row['banner_id'];
    $old = (string) ($row['url_banner'] ?? '');
    $new = normalizeBannerUrl($old);
    $title = $row['title_ar'] ?: $row['title'];
    echo "#{$id} [{$row['category']}] {$title}\n  old: {$old}\n";

    if ($new === '' && $old !== '') {
        // Disable broken/unwanted promos (e.g. bicycle CMS page, empty after cleanup).
        $conn->update($table, ['status' => 0, 'url_banner' => ''], ['banner_id = ?' => $id]);
        echo "  => DISABLED banner (removed broken link)\n";
        $updates++;
        continue;
    }

    if ($new !== $old) {
        $conn->update($table, ['url_banner' => $new], ['banner_id = ?' => $id]);
        echo "  new: {$new}\n";
        $updates++;
    } else {
        echo "  ok\n";
    }
}

echo "\n=== Summary: {$updates} banner(s) updated ===\n";

$om->get(\Magento\Framework\App\Cache\Manager::class)->flush(
    array_keys($om->get(\Magento\Framework\App\Cache\TypeListInterface::class)->getTypes())
);
echo "Cache flushed.\n";
