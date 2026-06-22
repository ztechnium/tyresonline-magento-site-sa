#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

mysql $DB -e "
SELECT entity_id, value FROM catalog_category_entity_varchar
WHERE entity_id IN (1944,1945) AND attribute_id=(SELECT attribute_id FROM eav_attribute WHERE attribute_code='url_path' AND entity_type_id=3) AND store_id=0;

SHOW TABLES LIKE '%virtual%';
"

mysql $DB -e "SELECT * FROM smile_virtualcategory_catalog_category_product_position WHERE category_id=1945 LIMIT 5;" 2>/dev/null || true

cd /var/www/magento
# debug category id in template
cp app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml /tmp/list.phtml.bak2
sed -i 's/\$category = \$objectManager->get/\$category = \$objectManager->get/' app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
sed -i 's/\$category = \$objectManager->get(.*Registry.*)->registry('\''current_category'\'');/echo "<!-- CAT ".($category?\$category->getId():"none")." path=".($category?\$category->getUrlPath():"")." -->"; \$category = \$objectManager->get(\\Magento\\Framework\\Registry::class)->registry('\''current_category'\'');/' app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml 2>/dev/null || true

# simpler patch after category line
python3 <<'PY'
from pathlib import Path
p = Path('/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml')
text = p.read_text()
needle = "$category = $objectManager->get('Magento\\Framework\\Registry')->registry('current_category');"
insert = needle + "\necho '<!-- CAT '.($category ? $category->getId() : 'none').' path='.($category ? $category->getUrlPath() : '').' coll='.(int)$_productCollection->getSize().' -->';"
if 'CAT ' not in text:
    text = text.replace(needle, insert, 1)
    p.write_text(text)
PY

curl -s 'https://stg.tyresonline.sa/en/all-tyres/car-tyres.html?dbg2='$(date +%s) | grep -o '<!-- CAT[^>]*-->' | head -3
cp /tmp/list.phtml.bak2 app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
