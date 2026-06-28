<?php
/**
 * Rebuild About Us CMS pages (AR page 20, EN page 5) with centered KSA copy.
 * Run: cd /var/www/magento && sudo -u www-data php infra/scripts/rebuild-about-us-ksa.php
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

$heroDesktop = 'wysiwyg/about-us/about-tyresonline-ksa.jpg';
$heroMobile = 'wysiwyg/about-us/about-tyresonline-ksa-mobile.jpg';

function heroBlock(string $desktop, string $mobile, string $alt): string
{
    return <<<HTML
<div class="page-title">
<div class="image-wrap picture loader aspect-custom"><picture>
<source class="lazyload" srcset="{{media url='{$mobile}'}}" media="(max-width: 767px)">
<source class="lazyload" srcset="{{media url='{$desktop}'}}" media="(min-width: 768px)">
<img class="lazyload" src="{{media url='images/section/blank.png'}}" alt="{$alt}" data-src="{{media url='{$desktop}'}}">
</picture></div>
</div>
HTML;
}

$pages = [
    20 => [
        'heading' => 'من هي TyresOnline؟',
        'heading_tag' => 'h1',
        'title_class' => 'section-title text-center',
        'alt' => 'عن تايرز أونلاين السعودية',
        'paragraphs' => [
            'تفخر TyresOnline بفريقها الذي يضم خبراء شغوفين متخصصين في الكفرات والإطارات داخل المملكة العربية السعودية، ويعملون بيدٍ واحدة لتلبية كل طلباتك. هذا الفريق يصبّ كلّ تركيزه على تقديم أعلى مستوى لخدمتكم، باعتماد أسلوب التواصل الودّي وتوفير الخدمة التي تلبّي احتياجاتك أيًا كانت. ضع كامل ثقتك بفريق TyresOnline، ولا تتردّد في طلب الحصول على أيّ خدمة أو توصية تحتاج إليها. فريقٌ من الخبراء الروّاد في مجال عملهم بتصرّفك، فلا تفوّت فرصة الاستفادة من طاقاتهم.',
            'التزامنا الثابت هو تبسيط وتسهيل عمليّة شراء وتركيب الكفرات والإطارات لكلّ عميل، بدءًا من انتقاء المنتج، وصولاً إلى تسليم الكفرات في الموعد المحدّد، وفي مختلف مناطق المملكة العربية السعودية. خدماتنا الفريدة وجودة كفراتنا وأسعارها التنافسيّة تأتي في طليعة أولوياتنا، حرصًا منّا ليس على تلبية طلباتك فحسب، بل على تجاوز توقّعاتك، وهذا ما نوفّره لك على الدوام، من خلال الجمع بين التقنيّات الحديثة وتعاملك المباشر مع خبراء حقيقيّين، ما يمنحك تجربةً لا مثيل لها.',
        ],
        'meta' => [
            'meta_title' => 'عن موقعنا | TyresOnline.sa',
            'meta_description' => 'نحن فريق من خبراء الكفرات والإطارات الشغوفين في المملكة العربية السعودية، نعمل معًا لتلبية احتياجاتك. ثق بفريق TyresOnline.sa للحصول على أي نصائح وتوصيات!',
            'title' => 'About Us - Arabic',
            'content_heading' => '',
        ],
    ],
    5 => [
        'heading' => 'ABOUT TYRESONLINE.SA',
        'heading_tag' => 'h1',
        'title_class' => 'section-title text-center text-uppercase',
        'alt' => 'About TyresOnline Saudi Arabia',
        'paragraphs' => [
            'TyresOnline is proud of its team of passionate tyre experts across the Kingdom of Saudi Arabia, working together to fulfil every request. This team is fully focused on delivering the highest level of service through a friendly approach and personalised care that meets your needs, whatever they may be. Place your full trust in the TyresOnline team, and do not hesitate to ask for any service or recommendation you need. A team of leading experts in their field at your service — do not miss the opportunity to benefit from their expertise.',
            'Our steadfast commitment is to simplify and streamline the process of buying and fitting tyres for every customer, from product selection through to on-time delivery across the Kingdom of Saudi Arabia. Our unique services, tyre quality, and competitive prices are among our top priorities, as we strive not only to meet your expectations but to exceed them. We deliver this consistently by combining modern technology with direct access to real experts, giving you an unparalleled experience.',
        ],
        'meta' => [
            'meta_title' => 'About Us | TyresOnline.sa',
            'meta_description' => 'We are a team of passionate tyre experts in Saudi Arabia working together to fulfil your demands. Trust the TyresOnline team for any tips and recommendations!',
            'title' => 'About Us',
            'content_heading' => '',
        ],
    ],
];

foreach ($pages as $pageId => $cfg) {
    $exists = (int)$conn->fetchOne('SELECT COUNT(*) FROM cms_page WHERE page_id = ?', [$pageId]);
    if (!$exists) {
        echo "Skip missing page_id={$pageId}\n";
        continue;
    }

    $paras = '';
    foreach ($cfg['paragraphs'] as $p) {
        $paras .= "<p>{$p}</p>\n";
    }

    $tag = $cfg['heading_tag'];
    $content = heroBlock($heroDesktop, $heroMobile, $cfg['alt'])
        . <<<HTML
<div class="cms-page-section">
<div class="container custom-width-1170">
<div class="{$cfg['title_class']}">
<{$tag}>{$cfg['heading']}</{$tag}>
{$paras}
</div>
</div>
</div>
HTML;

    $beforeLen = (int)$conn->fetchOne('SELECT LENGTH(content) FROM cms_page WHERE page_id = ?', [$pageId]);
    $conn->update('cms_page', ['content' => $content], ['page_id = ?' => $pageId]);

    foreach ($cfg['meta'] as $col => $val) {
        $cols = $conn->describeTable('cms_page');
        if (!isset($cols[$col])) {
            continue;
        }
        $conn->update('cms_page', [$col => $val], ['page_id = ?' => $pageId]);
    }

    echo "Rebuilt page_id={$pageId} len {$beforeLen} -> " . strlen($content) . "\n";
}

echo "Done\n";
