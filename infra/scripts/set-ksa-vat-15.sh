#!/bin/bash
set -e
MAGENTO=/var/www/magento
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB=tyresonline_sa

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" <<'EOSQL'
-- Custom VAT display modules
UPDATE core_config_data SET value='15' WHERE path IN ('hdweb/general/vat_percentage', 'purchaseorder/general/vat');
INSERT INTO core_config_data (scope, scope_id, path, value) VALUES
('default', 0, 'hdweb/general/vat_percentage', '15'),
('default', 0, 'purchaseorder/general/vat', '15'),
('default', 0, 'tax/defaults/country', 'SA')
ON DUPLICATE KEY UPDATE value=VALUES(value);

-- Magento tax rate/rule for KSA 15%
UPDATE tax_calculation_rate
SET tax_country_id='SA', code='KSA VAT', rate=15.0000
WHERE tax_calculation_rate_id=3;

UPDATE tax_calculation_rule
SET code='KSA VAT Rule'
WHERE tax_calculation_rule_id=1;

SELECT tax_calculation_rate_id, code, rate, tax_country_id FROM tax_calculation_rate;
SELECT path, value FROM core_config_data WHERE path IN ('hdweb/general/vat_percentage','purchaseorder/general/vat','tax/defaults/country');
EOSQL

sudo python3 /tmp/update-config-ksa-vat.py
sudo -u www-data php "$MAGENTO/bin/magento" app:config:import -n
sudo -u www-data php "$MAGENTO/bin/magento" cache:flush

echo "--- verify ---"
sudo -u www-data php "$MAGENTO/bin/magento" config:show hdweb/general/vat_percentage
sudo -u www-data php "$MAGENTO/bin/magento" config:show tax/defaults/country
sudo -u www-data php "$MAGENTO/bin/magento" config:show purchaseorder/general/vat
