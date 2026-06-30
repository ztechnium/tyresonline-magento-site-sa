#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SRC="$ROOT/app/design/frontend/Hditsol/tyresonline/MGS_Ajaxlayernavigation/web/js/ajax-navigation.js"
KEY="${SSH_KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
HOST="${DEPLOY_HOST:-ubuntu@16.170.202.188}"
MAGENTO="${MAGENTO_ROOT:-/var/www/magento}"
TMP_MIN="$(mktemp /tmp/ajax-navigation.min.XXXXXX.js)"

cleanup() {
  rm -f "$TMP_MIN"
}
trap cleanup EXIT

if ! grep -q 'stripUrlFragment' "$SRC"; then
  echo "Source ajax-navigation.js is missing stripUrlFragment helper" >&2
  exit 1
fi

npx --yes terser "$SRC" -c -m -o "$TMP_MIN"

echo "Deploying ajax-navigation.js to $HOST"
scp -i "$KEY" -o StrictHostKeyChecking=no "$SRC" "$HOST:/tmp/ajax-navigation.js"
scp -i "$KEY" -o StrictHostKeyChecking=no "$TMP_MIN" "$HOST:/tmp/ajax-navigation.min.js"

ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "MAGENTO='$MAGENTO' bash -s" <<'REMOTE'
set -euo pipefail
THEMES=(
  "frontend/Hditsol/tyresonline/en_US"
  "frontend/Hditsol/tyresonline/ar_SA"
  "frontend/Hditsol/tyresonline-ar/en_US"
  "frontend/Hditsol/tyresonline-ar/ar_SA"
)
TARGET_DIR="$MAGENTO/app/design/frontend/Hditsol/tyresonline/MGS_Ajaxlayernavigation/web/js"
sudo install -m 0644 -o www-data -g www-data /tmp/ajax-navigation.js "$TARGET_DIR/ajax-navigation.js"

for theme in "${THEMES[@]}"; do
  dir="$MAGENTO/pub/static/$theme/MGS_Ajaxlayernavigation/js"
  sudo mkdir -p "$dir"
  sudo install -m 0644 -o www-data -g www-data /tmp/ajax-navigation.min.js "$dir/ajax-navigation.min.js"
  sudo install -m 0644 -o www-data -g www-data /tmp/ajax-navigation.js "$dir/ajax-navigation.js"
done

echo $(date +%s) | sudo tee "$MAGENTO/pub/static/deployed_version.txt" >/dev/null
sudo rm -rf "$MAGENTO/pub/static/_cache/merged/"*
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush
REMOTE

echo "Deployed. New static version:"
ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "cat $MAGENTO/pub/static/deployed_version.txt"
