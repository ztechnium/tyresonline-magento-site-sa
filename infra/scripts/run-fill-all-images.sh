#!/usr/bin/env bash
# Fill all KSA product images without backup: multi-key UAE match + local + brand + placeholder.
set -euo pipefail

KSA_HOST="${KSA_HOST:-ubuntu@ec2-16-170-225-52.eu-north-1.compute.amazonaws.com}"
UAE_HOST="${UAE_HOST:-ubuntu@ec2-13-50-7-98.eu-north-1.compute.amazonaws.com}"
KEY="${KEY:-staging-key-tyresonline.pem}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

SSH_KSA=(ssh -i "$KEY" -o StrictHostKeyChecking=no "$KSA_HOST")
SSH_UAE=(ssh -i "$KEY" -o StrictHostKeyChecking=no "$UAE_HOST")
SCP=(scp -i "$KEY" -o StrictHostKeyChecking=no)

SCRIPTS=(
  export-ksa-products-full.php
  export-uae-images-full.php
  extract-needed-image-paths.php
  download-images-from-uae-staging.php
  fill-product-images-ksa.php
  match-local-media-by-name.php
  assign-brand-local-image.php
  apply-brand-fallback-images.php
  apply-generic-placeholder.php
  audit-ksa-image-coverage.php
)

for s in "${SCRIPTS[@]}"; do
  "${SCP[@]}" "$SCRIPT_DIR/$s" "$KSA_HOST:/tmp/$s"
done
"${SCP[@]}" "$SCRIPT_DIR/export-uae-images-full.php" "$UAE_HOST:/tmp/export-uae-images-full.php"

echo "=== Export KSA + UAE data ==="
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/export-ksa-products-full.php > /tmp/ksa-products-full.json"
"${SSH_UAE[@]}" "sudo -u www-data php /tmp/export-uae-images-full.php > /tmp/uae-images-full.json"
"${SCP[@]}" "$UAE_HOST:/tmp/uae-images-full.json" "$KSA_HOST:/tmp/uae-images-full.json"

echo "=== Try download missing files from UAE staging ==="
"${SSH_KSA[@]}" "php /tmp/extract-needed-image-paths.php /tmp/ksa-products-full.json /tmp/uae-images-full.json; sudo php /tmp/download-images-from-uae-staging.php /tmp/needed-image-paths.txt || true; sudo chown -R www-data:www-data /var/www/magento/pub/media/catalog/product"

echo "=== Fill pipeline ==="
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/fill-product-images-ksa.php /tmp/ksa-products-full.json /tmp/uae-images-full.json"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/match-local-media-by-name.php /tmp/ksa-products-full.json"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/assign-brand-local-image.php /tmp/ksa-products-full.json"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/apply-brand-fallback-images.php /tmp/ksa-products-full.json"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/apply-generic-placeholder.php"

echo "=== Audit + cache flush ==="
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/audit-ksa-image-coverage.php"
"${SSH_KSA[@]}" "cd /var/www/magento && sudo -u www-data php bin/magento cache:flush"
echo "Done."
