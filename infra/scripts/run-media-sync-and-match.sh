#!/bin/bash
# Full pipeline: sync media + export JSON + import gallery rows on KSA staging.
set -euo pipefail

KSA_HOST="${KSA_HOST:-ubuntu@ec2-16-170-225-52.eu-north-1.compute.amazonaws.com}"
UAE_HOST="${UAE_HOST:-ubuntu@ec2-13-50-7-98.eu-north-1.compute.amazonaws.com}"
KEY="${KEY:-staging-key-tyresonline.pem}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

SSH_KSA=(ssh -i "$KEY" -o StrictHostKeyChecking=no "$KSA_HOST")
SSH_UAE=(ssh -i "$KEY" -o StrictHostKeyChecking=no "$UAE_HOST")
SCP=(scp -i "$KEY" -o StrictHostKeyChecking=no)

echo "=== 1/5 Sync media UAE -> KSA ==="
"${SCP[@]}" "$SCRIPT_DIR/sync-media-uae-to-ksa.sh" "$KSA_HOST:/tmp/sync-media-uae-to-ksa.sh"
"${SSH_KSA[@]}" "chmod +x /tmp/sync-media-uae-to-ksa.sh && SSH_KEY=/home/ubuntu/$KEY /tmp/sync-media-uae-to-ksa.sh"

echo "=== 2/5 Export KSA match keys ==="
"${SCP[@]}" "$SCRIPT_DIR/export-ksa-match-keys.php" "$KSA_HOST:/tmp/export-ksa-match-keys.php"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/export-ksa-match-keys.php > /tmp/ksa-products-keys.json"

echo "=== 3/5 Export UAE images by match key ==="
"${SCP[@]}" "$SCRIPT_DIR/export-uae-images-by-match-key.php" "$UAE_HOST:/tmp/export-uae-images-by-match-key.php"
"${SSH_UAE[@]}" "sudo -u www-data php /tmp/export-uae-images-by-match-key.php > /tmp/uae-images-by-key.json"

echo "=== 4/5 Match report ==="
"${SCP[@]}" "$SCRIPT_DIR/match-product-images-report.php" "$KSA_HOST:/tmp/match-product-images-report.php"
"${SCP[@]}" "$KSA_HOST:/tmp/ksa-products-keys.json" /tmp/ksa-products-keys.json
"${SCP[@]}" "$UAE_HOST:/tmp/uae-images-by-key.json" /tmp/uae-images-by-key.json
"${SCP[@]}" /tmp/uae-images-by-key.json "$KSA_HOST:/tmp/uae-images-by-key.json"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/match-product-images-report.php /tmp/ksa-products-keys.json /tmp/uae-images-by-key.json"

echo "=== 5/5 Import gallery (dry-run then live) ==="
"${SCP[@]}" "$SCRIPT_DIR/import-product-images-ksa.php" "$KSA_HOST:/tmp/import-product-images-ksa.php"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/import-product-images-ksa.php /tmp/ksa-products-keys.json /tmp/uae-images-by-key.json --dry-run"
"${SSH_KSA[@]}" "sudo -u www-data php /tmp/import-product-images-ksa.php /tmp/ksa-products-keys.json /tmp/uae-images-by-key.json"
"${SSH_KSA[@]}" "cd /var/www/magento && sudo -u www-data php bin/magento cache:flush"
echo "Import complete."
