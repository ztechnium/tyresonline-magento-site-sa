<?php
/**
 * Fix About Us page: KSA copy from Google Doc, remove TYRESONLINE.AE duplicate, fix broken image.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/fix-about-us-ksa.php
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

$pageId = (int)$conn->fetchOne(
    "SELECT page_id FROM cms_page WHERE identifier = 'about-us' ORDER BY page_id DESC LIMIT 1"
);
if (!$pageId) {
    echo "about-us page not found\n";
    exit(1);
}

$content = (string)$conn->fetchOne('SELECT content FROM cms_page WHERE page_id = ?', [$pageId]);
echo "Before: page_id={$pageId} len=" . strlen($content) . "\n";

$aboutSection = <<<'HTML'
<div class="about-us-content">
<h2>من هي TyresOnline؟</h2>
<p>نفخر TyresOnline بفريقها الذي يضم خبراء شغوفين متخصصين في الكفرات والإطارات داخل المملكة العربية السعودية، ويعملون بيد واحدة لتلبية كل طلباتك، هذا الفريق يصب كل تركيزه علي تقديم أعلى مستوى لخدمتكم، باعتماد أسلوب التواصل الودي وتوفير الخدمة التي تلبي احتياجاتك أيا كانت. ضع كامل ثقتك بفريق TyresOnline، ولا تتردد في طلب الحصول على أي خدمة أو توصية تحتاج إليها. فريق من الخبراء الرواد في مجال عملهم بتصرفك، فلا تفوت فرصة الاستفادة من طاقاتهم.</p>
<p>التزامنا الثابت هو تبسيط وتسهيل عملية شراء وتركيب الكفرات والإطارات لكل عميل، بدءًا من انتقاء المنتج، وصولاً إلى تسليم الكفرات في الموعد المحدد، وفي مختلف مناطق المملكة العربية السعودية. خدماتنا الفريدة وجودة كفراتنا وأسعارها التنافسية تأتي في طليعة أولوياتنا، حرصًا منا ليس على تلبية طلباتك فحسب، بل على تجاوز توقعاتك، وهذا ما نوفره لك على الدوام، من خلال الجمع بين التقنيات الحديثة وتعاملك المباشر مع خبراء حقيقيين، ما يمنحك تجربة لا مثيل لها.</p>
</div>
HTML;

// Remove duplicate UAE heading block and everything after first about-us-content until next structural section
$content = preg_replace('/<h1[^>]*>\s*حول\s+TYRESONLINE\.AE\s*<\/h1>.*?$/is', '', $content);
$content = preg_replace('/<h2[^>]*>\s*حول\s+TYRESONLINE\.AE\s*<\/h2>.*?$/is', '', $content);
$content = preg_replace('/<h1[^>]*>\s*حول\s+TyresOnline\.ae\s*<\/h1>.*?$/is', '', $content);
$content = preg_replace('/TYRESONLINE\.AE/i', 'TyresOnline.sa', $content);
$content = preg_replace('/TyresOnline\.ae/i', 'TyresOnline.sa', $content);

// Fix broken about image paths (UAE -> KSA media if present)
$content = str_replace(
    [
        'about-tyres-online-uae',
        'about_tyres_online_uae',
        'About-Tyres-Online-UAE',
        'tyres-online-uae',
        'TYRESONLINE.AE',
        'TyresOnline.ae',
    ],
    [
        'about-tyres-online-ksa',
        'about_tyres_online_ksa',
        'About-Tyres-Online-KSA',
        'tyres-online-ksa',
        'TyresOnline.sa',
        'TyresOnline.sa',
    ],
    $content
);

// Replace or insert the about-us-content section once
if (preg_match('/<div class="about-us-content">.*?<\/div>/s', $content)) {
    $content = preg_replace('/<div class="about-us-content">.*?<\/div>/s', $aboutSection, $content, 1);
} elseif (preg_match('/<h2[^>]*>\s*من هي TyresOnline\s*\??\s*<\/h2>/u', $content)) {
    $content = preg_replace(
        '/<h2[^>]*>\s*من هي TyresOnline\s*\??\s*<\/h2>.*?(<div|<h[12]|$)/su',
        $aboutSection . "\n$1",
        $content,
        1
    );
} else {
    $content = $aboutSection . "\n" . $content;
}

// Remove duplicate paragraphs if KSA copy appears twice
$marker = 'نفخر TyresOnline بفريقها';
$first = strpos($content, $marker);
if ($first !== false) {
    $second = strpos($content, $marker, $first + 10);
    if ($second !== false) {
        $content = substr($content, 0, $second);
    }
}

$conn->update('cms_page', ['content' => $content], ['page_id = ?' => $pageId]);

// Meta title/description if still UAE
foreach (['meta_title', 'meta_description', 'title', 'content_heading'] as $col) {
    $cols = $conn->describeTable('cms_page');
    if (!isset($cols[$col])) {
        continue;
    }
    $val = $conn->fetchOne("SELECT {$col} FROM cms_page WHERE page_id = ?", [$pageId]);
    if ($val && preg_match('/\.ae|UAE|الإمارات|TYRESONLINE\.AE/i', $val)) {
        $new = preg_replace('/TyresOnline\.ae|TYRESONLINE\.AE|UAE/i', 'TyresOnline.sa', $val);
        $new = str_replace(['الإمارات', 'الإمارات العربية المتحدة'], 'المملكة العربية السعودية', $new);
        $conn->update('cms_page', [$col => $new], ['page_id = ?' => $pageId]);
        echo "Updated {$col}\n";
    }
}

echo "After: len=" . strlen($content) . "\n";
echo "Done page_id={$pageId}\n";
