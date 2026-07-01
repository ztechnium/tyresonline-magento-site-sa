#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
KEY="${SSH_KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
HOST="${DEPLOY_HOST:-ubuntu@16.170.202.188}"
MAGENTO="${MAGENTO_ROOT:-/var/www/magento}"

echo "Deploying promotions popup centering CSS to $HOST"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline/web/scss/cmspage.scss" \
  "$HOST:/tmp/cmspage-en.scss"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline/web/css/cmspage.css" \
  "$HOST:/tmp/cmspage-en.css"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline-ar/web/scss/cmspage.scss" \
  "$HOST:/tmp/cmspage-ar.scss"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline-ar/web/css/cmspage.css" \
  "$HOST:/tmp/cmspage-ar.css"

ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "MAGENTO='$MAGENTO' bash -s" <<'REMOTE'
set -euo pipefail
install_file() {
  local src="$1"
  local dest="$2"
  sudo install -m 0644 -o www-data -g www-data "$src" "$dest"
}

install_file /tmp/cmspage-en.scss "$MAGENTO/app/design/frontend/Hditsol/tyresonline/web/scss/cmspage.scss"
install_file /tmp/cmspage-en.css "$MAGENTO/app/design/frontend/Hditsol/tyresonline/web/css/cmspage.css"
install_file /tmp/cmspage-ar.scss "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/scss/cmspage.scss"
install_file /tmp/cmspage-ar.css "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/css/cmspage.css"

for theme in tyresonline tyresonline-ar; do
  for locale in en_US ar_SA; do
    src="$MAGENTO/app/design/frontend/Hditsol/$theme/web/css/cmspage.css"
    dest_dir="$MAGENTO/pub/static/frontend/Hditsol/$theme/$locale/css"
    sudo mkdir -p "$dest_dir"
    sudo install -m 0644 -o www-data -g www-data "$src" "$dest_dir/cmspage.min.css"
    sudo install -m 0644 -o www-data -g www-data "$src" "$dest_dir/cmspage.css"
  done
done

printf '%s' "$(date +%s)" | sudo tee "$MAGENTO/pub/static/deployed_version.txt" >/dev/null
sudo rm -rf "$MAGENTO/pub/static/_cache/merged/"*
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush
REMOTE

echo "Deployed static version:"
ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "cat $MAGENTO/pub/static/deployed_version.txt"
