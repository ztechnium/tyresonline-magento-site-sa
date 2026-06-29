#!/usr/bin/env bash
# Deploy place-order fixes (StorePickup observer + shipping method validation).
set -euo pipefail

MAGENTO_ROOT="${MAGENTO_ROOT:-/var/www/magento}"
REMOTE="${REMOTE:-ubuntu@16.170.202.188}"
KEY="${KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

SSH=(ssh -i "$KEY" -o StrictHostKeyChecking=no "$REMOTE")
SCP=(scp -i "$KEY" -o StrictHostKeyChecking=no)

FILES=(
  "app/code/Ecomteck/StorePickup/Observer/DataAssignObserver.php"
  "app/code/Ecomteck/OneStepCheckout/Observer/SetDefaultShippingObserver.php"
  "app/code/Ecomteck/OneStepCheckout/etc/config.xml"
  "app/code/Ecomteck/OneStepCheckout/view/frontend/web/js/mixin/shipping-mixin.js"
  "app/code/Hdweb/Coreoverride/Plugin/Checkout/QuoteManagementPlugin.php"
  "app/code/Hdweb/Coreoverride/etc/di.xml"
)

echo "==> Copying place-order fix files to staging"
for rel in "${FILES[@]}"; do
  "${SCP[@]}" "$REPO_ROOT/$rel" "$REMOTE:/tmp/$(basename "$rel").deploy"
  "${SSH[@]}" "sudo mkdir -p '$MAGENTO_ROOT/$(dirname "$rel")' && sudo cp '/tmp/$(basename "$rel").deploy' '$MAGENTO_ROOT/$rel'"
done

echo "==> Recompile and flush cache"
"${SSH[@]}" "cd '$MAGENTO_ROOT' && \
  sudo rm -rf generated/code/Ecomteck/ generated/code/Hdweb/ var/page_cache/* && \
  sudo -u www-data php bin/magento setup:di:compile && \
  sudo -u www-data php bin/magento cache:flush && \
  sudo -u www-data php bin/magento setup:static-content:deploy -f en_US ar_SA --theme Hditsol/tyresonline -j 4 && \
  sudo systemctl restart apache2"

echo "==> Place-order fix deployed"
