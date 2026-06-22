#!/bin/bash
set -euo pipefail
THEME=/var/www/magento/app/design/frontend/Hditsol/tyresonline
CODE=/var/www/magento/app/code

echo '=== Before ==='
grep 'Tax' "$THEME/i18n/en_US.csv" | head -3
grep 'VAT' "$THEME/Magento_Tax/templates/order/tax.phtml" | head -3

echo '=== Update en_US.csv ==='
sed -i 's/"Tax","VAT (5%)"/"Tax","VAT (15%)"/g' "$THEME/i18n/en_US.csv"
sed -i 's/"Tax","VAT(5%)"/"Tax","VAT (15%)"/g' "$THEME/i18n/en_US.csv"

if [ -f "$THEME/i18n/ar_SA.csv" ]; then
  sed -i 's/VAT (5%)/VAT (15%)/g' "$THEME/i18n/ar_SA.csv"
  sed -i 's/VAT(5%)/VAT (15%)/g' "$THEME/i18n/ar_SA.csv"
  # common Arabic label if present
  sed -i 's/ضريبة القيمة المضافة (5%)/ضريبة القيمة المضافة (15%)/g' "$THEME/i18n/ar_SA.csv" 2>/dev/null || true
fi

echo '=== Update tax.phtml ==='
sed -i 's/VAT(5%)/VAT(15%)/g' "$THEME/Magento_Tax/templates/order/tax.phtml"
sed -i 's/VAT (5%)/VAT (15%)/g' "$THEME/Magento_Tax/templates/order/tax.phtml"

echo '=== Update Hdweb email/PDF strings ==='
for f in \
  "$CODE/Hdweb/Purchaseorder/Controller/Adminhtml/Create/Save.php" \
  "$CODE/Hdweb/Purchaseorder/Controller/Adminhtml/Create/Editsave.php" \
  "$CODE/Hdweb/Booking/Controller/Index/Serviceemail.php"
do
  if [ -f "$f" ]; then
    sed -i 's/VAT(5%)/VAT(15%)/g; s/VAT (5%)/VAT (15%)/g; s/5% VAT/15% VAT/g' "$f"
  fi
done

echo '=== After ==='
grep 'Tax' "$THEME/i18n/en_US.csv" | head -3
grep 'VAT' "$THEME/Magento_Tax/templates/order/tax.phtml" | head -3

cd /var/www/magento
sudo -u www-data php bin/magento cache:flush 2>&1 | tail -3
sudo rm -rf var/view_preprocessed/* pub/static/frontend/Hditsol/tyresonline/en_US/js-translation.json 2>/dev/null || true

echo DONE
