#!/usr/bin/env php
<?php
/**
 * Apply Phase A KSA Arabic copy from Google Docs to CMS + config.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/apply-phase-a-ksa-copy.php
 */
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$conn = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$storeManager = $om->get(\Magento\Store\Model\StoreManagerInterface::class);
$arStoreId = (int)$storeManager->getStore('ar')->getId();

function dbReplace($conn, string $table, string $idCol, int $id, array $pairs): int {
    $content = $conn->fetchOne("SELECT content FROM {$table} WHERE {$idCol} = ?", [$id]);
    if ($content === false || $content === null) {
        echo "  SKIP missing {$table} id={$id}\n";
        return 0;
    }
    $orig = $content;
    foreach ($pairs as $from => $to) {
        if ($from === '' || $from === $to) {
            continue;
        }
        $content = str_replace($from, $to, $content);
    }
    if ($content === $orig) {
        echo "  no change {$table} id={$id}\n";
        return 0;
    }
    $conn->update($table, ['content' => $content], ["{$idCol} = ?" => $id]);
    echo "  UPDATED {$table} id={$id}\n";
    return 1;
}

function replaceInColumn($conn, string $table, string $idCol, int $id, string $col, array $pairs): int {
    $val = $conn->fetchOne("SELECT {$col} FROM {$table} WHERE {$idCol} = ?", [$id]);
    if ($val === false || $val === null) {
        echo "  SKIP missing {$table} id={$id} col={$col}\n";
        return 0;
    }
    $orig = $val;
    foreach ($pairs as $from => $to) {
        if ($from === '' || $from === $to) {
            continue;
        }
        $val = str_replace($from, $to, $val);
    }
    if ($val === $orig) {
        echo "  no change {$table} id={$id} col={$col}\n";
        return 0;
    }
    $conn->update($table, [$col => $val], ["{$idCol} = ?" => $id]);
    echo "  UPDATED {$table} id={$id} col={$col}\n";
    return 1;
}

$updates = 0;

echo "=== About Us (page 20) ===\n";
$aboutNew = <<<'HTML'
<div class="about-us-content">
<h2>من هي TyresOnline؟</h2>
<p>تفخر TyresOnline بفريقها الذي يضم خبراء شغوفين متخصصين في الكفرات والإطارات داخل المملكة العربية السعودية، ويعملون بيدٍ واحدة لتلبية كل طلباتك. هذا الفريق يصبّ كلّ تركيزه على تقديم أعلى مستوى لخدمتكم، باعتماد أسلوب التواصل الودّي وتوفير الخدمة التي تلبّي احتياجاتك أيًا كانت. ضع كامل ثقتك بفريق TyresOnline، ولا تتردّد في طلب الحصول على أيّ خدمة أو توصية تحتاج إليها. فريقٌ من الخبراء الروّاد في مجال عملهم بتصرّفك، فلا تفوّت فرصة الاستفادة من طاقاتهم.</p>
<p>التزامنا الثابت هو تبسيط وتسهيل عمليّة شراء وتركيب الكفرات والإطارات لكلّ عميل، بدءًا من انتقاء المنتج، وصولاً إلى تسليم الكفرات في الموعد المحدّد، وفي مختلف مناطق المملكة العربية السعودية. خدماتنا الفريدة وجودة كفراتنا وأسعارها التنافسيّة تأتي في طليعة أولوياتنا، حرصًا منّا ليس على تلبية طلباتك فحسب، بل على تجاوز توقّعاتك، وهذا ما نوفّره لك على الدوام، من خلال الجمع بين التقنيّات الحديثة وتعاملك المباشر مع خبراء حقيقيّين، ما يمنحك تجربةً لا مثيل لها.</p>
</div>
HTML;

$page20 = $conn->fetchOne("SELECT page_id FROM cms_page WHERE page_id = 20 OR (identifier = 'about-us' AND title LIKE '%Arabic%') LIMIT 1");
if (!$page20) {
    $page20 = $conn->fetchOne("SELECT page_id FROM cms_page WHERE identifier = 'about-us' ORDER BY page_id DESC LIMIT 1");
}
if ($page20) {
    // Replace main about section if marker exists, else prepend/replace common UAE block
    $content = $conn->fetchOne("SELECT content FROM cms_page WHERE page_id = ?", [$page20]);
    if (preg_match('/<div class="about-us-content">.*?<\/div>/s', $content)) {
        $content = preg_replace('/<div class="about-us-content">.*?<\/div>/s', $aboutNew, $content, 1);
    } elseif (strpos($content, 'من هي TyresOnline') !== false) {
        $content = preg_replace('/<h2[^>]*>\s*من هي TyresOnline.*?<\/div>\s*<\/div>/s', $aboutNew, $content, 1);
    } else {
        // fallback: replace UAE paragraph cluster
        $content = preg_replace(
            '/<p>.*?الإمارات.*?<\/p>\s*<p>.*?<\/p>/s',
            $aboutNew,
            $content,
            1
        );
        if (strpos($content, 'من هي TyresOnline؟') === false) {
            $content = $aboutNew . "\n" . $content;
        }
    }
    $conn->update('cms_page', ['content' => $content], ['page_id = ?' => $page20]);
    echo "  UPDATED about-us page_id={$page20}\n";
    $updates++;
}

echo "=== Tyre Brands page ===\n";
$brandsNew = <<<'HTML'
<div class="brands-intro">
<h2>كفرات من علامات تجارية نثق بها</h2>
<p>كفرات عالية الجودة من أجلك أينما كنت في المملكة العربية السعودية</p>
<p>موقع TyresOnline هو وجهتك الأولى والمثالية لشراء الكفرات في المملكة العربية السعودية. يضمن لك موقعنا الحصول على أفضل نوع بأسعارٍ تنافسية لن تجد لها مثيل، ونوفر لك تشكيلة واسعة من أشهر الماركات العالمية في صناعة الكفرات، وكلها من خلال موقعنا الإلكتروني.</p>
<p>لتسهيل عملية البحث عن الكفرات المناسبة لسيارتك، كل ما عليك أن تبدأ باختيار الكفر الذي تريده من الماركة التي تثق فيها، وإن لم تكن متاحة على موقعنا، تواصل معنا لتبلغنا بنوعه، وفريقنا سيبذل أقصى مجهود لتأمين طلبك.</p>
</div>
HTML;

$brandPageId = $conn->fetchOne("SELECT page_id FROM cms_page WHERE identifier = 'all-tyre-brands' ORDER BY page_id DESC LIMIT 1");
if ($brandPageId) {
    $content = $conn->fetchOne("SELECT content FROM cms_page WHERE page_id = ?", [$brandPageId]);
    if (preg_match('/<div class="brands-intro">.*?<\/div>/s', $content)) {
        $content = preg_replace('/<div class="brands-intro">.*?<\/div>/s', $brandsNew, $content, 1);
    } else {
        // Replace first intro text block containing UAE/KSA tyre brands copy
        $patterns = [
            '/<div class="page-heading[^"]*">.*?<\/div>\s*<div class="[^"]*">.*?الإمارات.*?<\/div>/s',
            '/<p>.*?الإمارات.*?<\/p>\s*<p>.*?TyresOnline.*?<\/p>\s*<p>.*?<\/p>/s',
        ];
        $replaced = false;
        foreach ($patterns as $pat) {
            if (preg_match($pat, $content)) {
                $content = preg_replace($pat, $brandsNew, $content, 1);
                $replaced = true;
                break;
            }
        }
        if (!$replaced && strpos($content, 'كفرات من علامات') === false) {
            $content = $brandsNew . $content;
        }
    }
    $conn->update('cms_page', ['content' => $content], ['page_id = ?' => $brandPageId]);
    echo "  UPDATED all-tyre-brands page_id={$brandPageId}\n";
    $updates++;
}

echo "=== Fitting locations (storelocator CMS + config) ===\n";
$fittingPairs = [
    'Tyre Shop Near Me - Find Your Location' => 'متجر كفرات بالقرب مني - ابحث عن موقعك',
    'Find Your Location' => 'ابحث عن موقعك',
    'Find your location' => 'ابحث عن موقعك',
    'Near Me' => 'بالقرب مني',
    'All Over UAE' => 'في جميع أنحاء السعودية',
    'ALL OVER THE UAE' => 'في جميع أنحاء السعودية',
    'في جميع أنحاء الإمارات' => 'في جميع أنحاء السعودية',
    'ALL OVER THE KSA' => 'في جميع أنحاء السعودية',
    'مواقع مناسبة لتركيب الإطارات مع TYRESONLINE' => 'مواقع مناسبة لتركيب الكفرات مع TYRESONLINE',
    'مواقع مناسبة لتركيب الإطارات معTYRESONLINE' => 'مواقع مناسبة لتركيب الكفرات مع TYRESONLINE',
    'في TyresOnline، نحن نعرف أن الراحة هي المفتاح عند تركيب إطارات جديدة لسيارتك.' => 'في تايرز اونلاين، نحن نعرف أن السهولة هي أهم عنصر عند تركيب كفرات جديدة لسيارتك.',
    'في TyresOnline، نحن نعرف أن الراحة هي المفتاح عند تركيب كفرات جديدة لسيارتك.' => 'في تايرز اونلاين، نحن نعرف أن السهولة هي أهم عنصر عند تركيب كفرات جديدة لسيارتك.',
    'لهذا، لقد تشاركنا مع أفضل مراكز التركيب في جميع أنحاء الإمارات' => 'لهذا، تعاونا مع أكبر مراكز تغيير الكفرات في المملكة العربية السعودية',
    'لهذا، تعاونا مع أكبر مراكز تغيير الكفرات في الإمارات' => 'لهذا، تعاونا مع أكبر مراكز تغيير الكفرات في المملكة العربية السعودية',
];

foreach ($conn->fetchAll("SELECT page_id FROM cms_page WHERE identifier IN ('storelocator','fitting-locations')") as $row) {
    $updates += dbReplace($conn, 'cms_page', 'page_id', (int)$row['page_id'], $fittingPairs);
}

$storelocBlock = $conn->fetchOne("SELECT block_id FROM cms_block WHERE identifier LIKE '%storelocator%' OR identifier LIKE '%fitting%' LIMIT 1");
if ($storelocBlock) {
    $updates += dbReplace($conn, 'cms_block', 'block_id', (int)$storelocBlock, $fittingPairs);
}

$fittingIntro = '<p>في تايرز اونلاين، نحن نعرف أن السهولة هي أهم عنصر عند تركيب كفرات جديدة لسيارتك. لهذا، تعاونا مع أكبر مراكز تغيير الكفرات في المملكة العربية السعودية، ولضمان وجود مركز قريب لخدمتك أيًا كان مكانك أو احتياجك.</p>'
    . '<p>يمكنك من خلال هذه الصفحة الاطلاع على جميع مراكز تغيير الكفرات القريبة منك، وتختار المناسب لك منها. مهتمنا هي راحتك، وراحتك هي الرجوع إلى الطريق في أقصر فترة بعد وصولك إلى مراكز شركائنا.</p>';

// storelocator module text settings
foreach ($conn->fetchAll("SELECT config_id, path, value FROM core_config_data WHERE path LIKE 'ecomteck_storelocator/%' AND scope IN ('default','stores')") as $cfg) {
    $val = $cfg['value'];
    $new = $val;
    foreach ($fittingPairs as $from => $to) {
        $new = str_replace($from, $to, $new);
    }
    if ($new !== $val) {
        $conn->update('core_config_data', ['value' => $new], ['config_id = ?' => $cfg['config_id']]);
        echo "  UPDATED config {$cfg['path']}\n";
        $updates++;
    }
}

echo "=== Homepage blocks & meta ===\n";
$homePairs = [
    'أشتري إطارات أونلاين' => 'أشتري كفرات أونلاين',
    'Buy Tyres Online' => 'أشتري كفرات أونلاين',
    'تركيب الإطارات في جميع أنحاء الإمارات' => 'تركيب الكفرات في جميع أنحاء السعودية',
    'تركيب الإطارات في أي مكان بالإمارات' => 'تركيب الكفرات في جميع أنحاء السعودية',
    'ماركات إطارات معتمدة +50' => 'ماركات كفرات معتمدة +50',
    '50+ Approved Tyre Brands' => 'ماركات كفرات معتمدة +50',
    'العروض الحالية' => 'العروض الحالية',
    'Current Offers' => 'العروض الحالية',
    'WE HAVE STRATEGICALLY PARTNERED UP WITH TYRE SPECIALISTS ACROSS THE 7 EMIRATES' => 'تضم شبكة شركائنا أسماء كبيرة في عالم الكفرات في جميع أنحاء المملكة العربية السعودية',
    'أبرمنا تعاوناً استراتيجياً مع أسماءٍ مرموقة في عالم الإطارات في جميع أنحاء الإمارات السبع' => 'تضم شبكة شركائنا أسماء كبيرة في عالم الكفرات في جميع أنحاء المملكة العربية السعودية. ولذلك نحن نضمن لك أسرع استبدال لكفرات سيارتك أيًا كان مكانك، وبأفضل خدمة عملاء قد تتخيلها على الإطلاق.',
    'Get your tyres replaced with the best prices of high-end tyre brands from all over the world, Don\'t wait any longer!' => 'استبدل كفرات سيارتك بأفضل الأسعار في المملكة العربية السعودية، ومن أفضل الأسماء في عالم صناعة الكفرات من جميع أنحاء العالم. لا تنتظر، اتجه إلى مراكز خدماتنا في أي مكان!',
    'Get your tyres replaced with the best prices of high-end tyre brands from all over the world, Don\'t wait any longer!' => 'استبدل كفرات سيارتك بأفضل الأسعار في المملكة العربية السعودية، ومن أفضل الأسماء في عالم صناعة الكفرات من جميع أنحاء العالم. لا تنتظر، اتجه إلى مراكز خدماتنا في أي مكان!',
    'مراكز تركيب الإطارات' => 'مراكز تركيب الكفرات',
    'Fitting Locations' => 'مراكز تركيب الكفرات',
    'Tyre Shop Near Me' => 'متجر كفرات بالقرب مني',
    'We are it! We\'re always online, search and find the tyres you need from the comfort of wherever your are in the KSA.' => 'نحن بجانبك أينما كنت. أبحث وأختر الكفرات التي تحتاجها بمنتهى السهولة أينما كنت داخل المملكة العربية السعودية.',
    'نحن هنا! نحن دائما اونلاين!' => 'نحن بجانبك أينما كنت.',
    'كفرات السعودية بجميع المدن الكبرى بالمملكة' => 'كفرات السعودية بجميع المدن الكبرى بالمملكة',
    'IN ALL MAJOR CITIES' => 'المدن الكبرى بالمملكة',
    'ALL OVER THE KSA' => 'جميع أنحاء المملكة العربية السعودية',
    'Tyres in UAE' => 'كفرات السعودية',
    'New Tyres in UAE' => 'كفرات جديدة في السعودية',
    'New+Tyres+in+UAE' => 'New+Tyres+in+KSA',
    'Anywhere in the UAE' => 'في جميع أنحاء السعودية',
    'anywhere_in_the_uae' => 'anywhere_in_ksa',
    'في كافة أنحاء الإمارات' => 'في جميع أنحاء السعودية',
    'إطارات في الإمارات' => 'كفرات في السعودية',
    'وفي كل الإمارات السبع' => 'في جميع أنحاء السعودية',
    'أهلاً بك في تايرز أونلاين الإمارات' => 'أهلاً بك في تايرز أونلاين السعودية',
    'TyresOnline.ae' => 'TyresOnline.sa',
    'tyresonline.ae' => 'tyresonline.sa',
    'الإمارات العربية المتحدة' => 'المملكة العربية السعودية',
    'في الإمارات' => 'في السعودية',
    'في أي مكان في الإمارات' => 'في أي مكان في السعودية',
    'من أي مكان في الإمارات' => 'من أي مكان في السعودية',
    'الإمارات السبع' => 'السعودية',
    'United Arab Emirates' => 'Saudi Arabia',
    'Tyres Online UAE' => 'Tyres Online KSA',
    'TyresOnline.ae, All Rights Reserved.' => 'TyresOnline.sa, All Rights Reserved.',
    '©جميع الحقوق محفوظة لشركة TyresOnline.ae' => '© جميع الحقوق محفوظة لشركة TyresOnline.sa',
    'متجر إطارات أونلاين، من أي مكان في الإمارات' => 'متجر كفرات أونلاين، من أي مكان في السعودية',
    'اشترِ إطارات جديدة أونلاين من أي مكان في الإمارات' => 'اشترِ كفرات جديدة أونلاين من أي مكان في السعودية',
    'Buy Tyres Online From Anywhere In UAE' => 'Buy Tyres Online From Anywhere In KSA',
    'Shop At TyresOnline.ae' => 'Shop At TyresOnline.sa',
    'How to Find my Tyre size ?' => 'How to Find my Tyre size ?',
];

$homePageIds = $conn->fetchCol("SELECT page_id FROM cms_page WHERE identifier = 'home'");
foreach ($homePageIds as $pid) {
    $updates += replaceInColumn($conn, 'cms_page', 'page_id', (int)$pid, 'content', $homePairs);
    $updates += replaceInColumn($conn, 'cms_page', 'page_id', (int)$pid, 'meta_title', $homePairs);
    $updates += replaceInColumn($conn, 'cms_page', 'page_id', (int)$pid, 'meta_description', $homePairs);
    $updates += replaceInColumn($conn, 'cms_page', 'page_id', (int)$pid, 'title', $homePairs);
}

$blockIds = $conn->fetchCol(
    "SELECT block_id FROM cms_block WHERE identifier IN (
        'anywhere_in_the_uae','New+Tyres+in+UAE','home_main_banner_tyres','special_offers_slider_home',
        'special_offers_slider_home_mobile','home_brands'
    ) OR title LIKE '%Arabic%' OR title LIKE '%RTL%' OR identifier LIKE '%uae%'"
);
foreach ($blockIds as $bid) {
    $updates += dbReplace($conn, 'cms_block', 'block_id', (int)$bid, $homePairs);
}

echo "=== Store config (Arabic store) ===\n";
$configPairs = [
    'Tyres Online UAE' => 'Tyres Online KSA',
    'Tyresonline.ae, All Rights Reserved.' => 'TyresOnline.sa, All Rights Reserved.',
    'TyresOnline.ae' => 'TyresOnline.sa',
];
foreach ($configPairs as $from => $to) {
    $rows = $conn->fetchAll(
        "SELECT config_id, value FROM core_config_data WHERE value LIKE ? AND scope = 'stores' AND scope_id = ?",
        ['%' . $from . '%', $arStoreId]
    );
    foreach ($rows as $row) {
        $newVal = str_replace($from, $to, $row['value']);
        if ($newVal !== $row['value']) {
            $conn->update('core_config_data', ['value' => $newVal], ['config_id = ?' => $row['config_id']]);
            echo "  UPDATED store config id={$row['config_id']}\n";
            $updates++;
        }
    }
}

echo "=== ar_SA.csv translation file ===\n";
$csvPath = BP . '/app/design/frontend/Hditsol/tyresonline-ar/i18n/ar_SA.csv';
if (is_readable($csvPath)) {
    $csv = file_get_contents($csvPath);
    $csvPairs = [
        '"United Arab Emirates","الإمارات العربية المتحدة"' => '"United Arab Emirates","المملكة العربية السعودية"',
        '"Saudi Arabia","المملكة العربية السعودية"' => '"Saudi Arabia","المملكة العربية السعودية"',
        'أبحث عن إطارات جديدة' => 'أبحث عن كفرات جديدة',
        'تركيب الإطارات الجديدة' => 'تركيب الكفرات الجديدة',
        'شراء إطارات' => 'شراء كفرات',
        'TyresOnline.ae' => 'TyresOnline.sa',
        'Tyres Online UAE' => 'Tyres Online KSA',
        'ALL OVER THE UAE' => 'في جميع أنحاء السعودية',
        'ALL OVER THE KSA' => 'جميع أنحاء المملكة العربية السعودية',
        'IN ALL MAJOR CITIES' => 'المدن الكبرى بالمملكة',
    ];
    $newCsv = $csv;
    foreach ($csvPairs as $from => $to) {
        $newCsv = str_replace($from, $to, $newCsv);
    }
    // Doc yellow search strings
    $extraTranslations = [
        ['Looking for new tyres', 'أبحث عن كفرات جديدة'],
        ['Fitting new tyres', 'تركيب الكفرات الجديدة'],
        ['Buy tyres', 'شراء كفرات'],
        ['Tyre Shop Near Me', 'متجر كفرات بالقرب مني'],
        ['Find Your Location', 'ابحث عن موقعك'],
        ['Near Me', 'بالقرب مني'],
    ];
    foreach ($extraTranslations as [$en, $ar]) {
        $line = '"' . str_replace('"', '""', $en) . '","' . str_replace('"', '""', $ar) . '"';
        if (strpos($newCsv, $line) === false && stripos($newCsv, '"' . $en . '"') === false) {
            $newCsv .= "\n" . $line;
        }
    }
    if ($newCsv !== $csv) {
        file_put_contents($csvPath, $newCsv);
        echo "  UPDATED ar_SA.csv\n";
        $updates++;
    } else {
        echo "  no change ar_SA.csv\n";
    }
}

echo "=== Global KSA pass on targeted CMS rows ===\n";
$globalPairs = [
    'الإمارات العربية المتحدة' => 'المملكة العربية السعودية',
    'في الإمارات' => 'في السعودية',
    'من أي مكان في الإمارات' => 'من أي مكان في السعودية',
    'الإمارات السبع' => 'السعودية',
    'TyresOnline.ae' => 'TyresOnline.sa',
    'tyresonline.ae' => 'tyresonline.sa',
    'Tyres Online UAE' => 'Tyres Online KSA',
    'ALL OVER THE UAE' => 'في جميع أنحاء السعودية',
    'All Over UAE' => 'في جميع أنحاء السعودية',
    'Anywhere in the UAE' => 'في جميع أنحاء السعودية',
    'New Tyres in UAE' => 'كفرات جديدة في السعودية',
    'Buy Tyres Online From Anywhere In UAE' => 'Buy Tyres Online From Anywhere In KSA',
    'Shop At TyresOnline.ae' => 'Shop At TyresOnline.sa',
    'متجر إطارات أونلاين، من أي مكان في الإمارات' => 'متجر كفرات أونلاين، من أي مكان في السعودية',
    'اشترِ إطارات جديدة أونلاين من أي مكان في الإمارات' => 'اشترِ كفرات جديدة أونلاين من أي مكان في السعودية',
    'أشتري إطارات أونلاين' => 'أشتري كفرات أونلاين',
    'تركيب الإطارات في أي مكان بالإمارات' => 'تركيب الكفرات في جميع أنحاء السعودية',
    'تركيب الإطارات في جميع أنحاء الإمارات' => 'تركيب الكفرات في جميع أنحاء السعودية',
    'ماركات إطارات معتمدة +50' => 'ماركات كفرات معتمدة +50',
    'مراكز تركيب الإطارات' => 'مراكز تركيب الكفرات',
    'إطارات في الإمارات' => 'كفرات في السعودية',
    'وفي كل الإمارات السبع' => 'في جميع أنحاء السعودية',
    'أهلاً بك في تايرز أونلاين الإمارات' => 'أهلاً بك في تايرز أونلاين السعودية',
    '©جميع الحقوق محفوظة لشركة TyresOnline.ae' => '© جميع الحقوق محفوظة لشركة TyresOnline.sa',
    'على خصم 15% على أفضل علامات الإطارات في الإمارات!' => 'على خصم 15% على أفضل علامات الكفرات في السعودية!',
    'لأي مكان في الإمارات العربية المتحدة' => 'لأي مكان في المملكة العربية السعودية',
    'في كافة أنحاء الإمارات' => 'في جميع أنحاء السعودية',
    'Browse Other' => 'Browse Other',
];

$targetPageIds = $conn->fetchCol(
    "SELECT page_id FROM cms_page WHERE identifier IN ('home','about-us','all-tyre-brands','storelocator') OR title LIKE '%Arabic%'"
);
foreach ($targetPageIds as $pid) {
    foreach (['content', 'meta_title', 'meta_description', 'meta_keywords', 'title', 'content_heading'] as $col) {
        $updates += replaceInColumn($conn, 'cms_page', 'page_id', (int)$pid, $col, $globalPairs);
    }
}

$targetBlockIds = $conn->fetchCol(
    "SELECT block_id FROM cms_block WHERE identifier LIKE '%uae%' OR identifier LIKE '%home%' OR identifier LIKE '%brand%' OR title LIKE '%Arabic%' OR title LIKE '%RTL%'"
);
foreach ($targetBlockIds as $bid) {
    $updates += dbReplace($conn, 'cms_block', 'block_id', (int)$bid, $globalPairs);
}

echo "=== Flush caches ===\n";
$cacheManager = $om->get(\Magento\Framework\App\Cache\Manager::class);
$cacheManager->flush(array_keys($om->get(\Magento\Framework\App\Cache\TypeListInterface::class)->getTypes()));
echo "Done. Total update operations: {$updates}\n";
