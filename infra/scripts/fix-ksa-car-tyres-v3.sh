#!/bin/bash
set -e
MAGENTO=/var/www/magento
PL=$MAGENTO/app/code/Hdweb/Tyrefinder/Helper/Productlisting.php
cp -a "$PL" "${PL}.bak-$(date +%s)"

python3 - <<'PY'
from pathlib import Path
p = Path("/var/www/magento/app/code/Hdweb/Tyrefinder/Helper/Productlisting.php")
text = p.read_text()
old = """        $sku = $productDetails->getSku();
        $proType = $productDetails->getTypeId();
        $salebleqty = $this->_objectManager->get('Magento\\InventorySalesApi\\Api\\GetProductSalableQtyInterface');

        if ($proType != 'configurable' && $proType != 'bundle' && $proType != 'grouped') {
            $stockQty = $salebleqty->execute($sku, $stockId);"""
new = """        $sku = $productDetails->getSku();
        $proType = $productDetails->getTypeId();
        $salebleqty = $this->_objectManager->get('Magento\\InventorySalesApi\\Api\\GetProductSalableQtyInterface');

        if (!$sku || !$productDetails->getId()) {
            return 0;
        }

        if ($proType != 'configurable' && $proType != 'bundle' && $proType != 'grouped') {
            $stockQty = $salebleqty->execute($sku, $stockId);"""
if old not in text:
    if "if (!$sku || !$productDetails->getId())" in text:
        print("Productlisting.php already patched")
    else:
        raise SystemExit("Productlisting patch target not found")
else:
    p.write_text(text.replace(old, new, 1))
    print("Productlisting.php patched")
PY

GAL=$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml
cp -a "$GAL" "${GAL}.bak-$(date +%s)"
python3 - <<'PY2'
from pathlib import Path
p = Path("/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml")
text = p.read_text()
old = "$helper = $block->getData('imageHelper');"
new = """$helper = $block->getData('imageHelper');
if (!$helper) {
    $helper = \\Magento\\Framework\\App\\ObjectManager::getInstance()->get(\\Magento\\Catalog\\Helper\\Image::class);
}"""
if "ObjectManager::getInstance()->get(\\Magento\\Catalog\\Helper\\Image::class)" in text:
    print("gallery.phtml already patched")
elif old not in text:
    raise SystemExit("gallery patch target not found")
else:
    p.write_text(text.replace(old, new, 1))
    print("gallery.phtml patched")
PY2

sudo chown -R www-data:www-data "$MAGENTO/generated" "$MAGENTO/var" 2>/dev/null || true
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -3
sudo rm -rf var/page_cache/* var/view_preprocessed/*
sudo systemctl restart apache2
sleep 3

echo "=== KSA AFTER FIX ==="
curl -s "https://stg.tyresonline.sa/en/all-tyres/car-tyres.html" -o /tmp/ksa3.html
wc -c /tmp/ksa3.html
echo -n "SAR prices: "; grep -oE "SAR [0-9][0-9.,]+" /tmp/ksa3.html | wc -l
echo -n "modern-layout: "; grep -c "product-box modern-layout" /tmp/ksa3.html || true
echo -n "list-view-mobile: "; grep -c "list-view-mobile" /tmp/ksa3.html || true
echo -n "doctype count: "; grep -ci "doctype html" /tmp/ksa3.html
grep -ni "doctype html" /tmp/ksa3.html | head -3

echo "=== UAE BENCHMARK ==="
curl -s "https://stg.tyresonline.ae/en/all-tyres/car-tyres.html" -o /tmp/uae.html
wc -c /tmp/uae.html
echo -n "AED prices: "; grep -oE "AED [0-9][0-9.,]+" /tmp/uae.html | wc -l
echo -n "modern-layout: "; grep -c "product-box modern-layout" /tmp/uae.html || true
echo -n "list-view-mobile: "; grep -c "list-view-mobile" /tmp/uae.html || true

echo "=== LATEST EXCEPTION ==="
tail -3 "$MAGENTO/var/log/exception.log" || true
