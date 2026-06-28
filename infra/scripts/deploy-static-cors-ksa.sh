#!/bin/bash
# Enable CORS on /static/ for CDN cross-origin XHR (Magento templates, js-translation.json)
set -euo pipefail

CONF_SRC=/tmp/apache-cors-static.conf
CONF_DST=/etc/apache2/conf-available/magento-cors-static.conf

if [ ! -f "$CONF_SRC" ]; then
  echo "Missing $CONF_SRC"
  exit 1
fi

sudo cp "$CONF_SRC" "$CONF_DST"
sudo a2enmod headers rewrite 2>/dev/null || true
sudo a2enconf magento-cors-static 2>/dev/null || sudo ln -sf "$CONF_DST" /etc/apache2/conf-enabled/magento-cors-static.conf

echo "=== Apache config test ==="
sudo apache2ctl configtest

echo "=== Reload Apache ==="
sudo systemctl reload apache2

echo "=== Verify CORS (origin -> CDN) ==="
curl -sSI "https://cdn.tyresonline.sa/static/version1782431267/frontend/Hditsol/tyresonline/en_US/js-translation.json" \
  -H "Origin: https://stg.tyresonline.sa" | grep -iE 'HTTP/|access-control|vary' || true

curl -sSI "https://cdn.tyresonline.sa/static/version1782431267/frontend/Hditsol/tyresonline/en_US/Magento_Ui/templates/modal/modal-custom.html" \
  -H "Origin: https://stg.tyresonline.sa" | grep -iE 'HTTP/|access-control|vary' || true

echo "=== CloudFront invalidation (static path) ==="
DIST_ID=$(aws cloudfront list-distributions --query "DistributionList.Items[?Aliases.Items[?@=='cdn.tyresonline.sa']].Id | [0]" --output text 2>/dev/null || echo "")
if [ -n "$DIST_ID" ] && [ "$DIST_ID" != "None" ]; then
  aws cloudfront create-invalidation --distribution-id "$DIST_ID" --paths "/static/*" --query 'Invalidation.Id' --output text 2>/dev/null || echo "invalidation skipped (no aws creds)"
else
  echo "CloudFront distribution not found via CLI — invalidate /static/* manually if headers missing on CDN"
fi

echo "DONE"
