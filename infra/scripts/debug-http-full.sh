#!/bin/bash
cd /var/www/magento
cp app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml /tmp/list.phtml.bak3
python3 <<'PY'
from pathlib import Path
p = Path('/var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml')
text = p.read_text()
needle = "<?php if (\$loadedItemsCount === 0 || (\$isBundle && count(\$bundleCollection) == 0)) : ?>"
insert = "<?php echo '<!-- DEBUG size='.(int)$collectionSize.' loaded='.(int)$loadedItemsCount.' collclass='.get_class($_productCollection).' -->'; ?>\n" + needle
if 'DEBUG size=' not in text:
    text = text.replace(needle, insert, 1)
    p.write_text(text)
PY

bash /tmp/debug-http-bootstrap.sh

cp /tmp/list.phtml.bak3 app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml
