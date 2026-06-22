#!/bin/bash
set -e
MAGENTO=/var/www/magento
DB_HOST=tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com
DB_USER=magento
DB_PASS=uxaYMIQRwEU0AFl1Ck69LJnH
DB=tyresonline_sa

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB" <<'EOSQL'
-- Currency
INSERT INTO core_config_data (scope, scope_id, path, value) VALUES
('default', 0, 'currency/options/base', 'SAR'),
('default', 0, 'currency/options/default', 'SAR'),
('default', 0, 'currency/options/allow', 'SAR')
ON DUPLICATE KEY UPDATE value=VALUES(value);

-- Country / locale (KSA)
INSERT INTO core_config_data (scope, scope_id, path, value) VALUES
('default', 0, 'general/country/default', 'SA'),
('default', 0, 'general/country/allow', 'SA'),
('default', 0, 'general/country/optional_zip_countries', 'SA'),
('default', 0, 'general/store_information/country_id', 'SA'),
('default', 0, 'general/locale/timezone', 'Asia/Riyadh')
ON DUPLICATE KEY UPDATE value=VALUES(value);

UPDATE core_config_data SET value='Riyadh' WHERE path='general/store_information/city' AND scope='default' AND scope_id=0;

-- HyperPay payment methods: SAR + SA
UPDATE core_config_data SET value='SAR' WHERE path LIKE 'payment/HyperPay_%/currencycode';
UPDATE core_config_data SET value='SA' WHERE path LIKE 'payment/HyperPay_%/specificcountry' AND value='AE';

-- Base currency rate
DELETE FROM directory_currency_rate WHERE currency_from='SAR' AND currency_to='SAR';
INSERT INTO directory_currency_rate (currency_from, currency_to, rate) VALUES ('SAR', 'SAR', 1.0000);
EOSQL

# Locked shared config (UAE clone values)
python3 <<'PYEOF'
path = '/var/www/magento/app/etc/config.php'
with open(path, encoding='utf-8') as f:
    content = f.read()

replacements = [
    ("'currencycode' => 'AED'", "'currencycode' => 'SAR'"),
    ("'specificcountry' => 'AE'", "'specificcountry' => 'SA'"),
]
for old, new in replacements:
    content = content.replace(old, new)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print('config.php updated (AED/AE -> SAR/SA)')
PYEOF

sudo -u www-data php "$MAGENTO/bin/magento app:config:import" -n
sudo -u www-data php "$MAGENTO/bin/magento cache:flush"

echo "--- verify ---"
sudo -u www-data php "$MAGENTO/bin/magento" config:show currency/options/base
sudo -u www-data php "$MAGENTO/bin/magento" config:show currency/options/default
sudo -u www-data php "$MAGENTO/bin/magento" config:show general/country/default
sudo -u www-data php "$MAGENTO/bin/magento" config:show payment/HyperPay_Visa/currencycode
