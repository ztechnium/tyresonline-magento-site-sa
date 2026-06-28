#!/usr/bin/env php
<?php
declare(strict_types=1);
/** Quick add-to-cart HTTP test */
$base = 'https://stg.tyresonline.sa/en';
$cookie = tempnam(sys_get_temp_dir(), 'cart');

// Pick first salable product from car-tyres
require '/var/www/magento/app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$pdo = $om->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$row = $pdo->fetchRow("SELECT cpe.entity_id, cpe.sku, ur.request_path
  FROM catalog_product_entity cpe
  JOIN catalog_category_product cp ON cp.product_id=cpe.entity_id AND cp.category_id=1945
  JOIN cataloginventory_stock_status st ON st.product_id=cpe.entity_id AND st.stock_status=1
  JOIN url_rewrite ur ON ur.entity_id=cpe.entity_id AND ur.entity_type='product' AND ur.store_id=1 AND ur.redirect_type=0
  LIMIT 1");
$pid = $row['entity_id'];
$pdp = "$base/{$row['request_path']}";
echo "SKU={$row['sku']} PID=$pid\nPDP=$pdp\n";

$ch = curl_init($pdp);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEJAR=>$cookie, CURLOPT_COOKIEFILE=>$cookie, CURLOPT_FOLLOWLOCATION=>true]);
$html = curl_exec($ch);
curl_close($ch);
preg_match('/name="form_key" type="hidden" value="([^"]+)"/', $html, $m);
$formKey = $m[1] ?? '';
echo "form_key=" . substr($formKey, 0, 16) . "...\n";

$post = http_build_query(['product'=>$pid, 'qty'=>1, 'form_key'=>$formKey]);
$ch = curl_init("$base/checkout/cart/add/");
curl_setopt_array($ch, [
    CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$post,
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEJAR=>$cookie, CURLOPT_COOKIEFILE=>$cookie,
    CURLOPT_FOLLOWLOCATION=>false, CURLOPT_HEADER=>true,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "ADD HTTP=$code\n";

$ch = curl_init("$base/checkout/cart/");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEFILE=>$cookie]);
$cart = curl_exec($ch);
curl_close($ch);
if (stripos($cart, 'cart-empty') !== false || stripos($cart, 'You have no items') !== false) {
    echo "RESULT: CART EMPTY\n";
    exit(1);
}
echo "RESULT: CART HAS ITEMS\n";
preg_match('/product-item-name[^>]*>([^<]+)/', $cart, $nm);
if ($nm) echo "Item: " . trim($nm[1]) . "\n";
exit(0);
