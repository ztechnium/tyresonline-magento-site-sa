#!/usr/bin/env bash
# Sync catalog/product media UAE -> KSA (non-interactive).
set -euo pipefail

KEY="${KEY:-/home/ubuntu/staging-key-tyresonline.pem}"
UAE_HOST="${UAE_HOST:-ubuntu@ec2-13-50-7-98.eu-north-1.compute.amazonaws.com}"
MAGENTO_MEDIA="${MAGENTO_MEDIA:-/var/www/magento/pub/media}"

RSYNC_OPTS=(-az --info=stats2)
EXCLUDES=(
  --exclude 'cache/**'
  --exclude 'tmp/**'
)

echo "=== rsync catalog/product UAE -> KSA ==="
sudo rsync "${RSYNC_OPTS[@]}" "${EXCLUDES[@]}" \
  -e "ssh -i $KEY -o StrictHostKeyChecking=no" \
  "$UAE_HOST:/var/www/magento/pub/media/catalog/product/" \
  "$MAGENTO_MEDIA/catalog/product/"

sudo chown -R www-data:www-data "$MAGENTO_MEDIA/catalog/product"
echo "=== done ==="
du -sh "$MAGENTO_MEDIA/catalog/product"
