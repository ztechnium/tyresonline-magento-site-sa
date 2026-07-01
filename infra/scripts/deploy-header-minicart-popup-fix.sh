#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
KEY="${SSH_KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
HOST="${DEPLOY_HOST:-ubuntu@16.170.202.188}"
MAGENTO="${MAGENTO_ROOT:-/var/www/magento}"

echo "Deploying header minicart popup fix to $HOST"

scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline/web/js/custom.js" \
  "$HOST:/tmp/custom-en.js"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline-ar/web/js/custom.js" \
  "$HOST:/tmp/custom-ar.js"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline/Magento_Theme/web/js/minicart-open-fix.js" \
  "$HOST:/tmp/minicart-open-fix.js"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline/web/css/custom.css" \
  "$HOST:/tmp/custom.css"
scp -i "$KEY" -o StrictHostKeyChecking=no \
  "$ROOT/app/design/frontend/Hditsol/tyresonline/Magento_Theme/layout/default_head_blocks.xml" \
  "$HOST:/tmp/default_head_blocks.xml"

ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "MAGENTO='$MAGENTO' bash -s" <<'REMOTE'
set -euo pipefail
install_file() {
  local src="$1"
  local dest="$2"
  sudo install -m 0644 -o www-data -g www-data "$src" "$dest"
}

install_file /tmp/custom-en.js "$MAGENTO/app/design/frontend/Hditsol/tyresonline/web/js/custom.js"
install_file /tmp/custom-ar.js "$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/js/custom.js"
install_file /tmp/minicart-open-fix.js "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Theme/web/js/minicart-open-fix.js"
install_file /tmp/custom.css "$MAGENTO/app/design/frontend/Hditsol/tyresonline/web/css/custom.css"
install_file /tmp/default_head_blocks.xml "$MAGENTO/app/design/frontend/Hditsol/tyresonline/Magento_Theme/layout/default_head_blocks.xml"

for theme in tyresonline tyresonline-ar; do
  for locale in en_US ar_SA; do
    js_src="$MAGENTO/app/design/frontend/Hditsol/tyresonline/web/js/custom.js"
    if [ "$theme" = "tyresonline-ar" ]; then
      js_src="$MAGENTO/app/design/frontend/Hditsol/tyresonline-ar/web/js/custom.js"
    fi
    js_dest_dir="$MAGENTO/pub/static/frontend/Hditsol/$theme/$locale/js"
    theme_js_dir="$MAGENTO/pub/static/frontend/Hditsol/$theme/$locale/Magento_Theme/js"
    css_dest_dir="$MAGENTO/pub/static/frontend/Hditsol/$theme/$locale/css"
    sudo mkdir -p "$js_dest_dir" "$theme_js_dir" "$css_dest_dir"
    install_file "$js_src" "$js_dest_dir/custom.js"
    install_file "$js_src" "$js_dest_dir/custom.min.js"
    install_file /tmp/minicart-open-fix.js "$theme_js_dir/minicart-open-fix.js"
    install_file /tmp/minicart-open-fix.js "$theme_js_dir/minicart-open-fix.min.js"
    install_file /tmp/custom.css "$css_dest_dir/custom.css"
    install_file /tmp/custom.css "$css_dest_dir/custom.min.css"
  done
done

printf '%s' "$(date +%s)" | sudo tee "$MAGENTO/pub/static/deployed_version.txt" >/dev/null
sudo rm -rf "$MAGENTO/pub/static/_cache/merged/"*
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush
REMOTE

echo "Deployed static version:"
ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "cat $MAGENTO/pub/static/deployed_version.txt"
