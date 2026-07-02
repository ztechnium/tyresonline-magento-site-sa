#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
KEY="${SSH_KEY:-/home/ubuntu/.cursor/projects/workspace/uploads/staging-key-tyresonline_b8ac.pem}"
HOST="${DEPLOY_HOST:-ubuntu@16.170.202.188}"
MAGENTO="${MAGENTO_ROOT:-/var/www/magento}"
FILE="app/code/Hyperpay/Extension/Helper/Data.php"

echo "Deploying Hyperpay preg_match null fix to $HOST"
scp -i "$KEY" -o StrictHostKeyChecking=no "$ROOT/$FILE" "$HOST:/tmp/Hyperpay-Data.php"
ssh -i "$KEY" -o StrictHostKeyChecking=no "$HOST" "MAGENTO='$MAGENTO' bash -s" <<'REMOTE'
set -euo pipefail
sudo install -m 0644 -o www-data -g www-data /tmp/Hyperpay-Data.php "$MAGENTO/app/code/Hyperpay/Extension/Helper/Data.php"
cd "$MAGENTO"
sudo -u www-data php -r '
require "app/bootstrap.php";
$om = Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$helper = $om->get(Hyperpay\Extension\Helper\Data::class);
foreach ([null, "", "Riyadh", "John"] as $value) {
    $result = $helper->isThisEnglishText($value);
    echo json_encode($value) . " => " . json_encode($result) . PHP_EOL;
}
'
sudo -u www-data php bin/magento cache:flush
REMOTE
echo "Done."
