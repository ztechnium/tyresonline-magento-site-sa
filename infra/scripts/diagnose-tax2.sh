#!/bin/bash
DB="-h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa"

mysql $DB -e "
SELECT * FROM tax_calculation_rate;
SELECT * FROM tax_calculation_rule;
SELECT * FROM tax_calculation;
SELECT path, value FROM core_config_data WHERE path LIKE 'tax/%' AND path NOT LIKE 'tax/vertex%';
"

echo '=== grep VAT 5% in theme ==='
grep -rn 'VAT.*5\|5%.*VAT\|vat.*5' /var/www/magento/app/design/frontend/Hditsol/tyresonline --include='*.phtml' --include='*.xml' --include='*.js' 2>/dev/null | head -20
grep -rn 'VAT.*5\|5%.*VAT' /var/www/magento/app/code/Hdweb --include='*.phtml' --include='*.js' 2>/dev/null | head -15
