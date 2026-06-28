#!/usr/bin/env bash
grep -n 'TyresOnline' /var/www/magento/app/design/frontend/Hditsol/tyresonline/Magento_Theme/templates/html/custom_head.phtml | head -5
echo "--- AR footer ---"
grep 'TyresOnline' /var/www/magento/app/design/frontend/Hditsol/tyresonline-ar/Magento_Theme/templates/html/footer.phtml
echo "--- localhost curl ---"
curl -s -H 'Host: stg.tyresonline.sa' http://127.0.0.1/ar/ | grep -c 'TyresOnline.ae' || true
curl -s -H 'Host: stg.tyresonline.sa' http://127.0.0.1/ar/ | grep 'og:site_name' | head -2
curl -s -H 'Host: stg.tyresonline.sa' http://127.0.0.1/ar/ | grep 'All Rights Reserved' | head -2
