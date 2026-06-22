#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
BIN=(sudo -u www-data php "$MAGENTO/bin/magento")
LOCK=(--lock-config)
TOKEN='OGFjOWE0ZmU5ZWQ1NzIyZDAxOWVkNThmMDk2ZjAwOGR8dDdydHdpIVlnY3NqQk5NTGUzPXg='
VISA='8acda4c88da6f653018dbb779a816465'
MADA='8acda4c88da6f653018dbb7828be646c'

"${BIN[@]}" config:set "${LOCK[@]}" payment/hyperpay/auth "$TOKEN"
"${BIN[@]}" config:set "${LOCK[@]}" payment/HyperPay_Visa/entityId "$VISA"
"${BIN[@]}" config:set "${LOCK[@]}" payment/HyperPay_Visa/payment_action DB
"${BIN[@]}" config:set "${LOCK[@]}" payment/HyperPay_Mada/active 1
"${BIN[@]}" config:set "${LOCK[@]}" payment/HyperPay_Mada/entityId "$MADA"
"${BIN[@]}" config:set "${LOCK[@]}" payment/HyperPay_Mada/payment_action DB
"${BIN[@]}" config:set "${LOCK[@]}" payment/HyperPay_Master/active 0
"${BIN[@]}" config:set "${LOCK[@]}" payment/HyperPay_ApplePay/active 0

# also update DB rows for admin UI consistency
mysql -h tyresonline-sa-prod.cbybbkyzwyxj.eu-north-1.rds.amazonaws.com -u magento -puxaYMIQRwEU0AFl1Ck69LJnH tyresonline_sa <<SQL
UPDATE core_config_data SET value='$TOKEN' WHERE path='payment/hyperpay/auth' AND scope='default' AND scope_id=0;
UPDATE core_config_data SET value='$VISA' WHERE path='payment/HyperPay_Visa/entityId' AND scope='default' AND scope_id=0;
UPDATE core_config_data SET value='DB' WHERE path='payment/HyperPay_Visa/payment_action' AND scope='default' AND scope_id=0;
UPDATE core_config_data SET value='1' WHERE path='payment/HyperPay_Mada/active' AND scope='default' AND scope_id=0;
UPDATE core_config_data SET value='$MADA' WHERE path='payment/HyperPay_Mada/entityId' AND scope='default' AND scope_id=0;
UPDATE core_config_data SET value='DB' WHERE path='payment/HyperPay_Mada/payment_action' AND scope='default' AND scope_id=0;
UPDATE core_config_data SET value='0' WHERE path='payment/HyperPay_Master/active' AND scope='default' AND scope_id=0;
UPDATE core_config_data SET value='0' WHERE path='payment/HyperPay_ApplePay/active' AND scope='default' AND scope_id=0;
SQL

"${BIN[@]}" cache:flush
php /tmp/read-config-php-payment.php
