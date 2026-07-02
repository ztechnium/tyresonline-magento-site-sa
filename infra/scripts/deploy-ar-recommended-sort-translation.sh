#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
KEY="${SSH_KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
HOST="${DEPLOY_HOST:-ubuntu@16.170.202.188}"
MAGENTO="${MAGENTO_ROOT:-/var/www/magento}"

echo "Deploying Arabic Recommended sort translation to $HOST"

scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline/i18n/ar_SA.csv" \
  "$HOST:/tmp/ar_SA-parent.csv"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline-ar/i18n/ar_SA.csv" \
  "$HOST:/tmp/ar_SA-ar.csv"

ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "MAGENTO='$MAGENTO' bash -s" <<'REMOTE'
set -euo pipefail
install_file() {
  local src="$1"
  local dest="$2"
  sudo install -m 0644 -o www-data -g www-data "$src" "$dest"
}

install_file /tmp/ar_SA-parent.csv "$MAGENTO/app/design/frontend/Hditsol/tyresonline/i18n/ar_SA.csv"
install_file /tmp/ar_SA-ar.csv "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/i18n/ar_SA.csv"

for theme in tyresonline tyresonline-ar; do
  for locale in en_US ar_SA; do
    src="$MAGENTO/app/design/frontend/Hditsol/$theme/i18n/ar_SA.csv"
    dest_dir="$MAGENTO/pub/static/frontend/Hditsol/$theme/$locale"
    if [ -f "$src" ]; then
      sudo mkdir -p "$dest_dir"
      install_file "$src" "$dest_dir/ar_SA.csv"
    fi
  done
done

cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush translate full_page block_html
REMOTE

echo "Done."
