#!/bin/bash
# Sync product media from UAE staging EC2 to KSA staging EC2.
# Run on KSA server as ubuntu (not root — sudo rsync breaks remote listing).
set -euo pipefail

UAE_HOST="${UAE_HOST:-ubuntu@ec2-13-50-7-98.eu-north-1.compute.amazonaws.com}"
SSH_KEY="${SSH_KEY:-/tmp/staging-key-tyresonline.pem}"
MAGENTO_MEDIA="${MAGENTO_MEDIA:-/var/www/magento/pub/media}"
UAE_MEDIA="${UAE_MEDIA:-/var/www/magento/pub/media}"
STAGING="${STAGING:-/tmp/media-import}"

RSYNC_OPTS=(-az --info=progress2)
EXCLUDES=(
  --exclude 'cache/**'
  --exclude 'tmp/**'
  --exclude 'captcha/**'
  --exclude 'catalog/product/cache/**'
)

echo "=== Sync media paths from UAE -> KSA (via $STAGING) ==="
echo "UAE: $UAE_HOST:$UAE_MEDIA"

PATHS=(
  catalog/product
)

for path in "${PATHS[@]}"; do
  echo "--- rsync $path ---"
  if ! ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "$UAE_HOST" "test -d $UAE_MEDIA/$path"; then
    echo "skip: $path not on UAE server"
    continue
  fi
  rm -rf "$STAGING/$path"
  mkdir -p "$STAGING/$path"
  rsync "${RSYNC_OPTS[@]}" "${EXCLUDES[@]}" \
    -e "ssh -i $SSH_KEY -o StrictHostKeyChecking=no" \
    "$UAE_HOST:$UAE_MEDIA/$path/" \
    "$STAGING/$path/"
  sudo rsync -a "$STAGING/$path/" "$MAGENTO_MEDIA/$path/"
done

echo "=== Fix ownership ==="
sudo chown -R www-data:www-data "$MAGENTO_MEDIA"
sudo find "$MAGENTO_MEDIA" -type d -exec chmod 775 {} \;
sudo find "$MAGENTO_MEDIA" -type f -exec chmod 664 {} \;
rm -rf "$STAGING"
echo "=== Done ==="
du -sh "$MAGENTO_MEDIA/catalog/product" 2>/dev/null || true
find "$MAGENTO_MEDIA/catalog/product" -type f 2>/dev/null | wc -l
