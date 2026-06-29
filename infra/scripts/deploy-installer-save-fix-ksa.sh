#!/usr/bin/env bash
# Deploy installer save fixes and recompile DI (required after Savecartinstaller constructor changes).
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
MAGENTO="${MAGENTO:-/var/www/magento}"
HOST="${DEPLOY_HOST:-16.170.202.188}"
USER="${DEPLOY_USER:-ubuntu}"
KEY="${DEPLOY_KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
TMP="/tmp/installer-save-fix"

chmod 600 "$KEY"
ssh -i "$KEY" -o StrictHostKeyChecking=no "${USER}@${HOST}" "mkdir -p ${TMP}"

scp -i "$KEY" -o StrictHostKeyChecking=no \
  "${REPO_ROOT}/app/code/Hdweb/Installer/Controller/Ajax/Savecartinstaller.php" \
  "${REPO_ROOT}/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml" \
  "${USER}@${HOST}:${TMP}/"

ssh -i "$KEY" -o StrictHostKeyChecking=no "${USER}@${HOST}" "sudo cp ${TMP}/Savecartinstaller.php ${MAGENTO}/app/code/Hdweb/Installer/Controller/Ajax/Savecartinstaller.php && \
  sudo cp ${TMP}/cart-installer.phtml ${MAGENTO}/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml && \
  sudo chown www-data:www-data ${MAGENTO}/app/code/Hdweb/Installer/Controller/Ajax/Savecartinstaller.php ${MAGENTO}/app/code/Hdweb/Installer/view/frontend/templates/cart-installer.phtml && \
  sudo rm -rf ${MAGENTO}/generated/code/Hdweb/Installer/ && \
  sudo -u www-data php ${MAGENTO}/bin/magento setup:di:compile && \
  sudo -u www-data php ${MAGENTO}/bin/magento cache:flush && \
  sudo rm -rf ${MAGENTO}/var/page_cache/* && \
  sudo systemctl restart apache2"

echo "Deploy complete."
