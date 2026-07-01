#!/usr/bin/env bash
set -euo pipefail

KEY="${SSH_KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
HOST="${DEPLOY_HOST:-ubuntu@16.170.202.188}"
MAGENTO="${MAGENTO_ROOT:-/var/www/magento}"

echo "Restoring Arabic Knockout template loader on $HOST"

ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "MAGENTO='$MAGENTO' bash -s" <<'REMOTE'
set -euo pipefail

SRC="$MAGENTO/pub/static/frontend/Hditsol/tyresonline/en_US/Magento_Ui/js/lib/knockout/template/loader.min.js"

if ! grep -q 'defaultPlugin' "$SRC"; then
  echo "Source loader file does not look like the Knockout template loader: $SRC" >&2
  exit 1
fi

for locale in en_US ar_SA; do
  for file in loader.min.js loader.js; do
    src="$SRC"
    if [ "$file" = "loader.js" ]; then
      src="${SRC%.min.js}"
      [ -f "$src" ] || continue
    fi
    dest_dir="$MAGENTO/pub/static/frontend/Hditsol/tyresonline-ar/$locale/Magento_Ui/js/lib/knockout/template"
    sudo mkdir -p "$dest_dir"
    sudo install -m 0644 -o www-data -g www-data "$src" "$dest_dir/$file"
    echo "Installed $dest_dir/$file"
    if grep -q "widget('mage.loader" "$dest_dir/$file"; then
      echo "ERROR: $dest_dir/$file still contains mage.loader widget code" >&2
      exit 1
    fi
  done
done

printf '%s' "$(date +%s)" | sudo tee "$MAGENTO/pub/static/deployed_version.txt" >/dev/null
sudo rm -rf "$MAGENTO/pub/static/_cache/merged/"*
cd "$MAGENTO"
sudo -u www-data php bin/magento cache:flush
REMOTE

echo "Deployed static version:"
ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "cat $MAGENTO/pub/static/deployed_version.txt"
