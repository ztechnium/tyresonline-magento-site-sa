#!/bin/bash
# Redeploy frontend static assets for tyresonline theme (fixes missing Magento_Checkout JS).
set -euo pipefail
MAGENTO=/var/www/magento
THEME='Hditsol/tyresonline'
LOCALES=(en_US ar_SA)

cd "$MAGENTO"

echo "=== Before: Magento_Checkout file count ==="
for locale in "${LOCALES[@]}"; do
    count=$(find "pub/static/frontend/$THEME/$locale/Magento_Checkout" -type f 2>/dev/null | wc -l)
    echo "$locale Magento_Checkout files=$count"
done

echo "=== Remove empty/broken Magento_Checkout static dirs ==="
for locale in "${LOCALES[@]}"; do
    sudo rm -rf "pub/static/frontend/$THEME/$locale/Magento_Checkout"
done
sudo rm -rf var/view_preprocessed/pub/static/frontend/"$THEME"

echo "=== Deploy static content (theme $THEME) ==="
sudo -u www-data php bin/magento setup:static-content:deploy \
    "${LOCALES[@]}" \
    -f \
    --area frontend \
    --theme "$THEME" \
    --jobs 4 2>&1 | tail -20

echo "=== After deploy ==="
for locale in "${LOCALES[@]}"; do
    count=$(find "pub/static/frontend/$THEME/$locale/Magento_Checkout" -type f 2>/dev/null | wc -l)
    echo "$locale Magento_Checkout files=$count"
    if [ -f "pub/static/frontend/$THEME/$locale/Magento_Checkout/js/view/shipping.min.js" ]; then
        echo "  shipping.min.js OK"
    elif [ -f "pub/static/frontend/$THEME/$locale/Magento_Checkout/js/view/shipping.js" ]; then
        echo "  shipping.js OK"
    else
        echo "  shipping MISSING"
    fi
done

echo "=== Flush caches ==="
sudo -u www-data php bin/magento cache:flush block_html full_page layout
sudo systemctl restart apache2 2>/dev/null || true

echo "Done."
