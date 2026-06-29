#!/bin/bash
# Restore corrupted Magento_Ui knockout template loader static files (wrong mage.loader was deployed).
set -euo pipefail
MAGENTO=/var/www/magento
SRC="$MAGENTO/vendor/magento/module-ui/view/base/web/js/lib/knockout/template/loader.js"
THEMES=(Hditsol/tyresonline)

if [ ! -f "$SRC" ]; then
    echo "Missing vendor source: $SRC"
    exit 1
fi

echo "=== Fix knockout template loader static files ==="
for theme in "${THEMES[@]}"; do
    for locale in en_US ar_SA; do
        dest_dir="$MAGENTO/pub/static/frontend/$theme/$locale/Magento_Ui/js/lib/knockout/template"
        if [ -d "$dest_dir" ]; then
            sudo cp -v "$SRC" "$dest_dir/loader.min.js"
            sudo cp -v "$SRC" "$dest_dir/loader.js"
            sudo chown www-data:www-data "$dest_dir/loader.min.js" "$dest_dir/loader.js"
        fi
    done
done

echo "=== Verify en_US head ==="
head -8 "$MAGENTO/pub/static/frontend/Hditsol/tyresonline/en_US/Magento_Ui/js/lib/knockout/template/loader.min.js"

echo "=== CloudFront invalidation ==="
DIST_ID=$(aws cloudfront list-distributions --query "DistributionList.Items[?Aliases.Items[?@=='cdn.tyresonline.sa']].Id | [0]" --output text 2>/dev/null || echo "")
if [ -n "$DIST_ID" ] && [ "$DIST_ID" != "None" ]; then
    aws cloudfront create-invalidation --distribution-id "$DIST_ID" \
        --paths "/static/*/frontend/Hditsol/tyresonline/*/Magento_Ui/js/lib/knockout/template/loader.min.js" \
        --query 'Invalidation.Id' --output text 2>/dev/null || echo "invalidation skipped"
else
    echo "CloudFront invalidation skipped (no aws cli creds)"
fi

echo "Done."
