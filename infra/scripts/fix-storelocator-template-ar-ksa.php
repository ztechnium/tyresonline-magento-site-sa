#!/usr/bin/env php
<?php
/**
 * Fix UTF-8 mojibake in ecomteck_storelocator location_list_description template (Arabic store).
 * Corrupted strings look like: ╪º┘å╪╕╪▒ ╪╣┘ä┘ë ╪º┘ä╪«╪▒┘è╪╖╪⌐
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = $om->get(\Magento\Framework\App\ResourceConnection::class);
$conn = $resource->getConnection();

$configPath = 'ecomteck_storelocator/template/location_list_description';

function fixCp437Mojibake(string $text): string
{
    if (!preg_match('/[\x{2500}-\x{25FF}\x{256A}\x{2568}]/u', $text)) {
        return $text;
    }

    if (function_exists('iconv')) {
        $bytes = @iconv('UTF-8', 'CP437//IGNORE', $text);
        if ($bytes !== false) {
            $fixed = @iconv('CP437', 'UTF-8//IGNORE', $bytes);
            if ($fixed !== false && preg_match('/[\x{0600}-\x{06FF}]/u', $fixed)) {
                return $fixed;
            }
        }
    }

    // Fallback: known replacements from the broken template
    $map = [
        '╪º┘å╪╕╪▒ ╪╣┘ä┘ë ╪º┘ä╪«╪▒┘è╪╖╪⌐' => 'انظر على الخريطة',
        '╪¡╪»╪» ╪º┘ä┘à╪½╪¿╪¬' => 'حدد المثبت',
        '╪¬╪º╪▒┘è╪« ╪º┘ä╪Ñ╪╣╪»╪º╪»' => 'تاريخ الإعداد',
        '┘ê┘é╪¬ ╪º┘ä╪Ñ╪╣╪»╪º╪»' => 'وقت الإعداد',
        '╪¡╪»╪» ┘ç╪░╪º ╪º┘ä┘à╪½╪¿╪¬' => 'حدد هذا المثبت',
    ];

    return strtr($text, $map);
}

function fixTemplate(string $template): string
{
    $template = str_replace(
        'https://stg.tyresonline.ae/storepickup/index/selectstore',
        'https://stg.tyresonline.sa/storepickup/index/selectstore',
        $template
    );
    $template = str_replace(
        'https://stg.tyresonline.ae/static/',
        'https://stg.tyresonline.sa/static/',
        $template
    );

    if (!preg_match('/[\x{2500}-\x{25FF}]/u', $template)) {
        return $template;
    }

    return preg_replace_callback('/[\x{2500}-\x{25FF}\x{0600}-\x{06FF}\s]+/u', static function (array $m) {
        return fixCp437Mojibake($m[0]);
    }, $template);
}

$rows = $conn->fetchAll(
    "SELECT config_id, scope, scope_id, LEFT(value, 80) AS preview
     FROM core_config_data
     WHERE path = ?
     ORDER BY scope, scope_id",
    [$configPath]
);

if (!$rows) {
    echo "No config rows found for {$configPath}\n";
    exit(0);
}

echo "Found " . count($rows) . " template config row(s)\n";

foreach ($rows as $row) {
    $configId = (int)$row['config_id'];
    $value = (string)$conn->fetchOne(
        'SELECT value FROM core_config_data WHERE config_id = ?',
        [$configId]
    );

    $fixed = fixTemplate($value);
    if ($fixed === $value) {
        echo "config_id={$configId} scope={$row['scope']} scope_id={$row['scope_id']}: no changes needed\n";
        continue;
    }

    $conn->update(
        'core_config_data',
        ['value' => $fixed],
        ['config_id = ?' => $configId]
    );

    echo "config_id={$configId} scope={$row['scope']} scope_id={$row['scope_id']}: FIXED\n";

    if (preg_match('/<span>([^<]+)<\/span>/u', $fixed, $m)) {
        echo "  sample span: {$m[1]}\n";
    }
}

echo "Done. Run: bin/magento cache:flush\n";
