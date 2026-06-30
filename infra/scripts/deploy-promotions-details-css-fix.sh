#!/usr/bin/env bash
# Deploy promotions Details button CSS fix (AR/EN) and bust merged static cache.
set -euo pipefail

MAGENTO="${MAGENTO:-/var/www/magento}"
FIX_SNIPPET='.promotions-offers .offers-list .offers-list-inner .box .box-title{gap:.5rem;overflow:hidden}
.promotions-offers .offers-list .offers-list-inner .box .box-title>span:not(.link){flex:1 1 auto;min-width:0}
.promotions-offers .offers-list .offers-list-inner .box .box-title .link{flex-shrink:0;white-space:nowrap}'

cd "$MAGENTO"

for theme in tyresonline tyresonline-ar; do
  for loc in ar_SA en_US; do
    css="pub/static/frontend/Hditsol/${theme}/${loc}/css/cmspage.css"
    min="pub/static/frontend/Hditsol/${theme}/${loc}/css/cmspage.min.css"
    if [[ -f "$css" ]] && ! grep -q 'box-title>span:not(.link)' "$css"; then
      printf '\n%s\n' "$FIX_SNIPPET" >> "$css"
    fi
    if [[ -f "$css" ]]; then
      cp "$css" "$min"
    fi
  done
done

echo "$(date +%s)" > pub/static/deployed_version.txt
rm -rf pub/static/_cache/merged/* pub/static/_cache/* 2>/dev/null || sudo rm -rf pub/static/_cache/merged/* pub/static/_cache/*
sudo -u www-data php bin/magento cache:flush | tail -3
echo "DEPLOY_DONE"
