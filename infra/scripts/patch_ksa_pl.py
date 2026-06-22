from pathlib import Path

p = Path("/var/www/magento/app/code/Hdweb/Tyrefinder/Helper/Productlisting.php")
text = p.read_text()
marker = "if (!$sku || !$productDetails->getId())"
old = "        if ($proType != 'configurable' && $proType != 'bundle' && $proType != 'grouped') {"
ins = (
    "        if (!$sku || !$productDetails->getId()) {\n"
    "            return 0;\n"
    "        }\n\n"
    + old
)
if marker not in text:
    if old not in text:
        raise SystemExit("patch target not found")
    p.write_text(text.replace(old, ins, 1))
    print("Productlisting patched")
else:
    print("Productlisting already patched")

gal = Path(
    "/var/www/magento/app/design/frontend/Hditsol/tyresonline/"
    "Magento_Catalog/templates/product/view/gallery.phtml"
)
gtext = gal.read_text()
g_old = "$helper = $block->getData('imageHelper');"
g_new = """$helper = $block->getData('imageHelper');
if (!$helper) {
    $helper = \\Magento\\Framework\\App\\ObjectManager::getInstance()->get(\\Magento\\Catalog\\Helper\\Image::class);
}"""
if "ObjectManager::getInstance()->get(\\Magento\\Catalog\\Helper\\Image::class)" in gtext:
    print("gallery already patched")
elif g_old in gtext:
    gal.write_text(gtext.replace(g_old, g_new, 1))
    print("gallery patched")
else:
    print("gallery patch target not found")
