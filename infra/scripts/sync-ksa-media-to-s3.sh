#!/bin/bash
# Sync full pub/media tree to S3 with /media prefix for CloudFront path mapping.
set -euo pipefail

BUCKET="${S3_BUCKET:-tyresonline-sa-prod-media}"
MEDIA="${MAGENTO_MEDIA:-/var/www/magento/pub/media}"

echo "=== Sync ${MEDIA} -> s3://${BUCKET}/media/ ==="
aws s3 sync "${MEDIA}/" "s3://${BUCKET}/media/" \
  --delete \
  --only-show-errors \
  --no-progress

echo "=== Sample keys ==="
aws s3 ls "s3://${BUCKET}/media/" | head -10
echo "=== Done ==="
