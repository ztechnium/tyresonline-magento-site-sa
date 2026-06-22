#!/bin/bash
set -euo pipefail
MAGENTO=/var/www/magento
BIN=(sudo -u www-data php "$MAGENTO/bin/magento")
SCOPE=(--scope=websites --scope-code=base)
TOKEN='OGFjOWE0ZmU5ZWQ1NzIyZDAxOWVkNThmMDk2ZjAwOGR8dDdydHdpIVlnY3NqQk5NTGUzPXg='
VISA='8acda4c88da6f653018dbb779a816465'
MADA='8acda4c88da6f653018dbb7828be646c'

"${BIN[@]}" config:set "${SCOPE[@]}" payment/hyperpay/mode live
"${BIN[@]}" config:set "${SCOPE[@]}" payment/hyperpay/auth "$TOKEN"
"${BIN[@]}" config:set "${SCOPE[@]}" payment/hyperpay/liveurl 'https://eu-prod.oppwa.com/v1/'
"${BIN[@]}" config:set "${SCOPE[@]}" payment/hyperpay/testurl 'https://eu-test.oppwa.com/v1/'
"${BIN[@]}" config:set "${SCOPE[@]}" payment/HyperPay_Visa/active 1
"${BIN[@]}" config:set "${SCOPE[@]}" payment/HyperPay_Visa/entityId "$VISA"
"${BIN[@]}" config:set "${SCOPE[@]}" payment/HyperPay_Visa/currencycode SAR
"${BIN[@]}" config:set "${SCOPE[@]}" payment/HyperPay_Mada/active 1
"${BIN[@]}" config:set "${SCOPE[@]}" payment/HyperPay_Mada/entityId "$MADA"
"${BIN[@]}" config:set "${SCOPE[@]}" payment/HyperPay_Mada/currencycode SAR
"${BIN[@]}" config:set "${SCOPE[@]}" payment/HyperPay_Master/active 0
"${BIN[@]}" config:set "${SCOPE[@]}" payment/tabby_installments/active 0

"${BIN[@]}" cache:flush

echo "=== VERIFY ==="
"${BIN[@]}" config:show payment/hyperpay/mode
"${BIN[@]}" config:show payment/HyperPay_Visa/entityId
"${BIN[@]}" config:show payment/HyperPay_Mada/entityId
"${BIN[@]}" config:show payment/HyperPay_Mada/active
"${BIN[@]}" config:show payment/HyperPay_Visa/currencycode
"${BIN[@]}" config:show payment/tabby_installments/active
