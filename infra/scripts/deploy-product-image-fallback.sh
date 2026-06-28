#!/usr/bin/env bash
# Deploy product image fallback changes to staging.
set -euo pipefail

SSH_KEY="${SSH_KEY:-staging-key-tyresonline.pem}"
SSH_HOST="${SSH_HOST:-ubuntu@ec2-16-170-225-52.eu-north-1.compute.amazonaws.com}"
REMOTE="/var/www/magento"
LOCAL_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

scp -i "$SSH_KEY" \
  "$LOCAL_ROOT/app/code/Hdweb/Tyrefinder/Helper/ProductImage.php" \
  "$SSH_HOST:/tmp/ProductImage.php"

scp -i "$SSH_KEY" \
  "$LOCAL_ROOT/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml" \
  "$LOCAL_ROOT/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml" \
  "$LOCAL_ROOT/app/design/frontend/Hditsol/tyresonline/web/images/tyre-placeholder.svg" \
  "$LOCAL_ROOT/infra/scripts/audit-broken-product-media.php" \
  "$LOCAL_ROOT/infra/scripts/apply-generic-placeholder.php" \
  "$SSH_HOST:/tmp/"

ssh -i "$SSH_KEY" "$SSH_HOST" bash -s <<'REMOTE_SCRIPT'
set -euo pipefail
REMOTE="/var/www/magento"

sudo cp /tmp/ProductImage.php "$REMOTE/app/code/Hdweb/Tyrefinder/Helper/ProductImage.php"
sudo cp /tmp/list.phtml "$REMOTE/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/list.phtml"
sudo cp /tmp/gallery.phtml "$REMOTE/app/design/frontend/Hditsol/tyresonline/Magento_Catalog/templates/product/view/gallery.phtml"
sudo cp /tmp/tyre-placeholder.svg "$REMOTE/app/design/frontend/Hditsol/tyresonline/web/images/tyre-placeholder.svg"
sudo cp /tmp/audit-broken-product-media.php "$REMOTE/infra/scripts/audit-broken-product-media.php"
sudo cp /tmp/apply-generic-placeholder.php "$REMOTE/infra/scripts/apply-generic-placeholder.php"
sudo mkdir -p "$REMOTE/infra/scripts"
sudo chown www-data:www-data "$REMOTE/app/code/Hdweb/Tyrefinder/Helper/ProductImage.php"
sudo chmod 664 "$REMOTE/app/code/Hdweb/Tyrefinder/Helper/ProductImage.php"

echo "=== Image coverage audit ==="
sudo -u www-data php "$REMOTE/infra/scripts/audit-ksa-image-coverage.php" || true
echo "=== Broken media audit ==="
sudo -u www-data php "$REMOTE/infra/scripts/audit-broken-product-media.php" --limit=10 || true
echo "=== Apply generic placeholder (dry-run) ==="
sudo -u www-data php "$REMOTE/infra/scripts/apply-generic-placeholder.php" --dry-run || true
echo "=== Apply generic placeholder ==="
sudo -u www-data php "$REMOTE/infra/scripts/apply-generic-placeholder.php" || true

redis-cli -h tyresonline-sa-prod-redis.rlsoda.0001.eun1.cache.amazonaws.com -n 3 FLUSHDB
redis-cli -h tyresonline-sa-prod-redis.rlsoda.0001.eun1.cache.amazonaws.com -n 4 FLUSHDB
sudo rm -rf /var/cache/apache2/mod_cache_disk/* "$REMOTE/var/cache/"* "$REMOTE/var/page_cache/"*
sudo systemctl restart apache2
cd "$REMOTE" && sudo -u www-data php bin/magento cache:flush
echo "Deploy complete."
REMOTE_SCRIPT
